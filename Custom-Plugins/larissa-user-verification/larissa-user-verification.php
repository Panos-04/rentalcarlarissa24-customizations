<?php
/**
 * Plugin Name: Larissa – User Verification Uploads (Private, 15MB)
 * Description: Adds ID & Driver’s License uploads to WooCommerce registration + private storage + My Account re-upload endpoint + admin verification + secure admin downloads + emails.
 * Version:     2.1.0
 * Author:      You
 */

if ( ! defined('ABSPATH') ) exit;

final class Larissa_UV_Plugin {

    const TEXTDOMAIN = 'larissa24';

    // Config
    const MAX_BYTES = 15728640; // 15 * 1024 * 1024
    const MAX_LABEL = '15MB';

    // Retention: auto-delete docs after verified (days). Set to 0 to disable.
    const RETENTION_DAYS = 30;

    // Meta keys
    const META_STATUS   = 'larissa_verification_status';
    const META_REASON   = 'larissa_rejection_reason';
    const META_VER_AT   = 'larissa_verified_at';
    const META_VER_BY   = 'larissa_verified_by';
    const META_REJ_AT   = 'larissa_rejected_at';
    const META_REJ_BY   = 'larissa_rejected_by';

    // Document meta keys (each stores an array)
    const META_ID_FRONT = 'larissa_id_front';
    const META_ID_BACK  = 'larissa_id_back';
    const META_DL_FRONT = 'larissa_dl_front';
    const META_DL_BACK  = 'larissa_dl_back';

    // Optional: store last upload error for admin debugging
    const META_LAST_UPLOAD_ERROR = 'larissa_last_upload_error';

    const CRON_HOOK = 'larissa_uv_daily_cleanup';

    private static $instance = null;

    /** @return Larissa_UV_Plugin */
    public static function instance() {
        if ( self::$instance === null ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', [$this, 'boot']);
    }

    public static function activate() {
        if ( ! wp_next_scheduled(self::CRON_HOOK) ) {
            wp_schedule_event(time() + 3600, 'daily', self::CRON_HOOK);
        }
        flush_rewrite_rules();
    }

    public static function deactivate() {
        $ts = wp_next_scheduled(self::CRON_HOOK);
        if ( $ts ) wp_unschedule_event($ts, self::CRON_HOOK);
        flush_rewrite_rules();
    }

    public function boot() {
        if ( ! class_exists('WooCommerce') ) return;

        add_filter('woocommerce_enable_myaccount_registration', '__return_true');

        // Woo register form tag is an ACTION; echo enctype
        add_action('woocommerce_register_form_tag', function () {
            echo ' enctype="multipart/form-data"';
        });

        add_action('woocommerce_register_form', [$this, 'render_register_fields']);
        add_filter('woocommerce_registration_errors', [$this, 'validate_registration_uploads'], 10, 3);
        add_action('woocommerce_created_customer', [$this, 'save_registration_uploads'], 10, 1);

        // My Account endpoint for re-upload
        add_action('init', [$this, 'add_myaccount_endpoint']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_filter('woocommerce_account_menu_items', [$this, 'add_documents_menu_item']);
        add_action('woocommerce_account_documents_endpoint', [$this, 'render_documents_endpoint']);
        add_action('template_redirect', [$this, 'handle_documents_form_submit'], 1);

        // Admin profile UI + save
        add_action('show_user_profile', [$this, 'admin_profile_box']);
        add_action('edit_user_profile', [$this, 'admin_profile_box']);
        add_action('personal_options_update', [$this, 'admin_profile_save']);
        add_action('edit_user_profile_update', [$this, 'admin_profile_save']);

        // Secure admin download handler
        add_action('admin_post_larissa_uv_download', [$this, 'handle_admin_download']);

        // My Account dashboard status line
        add_action('woocommerce_account_dashboard', [$this, 'render_dashboard_status']);

        // Admin notices about PHP limits
        add_action('admin_notices', [$this, 'admin_server_limits_notice']);

        // Cleanup on user delete
        add_action('delete_user', [$this, 'delete_user_docs']);
        add_action('wpmu_delete_user', [$this, 'delete_user_docs']);

        // Daily retention cleanup
        add_action(self::CRON_HOOK, [$this, 'daily_cleanup_verified_docs']);
    }

    // -----------------------------------------------------
    // UI: Register fields
    // -----------------------------------------------------
    public function render_register_fields() {
        ?>
        <fieldset class="form-row-wide larissa-verify">
            <legend><?php esc_html_e('Identity & Driver’s License', self::TEXTDOMAIN); ?></legend>

            <p class="form-row form-row-wide">
                <label><?php esc_html_e('ID / Passport — Front', self::TEXTDOMAIN); ?> <span class="required">*</span></label>
                <input type="file" name="id_front" accept="image/jpeg,image/png,application/pdf" required>
                <small>
                    <?php esc_html_e('Allowed: JPG, PNG, PDF.', self::TEXTDOMAIN); ?>
                    <?php echo esc_html(sprintf(__('Max %s each.', self::TEXTDOMAIN), self::MAX_LABEL)); ?>
                </small>
            </p>

            <p class="form-row form-row-wide">
                <label><?php esc_html_e('ID / Passport — Back', self::TEXTDOMAIN); ?> <span class="required">*</span></label>
                <input type="file" name="id_back" accept="image/jpeg,image/png,application/pdf" required>
            </p>

            <p class="form-row form-row-first">
                <label><?php esc_html_e('Driver’s License — Front', self::TEXTDOMAIN); ?> <span class="required">*</span></label>
                <input type="file" name="dl_front" accept="image/jpeg,image/png,application/pdf" required>
            </p>

            <p class="form-row form-row-last">
                <label><?php esc_html_e('Driver’s License — Back', self::TEXTDOMAIN); ?> <span class="required">*</span></label>
                <input type="file" name="dl_back" accept="image/jpeg,image/png,application/pdf" required>
            </p>

            <p class="form-row">
                <small style="opacity:.7">
                    <?php esc_html_e('These documents are used to verify your identity for rentals. We store them securely and never share them with third parties.', self::TEXTDOMAIN); ?>
                </small>
            </p>
        </fieldset>
        <?php
    }

    // -----------------------------------------------------
    // Helpers
    // -----------------------------------------------------
    private function allowed_ext() {
        return ['jpg','jpeg','png','pdf'];
    }

    private function bytes_pretty($bytes) {
        $units = ['B','KB','MB','GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units)-1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, ($i === 0 ? 0 : 1)) . $units[$i];
    }

    /**
     * Guard for POST too large: when post_max_size is exceeded, PHP drops $_POST and $_FILES.
     * Returns a WC notice + redirect for endpoint POSTs.
     */
    private function guard_post_too_large_and_redirect_if_needed($redirect_url) {
        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) return false;

        if ( ! empty($_FILES) ) return false;
        if ( empty($_SERVER['CONTENT_LENGTH']) ) return false;

        if ( ! function_exists('wp_convert_hr_to_bytes') ) return false;

        $content_length = (int) $_SERVER['CONTENT_LENGTH'];
        $post_max = ini_get('post_max_size');
        $post_max_bytes = wp_convert_hr_to_bytes($post_max);

        if ( $post_max_bytes > 0 && $content_length > $post_max_bytes ) {
            if ( function_exists('wc_add_notice') ) {
                wc_add_notice(
                    sprintf(
                        __('Your upload is too large for the server (post_max_size=%1$s). Please reduce image sizes or increase PHP limits.', self::TEXTDOMAIN),
                        esc_html($post_max)
                    ),
                    'error'
                );
            }
            if ( $redirect_url ) {
                wp_safe_redirect($redirect_url);
                exit;
            }
            return true;
        }

        return false;
    }

    /** Returns base private dir (tries outside webroot, falls back). */
    private function private_base_dir() {
        // 1) Try one level above WP root (often outside public_html)
        $dir1 = trailingslashit(dirname(untrailingslashit(ABSPATH))) . 'odafihjgbpoadjfbpo';
        //sanitized the actuall dir
        if ( wp_mkdir_p($dir1) && is_writable($dir1) ) {
            $this->harden_dir($dir1);
            return wp_normalize_path($dir1);
        }

        // 2) Fallback inside wp-content
        $dir2 = trailingslashit(WP_CONTENT_DIR) . 'odafihjgbpoadjfbpo';
        //sanitized the actuall dir
        wp_mkdir_p($dir2);
        if ( is_dir($dir2) ) $this->harden_dir($dir2);
        return wp_normalize_path($dir2);
    }

    /** Adds index.html + (Apache) deny rules. */
    private function harden_dir($dir) {
        if ( ! is_dir($dir) ) return;

        $index = trailingslashit($dir) . 'index.html';
        if ( ! file_exists($index) ) @file_put_contents($index, '');

        // Apache protection (safe if ignored). Supports Apache 2.2 + 2.4
        $ht = trailingslashit($dir) . '.htaccess';
        if ( ! file_exists($ht) ) {
            $rules = "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Order deny,allow\n  Deny from all\n</IfModule>\n";
            @file_put_contents($ht, $rules);
        }
    }

    /** Returns a user-specific directory. */
    private function user_private_dir($user_id) {
        $base = $this->private_base_dir();
        $udir = trailingslashit($base) . 'larissa-uv/' . (int)$user_id;
        wp_mkdir_p($udir);
        $this->harden_dir($udir);
        return wp_normalize_path($udir);
    }

    /** Map form fields to meta keys. */
    private function doc_meta_map() {
        return [
            'id_front' => self::META_ID_FRONT,
            'id_back'  => self::META_ID_BACK,
            'dl_front' => self::META_DL_FRONT,
            'dl_back'  => self::META_DL_BACK,
        ];
    }

    private function required_fields() {
        return array_keys($this->doc_meta_map());
    }

    private function can_review_docs() {
        // adjust to your policy:
        return current_user_can('manage_woocommerce') || current_user_can('edit_users') || current_user_can('manage_options');
    }

    /**
     * Resolve a doc array into an absolute path.
     * Supports new format (rel) and old format (path).
     */
    private function resolve_doc_path($doc) {
        if ( ! is_array($doc) ) return '';

        // New stable format: relative path from private base
        if ( ! empty($doc['rel']) ) {
            $base = wp_normalize_path($this->private_base_dir());
            $abs  = trailingslashit($base) . ltrim((string)$doc['rel'], '/');
            return wp_normalize_path($abs);
        }

        // Old format: absolute stored path
        if ( ! empty($doc['path']) ) {
            return wp_normalize_path((string)$doc['path']);
        }

        return '';
    }

    private function doc_file_exists($doc) {
        $p = $this->resolve_doc_path($doc);
        return $p && is_file($p) && is_readable($p);
    }

    /** Delete a stored doc file safely. */
    private function delete_doc_file($doc) {
        $path = $this->resolve_doc_path($doc);
        if ( $path && file_exists($path) ) {
            @unlink($path);
        }
    }

    /**
     * Validate and move upload into private storage, return array doc or WP_Error.
     * Saves both absolute "path" (compat) and stable "rel".
     */
    private function handle_private_upload(array $file_arr, $user_id) {
        $allowed_ext = $this->allowed_ext();

        if ( empty($file_arr['tmp_name']) || empty($file_arr['name']) ) {
            return new WP_Error('upload_error', __('Missing upload data.', self::TEXTDOMAIN));
        }

        $err = isset($file_arr['error']) ? (int)$file_arr['error'] : UPLOAD_ERR_OK;
        if ( $err !== UPLOAD_ERR_OK ) {
            return new WP_Error('upload_error', sprintf(__('Upload error (code %d).', self::TEXTDOMAIN), $err));
        }

        $size = isset($file_arr['size']) ? (int)$file_arr['size'] : 0;
        if ( $size > self::MAX_BYTES ) {
            return new WP_Error('upload_error', sprintf(__('File exceeds the %s limit.', self::TEXTDOMAIN), self::MAX_LABEL));
        }

        $ft   = wp_check_filetype_and_ext($file_arr['tmp_name'], $file_arr['name']);
        $ext  = strtolower((string)($ft['ext'] ?? ''));
        $mime = (string)($ft['type'] ?? '');

        if ( empty($ext) || ! in_array($ext, $allowed_ext, true) ) {
            return new WP_Error('upload_error', __('Invalid file type. Allowed: JPG, PNG, PDF.', self::TEXTDOMAIN));
        }

        $dir = $this->user_private_dir($user_id);
        if ( ! is_dir($dir) || ! is_writable($dir) ) {
            return new WP_Error('upload_error', __('Private upload directory is not writable.', self::TEXTDOMAIN));
        }

        $safe_name = sanitize_file_name($file_arr['name']);
        $filename  = wp_unique_filename($dir, $safe_name);
        $target    = wp_normalize_path(trailingslashit($dir) . $filename);

        if ( ! @move_uploaded_file($file_arr['tmp_name'], $target) ) {
            if ( defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ) {
                error_log('Larissa UV: move_uploaded_file failed tmp=' . $file_arr['tmp_name'] . ' target=' . $target);
            }
            return new WP_Error('upload_error', __('Could not move uploaded file. Please contact support.', self::TEXTDOMAIN));
        }

        @chmod($target, 0600);

        $base = wp_normalize_path($this->private_base_dir());
        $rel  = ltrim(str_replace(trailingslashit($base), '', $target), '/');

        return [
            'path'        => $target, // backward compatibility
            'rel'         => $rel,    // stable
            'name'        => $safe_name,
            'mime'        => $mime ?: 'application/octet-stream',
            'ext'         => $ext,
            'size'        => $size,
            'uploaded_at' => current_time('mysql'),
        ];
    }

    /**
     * If a doc is in old format (path only), backfill rel so admin UI can survive migrations.
     */
    private function maybe_backfill_rel($doc) {
        if ( ! is_array($doc) ) return $doc;
        if ( ! empty($doc['rel']) ) return $doc;

        $path = ! empty($doc['path']) ? wp_normalize_path((string)$doc['path']) : '';
        if ( ! $path ) return $doc;

        $base = wp_normalize_path($this->private_base_dir());
        if ( strpos($path, trailingslashit($base)) === 0 ) {
            $doc['rel'] = ltrim(str_replace(trailingslashit($base), '', $path), '/');
        }

        return $doc;
    }

    // -----------------------------------------------------
    // Registration validation & save
    // -----------------------------------------------------
    public function validate_registration_uploads($errors, $username, $email) {
        // Detect post too large: if PHP dropped files, show a useful error.
        if ( empty($_FILES) && ! empty($_SERVER['CONTENT_LENGTH']) && function_exists('wp_convert_hr_to_bytes') ) {
            $post_max = ini_get('post_max_size');
            $post_max_bytes = wp_convert_hr_to_bytes($post_max);
            if ( $post_max_bytes > 0 && (int) $_SERVER['CONTENT_LENGTH'] > $post_max_bytes ) {
                $errors->add(
                    'larissa_uv_post_too_large',
                    sprintf(
                        __('Your upload is too large for the server (post_max_size=%1$s). Please reduce image sizes or increase PHP limits.', self::TEXTDOMAIN),
                        esc_html($post_max)
                    )
                );
                return $errors;
            }
        }

        $required = $this->required_fields();
        $allowed_ext = $this->allowed_ext();

        foreach ($required as $key) {
            if ( empty($_FILES[$key]) || empty($_FILES[$key]['name']) ) {
                $errors->add('missing_' . $key, sprintf(__('Please upload: %s', self::TEXTDOMAIN), esc_html(str_replace('_',' ', $key))));
                continue;
            }

            $err = isset($_FILES[$key]['error']) ? (int) $_FILES[$key]['error'] : UPLOAD_ERR_OK;
            if ( $err !== UPLOAD_ERR_OK ) {
                $errors->add('upload_err_' . $key, sprintf(__('Upload problem with %1$s (error code %2$d).', self::TEXTDOMAIN), esc_html(str_replace('_',' ', $key)), $err));
                continue;
            }

            $size = isset($_FILES[$key]['size']) ? (int) $_FILES[$key]['size'] : 0;
            if ( $size > self::MAX_BYTES ) {
                $errors->add('filesize_' . $key, sprintf(__('%1$s exceeds the %2$s limit.', self::TEXTDOMAIN), esc_html(str_replace('_',' ', $key)), esc_html(self::MAX_LABEL)));
            }

            $tmp = $_FILES[$key]['tmp_name'] ?? '';
            $nam = $_FILES[$key]['name'] ?? '';
            if ( $tmp && $nam ) {
                $ft  = wp_check_filetype_and_ext($tmp, $nam);
                $ext = strtolower((string)($ft['ext'] ?? ''));

                if ( empty($ext) || ! in_array($ext, $allowed_ext, true) ) {
                    $errors->add('filetype_' . $key, sprintf(__('%s must be JPG, PNG, or PDF.', self::TEXTDOMAIN), esc_html(str_replace('_',' ', $key))));
                }
            }
        }

        return $errors;
    }

    public function save_registration_uploads($customer_id) {
        $map = $this->doc_meta_map();

        // Attempt to save all 4 docs. If any fail, clean up partial saves and record a debug meta.
        $saved = [];
        $errors = [];

        foreach ($map as $field => $meta_key) {
            if ( empty($_FILES[$field]['name']) ) {
                $errors[] = 'Missing ' . $field;
                continue;
            }

            $doc = $this->handle_private_upload($_FILES[$field], $customer_id);
            if ( is_wp_error($doc) ) {
                $errors[] = $field . ': ' . $doc->get_error_message();
                continue;
            }

            $saved[$meta_key] = $doc;
        }

        if ( ! empty($errors) || count($saved) !== 4 ) {
            // delete any files that were moved
            foreach ($saved as $doc) {
                $this->delete_doc_file($doc);
            }

            // clear doc meta to avoid "half state"
            foreach (array_values($map) as $meta_key) {
                delete_user_meta($customer_id, $meta_key);
            }

            update_user_meta($customer_id, self::META_STATUS, 'pending');
            update_user_meta($customer_id, self::META_REASON, '');
            update_user_meta($customer_id, self::META_LAST_UPLOAD_ERROR, implode(' | ', $errors));
            return;
        }

        // Save docs meta
        foreach ($saved as $meta_key => $doc) {
            update_user_meta($customer_id, $meta_key, $doc);
        }

        delete_user_meta($customer_id, self::META_LAST_UPLOAD_ERROR);

        // Set pending
        update_user_meta($customer_id, self::META_STATUS, 'pending');
        update_user_meta($customer_id, self::META_REASON, '');
    }

    // -----------------------------------------------------
    // My Account endpoint: /my-account/documents/
    // -----------------------------------------------------
    public function add_myaccount_endpoint() {
        add_rewrite_endpoint('documents', EP_ROOT | EP_PAGES);
    }

    public function add_query_vars($vars) {
        $vars[] = 'documents';
        return $vars;
    }

    public function add_documents_menu_item($items) {
        $new = [];
        foreach ($items as $key => $label) {
            $new[$key] = $label;
            if ($key === 'dashboard') {
                $new['documents'] = __('Documents', self::TEXTDOMAIN);
            }
        }
        if ( ! isset($new['documents']) ) $new['documents'] = __('Documents', self::TEXTDOMAIN);
        return $new;
    }

    public function render_documents_endpoint() {
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            echo '<p>' . esc_html__('Please log in to manage documents.', self::TEXTDOMAIN) . '</p>';
            return;
        }

        $status = get_user_meta($user_id, self::META_STATUS, true) ?: 'pending';
        $reason = get_user_meta($user_id, self::META_REASON, true) ?: '';
        $last_err = get_user_meta($user_id, self::META_LAST_UPLOAD_ERROR, true);

        if ( function_exists('wc_print_notices') ) wc_print_notices();

        echo '<h3>' . esc_html__('Upload your verification documents', self::TEXTDOMAIN) . '</h3>';

        echo '<p><strong>' . esc_html__('Current status:', self::TEXTDOMAIN) . '</strong> ' . esc_html(ucfirst($status)) . '</p>';

        if ( $status === 'rejected' && $reason ) {
            echo '<p style="padding:10px 12px;border-left:4px solid #b91c1c;background:#fff5f5;">'
                . '<strong>' . esc_html__('Rejection reason:', self::TEXTDOMAIN) . '</strong> '
                . esc_html($reason)
                . '</p>';
        }

        if ( $last_err ) {
            echo '<p style="padding:10px 12px;border-left:4px solid #f59e0b;background:#fffbeb;">'
                . '<strong>' . esc_html__('Last upload issue (debug):', self::TEXTDOMAIN) . '</strong> '
                . esc_html($last_err)
                . '</p>';
        }

        // Show whether each doc exists (no public links)
        $docs = [
            __('ID / Passport — Front', self::TEXTDOMAIN)    => self::META_ID_FRONT,
            __('ID / Passport — Back', self::TEXTDOMAIN)     => self::META_ID_BACK,
            __('Driver’s License — Front', self::TEXTDOMAIN) => self::META_DL_FRONT,
            __('Driver’s License — Back', self::TEXTDOMAIN)  => self::META_DL_BACK,
        ];

        echo '<ul style="margin:12px 0 18px;opacity:.95;">';
        foreach ($docs as $label => $meta_key) {
            $doc = get_user_meta($user_id, $meta_key, true);
            $doc = $this->maybe_backfill_rel($doc);
            if ( is_array($doc) && ! empty($doc['rel']) ) {
                update_user_meta($user_id, $meta_key, $doc);
            }

            $has = $this->doc_file_exists($doc);
            echo '<li>' . esc_html($label) . ': ' . ($has ? '✅' : '❌') . '</li>';
        }
        echo '</ul>';
        ?>
        <form method="post" enctype="multipart/form-data" class="larissa-documents-form">
            <?php wp_nonce_field('larissa_uv_documents_update', 'larissa_uv_documents_nonce'); ?>

            <p>
                <label><?php esc_html_e('ID / Passport — Front', self::TEXTDOMAIN); ?> <span class="required">*</span></label><br>
                <input type="file" name="id_front" accept="image/jpeg,image/png,application/pdf" required>
            </p>

            <p>
                <label><?php esc_html_e('ID / Passport — Back', self::TEXTDOMAIN); ?> <span class="required">*</span></label><br>
                <input type="file" name="id_back" accept="image/jpeg,image/png,application/pdf" required>
            </p>

            <p>
                <label><?php esc_html_e('Driver’s License — Front', self::TEXTDOMAIN); ?> <span class="required">*</span></label><br>
                <input type="file" name="dl_front" accept="image/jpeg,image/png,application/pdf" required>
            </p>

            <p>
                <label><?php esc_html_e('Driver’s License — Back', self::TEXTDOMAIN); ?> <span class="required">*</span></label><br>
                <input type="file" name="dl_back" accept="image/jpeg,image/png,application/pdf" required>
            </p>

            <p style="opacity:.7">
                <?php echo esc_html(sprintf(__('Allowed: JPG, PNG, PDF. Max %s each.', self::TEXTDOMAIN), self::MAX_LABEL)); ?>
            </p>

            <button type="submit" name="larissa_uv_documents_submit" class="button">
                <?php esc_html_e('Submit documents', self::TEXTDOMAIN); ?>
            </button>
        </form>
        <?php
    }

    public function handle_documents_form_submit() {
        if ( ! function_exists('is_wc_endpoint_url') ) return;
        if ( ! is_wc_endpoint_url('documents') ) return;

        // Important: handle "POST too large" BEFORE checking submit flag (because $_POST can be empty).
        $this->guard_post_too_large_and_redirect_if_needed(
            function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('documents') : ''
        );

        if ( empty($_POST['larissa_uv_documents_submit']) ) return;

        $user_id = get_current_user_id();
        if ( ! $user_id ) return;

        if ( empty($_POST['larissa_uv_documents_nonce']) || ! wp_verify_nonce($_POST['larissa_uv_documents_nonce'], 'larissa_uv_documents_update') ) {
            wc_add_notice(__('Security check failed. Please try again.', self::TEXTDOMAIN), 'error');
            return;
        }

        // Validate required files
        $errors = new WP_Error();

        foreach ($this->required_fields() as $key) {
            if ( empty($_FILES[$key]) || empty($_FILES[$key]['name']) ) {
                $errors->add('missing_' . $key, sprintf(__('Please upload: %s', self::TEXTDOMAIN), esc_html(str_replace('_',' ', $key))));
                continue;
            }
            $err = isset($_FILES[$key]['error']) ? (int) $_FILES[$key]['error'] : UPLOAD_ERR_OK;
            if ( $err !== UPLOAD_ERR_OK ) {
                $errors->add('upload_err_' . $key, sprintf(__('Upload problem with %1$s (error code %2$d).', self::TEXTDOMAIN), esc_html(str_replace('_',' ', $key)), $err));
                continue;
            }
        }

        if ( $errors->has_errors() ) {
            foreach ( $errors->get_error_messages() as $msg ) {
                wc_add_notice($msg, 'error');
            }
            return;
        }

        // Save uploads (replace old docs) - all-or-nothing
        $map = $this->doc_meta_map();
        $new_docs = [];
        $failures = [];

        foreach ($map as $field => $meta_key) {
            $doc = $this->handle_private_upload($_FILES[$field], $user_id);
            if ( is_wp_error($doc) ) {
                $failures[] = $field . ': ' . $doc->get_error_message();
                continue;
            }
            $new_docs[$meta_key] = $doc;
        }

        if ( ! empty($failures) || count($new_docs) !== 4 ) {
            // cleanup moved files
            foreach ($new_docs as $doc) {
                $this->delete_doc_file($doc);
            }
            update_user_meta($user_id, self::META_LAST_UPLOAD_ERROR, implode(' | ', $failures) ?: 'Unknown upload failure');
            wc_add_notice(__('Upload failed. Please try again or reduce file sizes.', self::TEXTDOMAIN), 'error');
            return;
        }

        // delete old files
        foreach ($map as $field => $meta_key) {
            $old = get_user_meta($user_id, $meta_key, true);
            $this->delete_doc_file($old);
        }

        // store new meta
        foreach ($new_docs as $meta_key => $doc) {
            update_user_meta($user_id, $meta_key, $doc);
        }

        delete_user_meta($user_id, self::META_LAST_UPLOAD_ERROR);

        // reset status to pending when re-uploaded
        update_user_meta($user_id, self::META_STATUS, 'pending');
        update_user_meta($user_id, self::META_REASON, '');

        wc_add_notice(__('Documents uploaded successfully. Your verification is now pending review.', self::TEXTDOMAIN), 'success');

        wp_safe_redirect( wc_get_account_endpoint_url('documents') );
        exit;
    }

    // -----------------------------------------------------
    // Admin profile UI
    // -----------------------------------------------------
    public function admin_profile_box($user) {
        if ( ! $this->can_review_docs() ) return;

        $docs = [
            __('ID / Passport — Front', self::TEXTDOMAIN)    => self::META_ID_FRONT,
            __('ID / Passport — Back', self::TEXTDOMAIN)     => self::META_ID_BACK,
            __('Driver’s License — Front', self::TEXTDOMAIN) => self::META_DL_FRONT,
            __('Driver’s License — Back', self::TEXTDOMAIN)  => self::META_DL_BACK,
        ];

        $status = get_user_meta($user->ID, self::META_STATUS, true) ?: 'pending';
        $reason = get_user_meta($user->ID, self::META_REASON, true) ?: '';
        $last_err = get_user_meta($user->ID, self::META_LAST_UPLOAD_ERROR, true);

        ?>
        <h2><?php esc_html_e('Identity Verification', self::TEXTDOMAIN); ?></h2>

        <?php if ( $last_err ): ?>
            <div style="padding:10px 12px;border-left:4px solid #f59e0b;background:#fffbeb;margin:10px 0;">
                <strong><?php esc_html_e('Last upload issue (debug):', self::TEXTDOMAIN); ?></strong>
                <?php echo esc_html($last_err); ?>
            </div>
        <?php endif; ?>

        <table class="form-table">
            <?php foreach ($docs as $label => $meta_key):
                $doc = get_user_meta($user->ID, $meta_key, true);
                $doc = $this->maybe_backfill_rel($doc);
                if ( is_array($doc) && ! empty($doc['rel']) ) {
                    update_user_meta($user->ID, $meta_key, $doc);
                }

                $has = $this->doc_file_exists($doc);
                $dl_url = '';

                if ( $has ) {
                    $nonce = wp_create_nonce('larissa_uv_download_'.$user->ID.'_'.$meta_key);
                    $dl_url = admin_url('admin-post.php?action=larissa_uv_download&user_id='.$user->ID.'&key='.$meta_key.'&_wpnonce='.$nonce);
                }
                ?>
                <tr>
                    <th><label><?php echo esc_html($label); ?></label></th>
                    <td>
                        <?php if ( $has ): ?>
                            <a class="button button-secondary" href="<?php echo esc_url($dl_url); ?>">
                                <?php esc_html_e('Download', self::TEXTDOMAIN); ?>
                            </a>
                            <span style="opacity:.75;margin-left:8px;">
                                <?php echo esc_html($doc['name'] ?? 'document'); ?>
                                <?php if ( ! empty($doc['size']) ): ?>
                                    (<?php echo esc_html($this->bytes_pretty((int)$doc['size'])); ?>)
                                <?php endif; ?>
                            </span>
                        <?php else: ?>
                            <?php
                            // If meta exists but file is missing, say so (helps admins understand what's wrong).
                            if ( is_array($doc) && ( ! empty($doc['path']) || ! empty($doc['rel']) ) ) {
                                echo '<em>' . esc_html__('Uploaded meta exists, but file is missing/unreadable.', self::TEXTDOMAIN) . '</em>';
                            } else {
                                echo '<em>' . esc_html__('Not uploaded', self::TEXTDOMAIN) . '</em>';
                            }
                            ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            <tr>
                <th><label for="larissa_verification_status"><?php esc_html_e('Verification Status', self::TEXTDOMAIN); ?></label></th>
                <td>
                    <select name="larissa_verification_status" id="larissa_verification_status">
                        <option value="pending"  <?php selected($status,'pending');  ?>><?php esc_html_e('Pending', self::TEXTDOMAIN); ?></option>
                        <option value="verified" <?php selected($status,'verified'); ?>><?php esc_html_e('Verified', self::TEXTDOMAIN); ?></option>
                        <option value="rejected" <?php selected($status,'rejected'); ?>><?php esc_html_e('Rejected', self::TEXTDOMAIN); ?></option>
                    </select>

                    <p style="margin-top:10px;">
                        <label for="larissa_rejection_reason"><strong><?php esc_html_e('Rejection reason (optional)', self::TEXTDOMAIN); ?></strong></label><br>
                        <textarea name="larissa_rejection_reason" id="larissa_rejection_reason" rows="3" style="width:420px;max-width:100%;"><?php echo esc_textarea($reason); ?></textarea>
                    </p>

                    <?php wp_nonce_field('larissa_uv_save_'.$user->ID, 'larissa_uv_nonce'); ?>
                    <p class="description"><?php esc_html_e('Set the status after reviewing the documents.', self::TEXTDOMAIN); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function admin_profile_save($user_id) {
        if ( empty($_POST['larissa_uv_nonce']) || ! wp_verify_nonce($_POST['larissa_uv_nonce'], 'larissa_uv_save_'.$user_id) ) return;
        if ( ! current_user_can('edit_user', $user_id) ) return;

        $old = get_user_meta($user_id, self::META_STATUS, true) ?: 'pending';
        $new = isset($_POST['larissa_verification_status']) ? sanitize_text_field($_POST['larissa_verification_status']) : $old;

        $reason = isset($_POST['larissa_rejection_reason']) ? sanitize_textarea_field($_POST['larissa_rejection_reason']) : '';
        update_user_meta($user_id, self::META_REASON, $reason);

        if ( $new === $old ) return;

        update_user_meta($user_id, self::META_STATUS, $new);

        if ( $new === 'verified' ) {
            update_user_meta($user_id, self::META_VER_AT, current_time('mysql'));
            update_user_meta($user_id, self::META_VER_BY, get_current_user_id());
            $this->send_verified_email($user_id);
        }

        if ( $new === 'rejected' ) {
            update_user_meta($user_id, self::META_REJ_AT, current_time('mysql'));
            update_user_meta($user_id, self::META_REJ_BY, get_current_user_id());
            $this->send_rejected_email($user_id, $reason);
        }
    }

    // -----------------------------------------------------
    // Secure admin downloads
    // -----------------------------------------------------
    public function handle_admin_download() {
        if ( ! $this->can_review_docs() ) wp_die('Forbidden', 403);

        $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
        $key     = isset($_GET['key']) ? sanitize_key($_GET['key']) : '';

        $valid_keys = [self::META_ID_FRONT, self::META_ID_BACK, self::META_DL_FRONT, self::META_DL_BACK];
        if ( ! $user_id || ! $key || ! in_array($key, $valid_keys, true) ) wp_die('Bad request', 400);

        $nonce_action = 'larissa_uv_download_' . $user_id . '_' . $key;
        if ( empty($_GET['_wpnonce']) || ! wp_verify_nonce($_GET['_wpnonce'], $nonce_action) ) {
            wp_die('Invalid nonce', 403);
        }

        $doc = get_user_meta($user_id, $key, true);
        $doc = $this->maybe_backfill_rel($doc);
        if ( is_array($doc) && ! empty($doc['rel']) ) {
            update_user_meta($user_id, $key, $doc);
        }

        $path = $this->resolve_doc_path($doc);
        if ( ! $path || ! file_exists($path) ) wp_die('Not found', 404);

        $filename = ! empty($doc['name']) ? $doc['name'] : basename($path);
        $mime     = ! empty($doc['mime']) ? $doc['mime'] : 'application/octet-stream';

        nocache_headers();
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Content-Length: ' . filesize($path));

        readfile($path);
        exit;
    }

    // -----------------------------------------------------
    // Emails (Woo mailer friendly)
    // -----------------------------------------------------
    private function send_verified_email($user_id) {
        $user = get_userdata($user_id);
        if ( ! $user || empty($user->user_email) ) return;

        $site = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $subject = sprintf(__('Your account was verified — %s', self::TEXTDOMAIN), $site);

        $body_html = sprintf(
            '<p>%s</p><p>%s</p><p>%s</p>',
            esc_html(sprintf(__('Hello %s,', self::TEXTDOMAIN), $user->display_name ?: $user->user_login)),
            esc_html(__('Great news — your identity documents have been verified.', self::TEXTDOMAIN)),
            esc_html(sprintf(__('Thanks, %s', self::TEXTDOMAIN), $site))
        );

        if ( function_exists('WC') && WC() && WC()->mailer() ) {
            $mailer  = WC()->mailer();
            $heading = __('Account verified', self::TEXTDOMAIN);
            $wrapped = $mailer->wrap_message($heading, $body_html);
        
            // Woo expects headers as a string or array; don't call WC_Emails::get_headers()
            $headers = ['Content-Type: text/html; charset=UTF-8'];
        
            $mailer->send($user->user_email, $subject, $wrapped, $headers, []);
            return;
        }

        wp_mail($user->user_email, $subject, wp_strip_all_tags($body_html));
    }

    private function send_rejected_email($user_id, $reason = '') {
        $user = get_userdata($user_id);
        if ( ! $user || empty($user->user_email) ) return;

        $site = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $subject = sprintf(__('We need new copies of your documents — %s', self::TEXTDOMAIN), $site);

        $myaccount = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
        $reupload_url = function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('documents') : trailingslashit($myaccount) . 'documents/';

        $reason_html = '';
        if ( $reason ) {
            $reason_html = '<p style="margin:12px 0 0;color:#444;"><strong>' .
                esc_html__('Reason:', self::TEXTDOMAIN) . '</strong> ' . esc_html($reason) . '</p>';
        }

        $body_html = sprintf(
            '<p>%s</p>
             <p>%s</p>
             %s
             <p><a href="%s" style="display:inline-block;background:#b91c1c;color:#fff;padding:10px 14px;border-radius:8px;text-decoration:none;">%s</a></p>
             <p style="color:#666;font-size:12px;">%s</p>',
            esc_html(sprintf(__('Hello %s,', self::TEXTDOMAIN), $user->display_name ?: $user->user_login)),
            esc_html(__('After review, we could not verify your documents. Please re-upload clear photos.', self::TEXTDOMAIN)),
            $reason_html,
            esc_url($reupload_url),
            esc_html__('Re-upload documents', self::TEXTDOMAIN),
            esc_html__('If you have questions, reply to this email and our team will help.', self::TEXTDOMAIN)
        );

        if ( function_exists('WC') && WC() && WC()->mailer() ) {
            $mailer  = WC()->mailer();
            $heading = __('Verification required: re-upload documents', self::TEXTDOMAIN);
            $wrapped = $mailer->wrap_message($heading, $body_html);
        
            $headers = ['Content-Type: text/html; charset=UTF-8'];
        
            $mailer->send($user->user_email, $subject, $wrapped, $headers, []);
            return;
        }

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        wp_mail($user->user_email, $subject, $body_html, $headers);
    }

    // -----------------------------------------------------
    // My Account dashboard status
    // -----------------------------------------------------
    public function render_dashboard_status() {
        $user_id = get_current_user_id();
        if ( ! $user_id ) return;

        $status = get_user_meta($user_id, self::META_STATUS, true) ?: 'pending';
        $docs_url = function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('documents') : '';

        echo '<p class="larissa-verify-status"><strong>' .
             esc_html__('Verification status:', self::TEXTDOMAIN) .
             '</strong> ' . esc_html(ucfirst($status));

        if ( $docs_url ) {
            echo ' — <a href="' . esc_url($docs_url) . '">' . esc_html__('Manage documents', self::TEXTDOMAIN) . '</a>';
        }

        echo '</p>';
    }

    // -----------------------------------------------------
    // Server limits admin notice
    // -----------------------------------------------------
    public function admin_server_limits_notice() {
        if ( ! current_user_can('manage_options') ) return;

        $upload_max = ini_get('upload_max_filesize');
        $post_max   = ini_get('post_max_size');
        $max_files  = ini_get('max_file_uploads');

        if ( function_exists('wp_convert_hr_to_bytes') ) {
            $upload_bytes = wp_convert_hr_to_bytes($upload_max);
            $post_bytes   = wp_convert_hr_to_bytes($post_max);

            $recommended_post = self::MAX_BYTES * 4 + (2 * 1024 * 1024);

            if ( $upload_bytes > 0 && $upload_bytes < self::MAX_BYTES ) {
                echo '<div class="notice notice-warning"><p><strong>Larissa uploads:</strong> PHP <code>upload_max_filesize</code> is ' .
                    esc_html($upload_max) . ' but the plugin allows ' . esc_html(self::MAX_LABEL) .
                    '. Increase PHP limits to avoid failed uploads.</p></div>';
            }

            if ( $post_bytes > 0 && $post_bytes < $recommended_post ) {
                echo '<div class="notice notice-warning"><p><strong>Larissa uploads:</strong> PHP <code>post_max_size</code> is ' .
                    esc_html($post_max) .
                    '. For 4 files up to ' . esc_html(self::MAX_LABEL) . ' each, set post_max_size to at least ' .
                    esc_html($this->bytes_pretty($recommended_post)) .
                    ' (recommended higher).</p></div>';
            }

            if ( $max_files !== '' && is_numeric($max_files) && (int)$max_files < 4 ) {
                echo '<div class="notice notice-warning"><p><strong>Larissa uploads:</strong> PHP <code>max_file_uploads</code> is ' .
                    esc_html($max_files) . '. It should be at least 4.</p></div>';
            }
        }
    }

    // -----------------------------------------------------
    // Delete docs on user delete
    // -----------------------------------------------------
    public function delete_user_docs($user_id) {
        $keys = [self::META_ID_FRONT, self::META_ID_BACK, self::META_DL_FRONT, self::META_DL_BACK];

        foreach ($keys as $k) {
            $doc = get_user_meta($user_id, $k, true);
            $this->delete_doc_file($doc);
            delete_user_meta($user_id, $k);
        }
        delete_user_meta($user_id, self::META_STATUS);
        delete_user_meta($user_id, self::META_REASON);
        delete_user_meta($user_id, self::META_VER_AT);
        delete_user_meta($user_id, self::META_VER_BY);
        delete_user_meta($user_id, self::META_REJ_AT);
        delete_user_meta($user_id, self::META_REJ_BY);
        delete_user_meta($user_id, self::META_LAST_UPLOAD_ERROR);
    }

    // -----------------------------------------------------
    // Retention cleanup: delete docs for verified users after X days
    // -----------------------------------------------------
    public function daily_cleanup_verified_docs() {
        if ( (int)self::RETENTION_DAYS <= 0 ) return;

        $cutoff_ts = time() - ((int)self::RETENTION_DAYS * DAY_IN_SECONDS);
        $cutoff = gmdate('Y-m-d H:i:s', $cutoff_ts);

        $user_query = new WP_User_Query([
            'fields' => 'ID',
            'number' => 200,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key'     => self::META_STATUS,
                    'value'   => 'verified',
                    'compare' => '='
                ],
                [
                    'key'     => self::META_VER_AT,
                    'value'   => $cutoff,
                    'compare' => '<=',
                    'type'    => 'DATETIME'
                ],
            ],
        ]);

        $ids = $user_query->get_results();
        if ( empty($ids) ) return;

        foreach ($ids as $user_id) {
            foreach ([self::META_ID_FRONT, self::META_ID_BACK, self::META_DL_FRONT, self::META_DL_BACK] as $k) {
                $doc = get_user_meta($user_id, $k, true);
                $this->delete_doc_file($doc);
                delete_user_meta($user_id, $k);
            }
        }
    }
}

// Init
Larissa_UV_Plugin::instance();

// Hooks
register_activation_hook(__FILE__, ['Larissa_UV_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['Larissa_UV_Plugin', 'deactivate']);


// ============================================
// Username availability check (AJAX) + inline UI
// ============================================

// Enqueue script only on My Account page (where register form usually is)
add_action('wp_enqueue_scripts', function () {
    if ( function_exists('is_account_page') && is_account_page() ) {
        wp_enqueue_script(
            'larissa-uv-username-check',
            plugin_dir_url(__FILE__) . 'assets/larissa-uv-username-check.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('larissa-uv-username-check', 'LarissaUV', [
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('larissa_uv_username_check'),
            'takenMsg'  => __('Username is already taken.', 'larissa24'),
            'okMsg'     => __('Username is available.', 'larissa24'),
            'checking'  => __('Checking…', 'larissa24'),
        ]);
    }
});

// AJAX handlers (logged out + logged in)
add_action('wp_ajax_nopriv_larissa_uv_check_username', 'larissa_uv_check_username');
add_action('wp_ajax_larissa_uv_check_username', 'larissa_uv_check_username');

function larissa_uv_check_username() {
    check_ajax_referer('larissa_uv_username_check', 'nonce');

    $username_raw = isset($_POST['username']) ? (string) $_POST['username'] : '';
    $username     = sanitize_user($username_raw, true);

    // Treat empty / invalid as "not available" so user fixes it
    if ( $username === '' || $username !== $username_raw ) {
        wp_send_json_success([
            'exists'  => true,
            'invalid' => true,
        ]);
    }

    $exists = username_exists($username) ? true : false;

    wp_send_json_success([
        'exists'  => $exists,
        'invalid' => false,
    ]);
}


add_action('wp_enqueue_scripts', function () {
    if ( function_exists('is_account_page') && is_account_page() ) {

        // If you're on an endpoint (orders, edit-account, documents, etc.), query var is set
        $is_endpoint = false;
        if ( function_exists('WC') && WC() && ! empty(WC()->query) ) {
            $is_endpoint = WC()->query->get_current_endpoint() ? true : false;
        }

        if ( ! $is_endpoint ) {
            wp_enqueue_style(
                'larissa-uv-myaccount',
                plugin_dir_url(__FILE__) . 'assets/my_acc_styles.css',
                [],
                '1.0.0'
            );
        }
    }
});
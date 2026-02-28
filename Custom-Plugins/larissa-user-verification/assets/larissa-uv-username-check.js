jQuery(function ($) {
    // Woo register username input is usually: #reg_username (name="username")
    const $username = $('#reg_username').length ? $('#reg_username') : $('input[name="username"]').first();
    if (!$username.length) return;

    const $form = $username.closest('form');
    const $submit = $form.find('button[type="submit"], input[type="submit"]').first();

    // Create inline message element near the field
    let $msg = $form.find('.larissa-uv-username-msg');
    if (!$msg.length) {
        $msg = $('<small class="larissa-uv-username-msg" style="display:block;margin-top:6px;"></small>');
        // Put it right after the username input
        $username.after($msg);
    }

    let timer = null;
    let lastValue = '';
    let pending = false;

    function setState({ text = '', ok = false, error = false, checking = false }) {
        $msg.text(text);

        // Basic styling (you can move this to CSS if you prefer)
        $msg.css({
            opacity: text ? 1 : 0.7,
            color: checking ? '#555' : (error ? '#b91c1c' : (ok ? '#166534' : '#555'))
        });

        // Native browser validation hook
        if (error) {
            $username[0].setCustomValidity(text || LarissaUV.takenMsg);
        } else {
            $username[0].setCustomValidity('');
        }

        // Disable submit while checking or invalid
        if ($submit.length) {
            $submit.prop('disabled', checking || error);
        }
    }

    async function checkUsername(value) {
        if (!value) {
            setState({ text: '', ok: false, error: false, checking: false });
            return;
        }

        pending = true;
        setState({ text: LarissaUV.checking, checking: true });

        try {
            const resp = await $.post(LarissaUV.ajaxUrl, {
                action: 'larissa_uv_check_username',
                nonce: LarissaUV.nonce,
                username: value
            });

            pending = false;

            if (!resp || !resp.success || !resp.data) {
                // Fail open: don’t block user if AJAX fails; clear message
                setState({ text: '', ok: false, error: false, checking: false });
                return;
            }

            if (resp.data.invalid) {
                setState({ text: 'Please use a valid username.', ok: false, error: true, checking: false });
                return;
            }

            if (resp.data.exists) {
                setState({ text: LarissaUV.takenMsg, ok: false, error: true, checking: false });
            } else {
                setState({ text: LarissaUV.okMsg, ok: true, error: false, checking: false });
            }
        } catch (e) {
            pending = false;
            // Fail open
            setState({ text: '', ok: false, error: false, checking: false });
        }
    }

    // Debounced check on input
    $username.on('input blur', function () {
        const val = ($username.val() || '').trim();

        if (val === lastValue) return;
        lastValue = val;

        if (timer) clearTimeout(timer);
        timer = setTimeout(() => checkUsername(val), 350);
    });

    // Prevent submit if invalid (setCustomValidity handles this too, but we’ll be explicit)
    $form.on('submit', function (e) {
        if (pending) {
            e.preventDefault();
            $username.trigger('blur');
            return false;
        }
        if ($username[0].validationMessage) {
            // Forces browser to show the message (some browsers)
            $username[0].reportValidity && $username[0].reportValidity();
            e.preventDefault();
            return false;
        }
    });
});

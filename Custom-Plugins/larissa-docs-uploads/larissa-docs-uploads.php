<?php
/**
 * Plugin Name: Larissa Docs Uploads
 * Description: Checkout uploads for ID/Driver's License with status-aware rendering (guest/pending/verified/rejected). Saves to order and user.
 * Version:     1.2.0
 * Author:      You
 */
// Make sure WooCommerce functions exist
if ( ! function_exists( 'larissa_require_login_for_checkout' ) ) {

// ---------- Fix registration redirect fatal error & use safe redirect logic ----------
/**
 * Defensive cleanup: remove any stray hook pointing at a missing function name.
 * If an earlier version left add_filter('woocommerce_registration_redirect','larissa_return_after_auth')
 * this will stop WP trying to call that non-existing callback.
 */
if ( has_filter( 'woocommerce_registration_redirect', 'larissa_return_after_auth' ) ) {
    remove_filter( 'woocommerce_registration_redirect', 'larissa_return_after_auth', 10 );
}

/**
 * Correct registration redirect handler.
 * WooCommerce applies this filter with 1 argument: the $redirect URL.
 * So our callback MUST accept exactly one argument.
 */
add_filter( 'woocommerce_registration_redirect', 'larissa_registration_redirect', 10, 1 );
function larissa_registration_redirect( $redirect ) {
    // Prefer WooCommerce session saved target
    if ( function_exists( 'WC' ) && WC()->session ) {
        $target = WC()->session->get( 'larissa_after_auth' );
        if ( $target ) {
            WC()->session->__unset( 'larissa_after_auth' );
            return esc_url_raw( $target );
        }
    }

    // fallback to redirect_to query parameter if present
    if ( isset( $_GET['redirect_to'] ) ) {
        $raw = wp_unslash( $_GET['redirect_to'] );
        $decoded = rawurldecode( $raw );
        if ( $decoded ) {
            return esc_url_raw( $decoded );
        }
    }

    // default WooCommerce redirect
    return $redirect;
}

/**
 * Also ensure login redirect uses the same logic (one of these should already exist,
 * but re-add here just in case).
 */
add_filter( 'woocommerce_login_redirect', 'larissa_login_redirect', 10, 2 );
function larissa_login_redirect( $redirect, $user ) {
    if ( function_exists( 'WC' ) && WC()->session ) {
        $target = WC()->session->get( 'larissa_after_auth' );
        if ( $target ) {
            WC()->session->__unset( 'larissa_after_auth' );
            return esc_url_raw( $target );
        }
    }

    if ( isset( $_GET['redirect_to'] ) ) {
        $raw = wp_unslash( $_GET['redirect_to'] );
        $decoded = rawurldecode( $raw );
        if ( $decoded ) {
            return esc_url_raw( $decoded );
        }
    }

    return $redirect;
}

/**
 * Fallback catch: if WooCommerce flow is bypassed, redirect after wp_login if session exists.
 */
add_action( 'wp_login', 'larissa_wp_login_fallback_redirect', 10, 2 );
function larissa_wp_login_fallback_redirect( $user_login, $user ) {
    if ( is_admin() ) {
        return;
    }

    if ( function_exists( 'WC' ) && WC()->session ) {
        $target = WC()->session->get( 'larissa_after_auth' );
        if ( $target ) {
            WC()->session->__unset( 'larissa_after_auth' );
            wp_safe_redirect( esc_url_raw( $target ) );
            exit;
        }
    }
}

}
// -- END larissa auth / redirect helpers ------------------------------------
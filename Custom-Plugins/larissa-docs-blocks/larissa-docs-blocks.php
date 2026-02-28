<?php
/**
 * Plugin Name: Larissa Docs + Checkout Enhancements
 * Description: ID/License uploads, verification workflow, guest/register choice at checkout, and IBAN instructions for BACS.
 * Author: You
 * Version: 1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * =========================================================
 * CONFIG: Checkout profile switch
 * =========================================================
 *
 * minimal       = first/last/email/phone (+ optional country), no shipping section, no order notes
 * billing_only  = FULL billing details (address etc), no shipping section, order notes optional
 * full          = default WooCommerce checkout (billing + shipping + order notes)
 *
 * Change ONLY this line later when you enable cards / want full details:
 */
if ( ! defined( 'LARISSA_CHECKOUT_PROFILE' ) ) {
    define( 'LARISSA_CHECKOUT_PROFILE', 'full' ); // <-- change to 'billing_only' or 'full'
}

/* -------------------------------------------------------
 * 1) Shortcode: [larissa_checkout]
 * ----------------------------------------------------- */
add_action( 'init', function () {
    add_shortcode( 'larissa_checkout', 'larissa_render_checkout_shortcode' );
} );

function larissa_render_checkout_shortcode( $atts = [], $content = '' ) {
    if ( is_admin() ) {
        return '';
    }

    // Keep Woo hooks working and render the standard checkout
    ob_start();

    echo '<div class="larissa-checkout-wrapper">';

    // Small status banner for logged-in users
    if ( is_user_logged_in() ) {
        $uid    = get_current_user_id();
        $status = get_user_meta( $uid, 'larissa_verification_status', true );

        if ( $status === 'verified' ) {
            echo '<div class="larissa-verify-banner larissa-verify--ok">✅ ID & Driver’s License verified.</div>';
        } elseif ( $status === 'pending' ) {
            echo '<div class="larissa-verify-banner larissa-verify--pending">⏳ Your documents are under review. You can place your order now — we usually verify shortly after.</div>';
        } elseif ( $status === 'rejected' ) {
            echo '<div class="larissa-verify-banner larissa-verify--bad">❗ Verification rejected — please upload clear photos again below.</div>';
        }
    }

    // Render the native WooCommerce checkout (keeps totals, gateways, coupons, etc)
    echo do_shortcode( '[woocommerce_checkout]' );

    echo '</div>';

    return ob_get_clean();
}

/* -------------------------------------------------------
 * 2) Checkout field behavior (switchable)
 * ----------------------------------------------------- */

/**
 * Shipping address section visibility.
 * WooCommerce only shows shipping fields if it thinks it "needs shipping".
 * We force it ON only in full profile.
 */
add_filter( 'woocommerce_cart_needs_shipping_address', function( $needs_address ) {

    if ( LARISSA_CHECKOUT_PROFILE === 'full' ) {
        return true;
    }

    // minimal + billing_only: keep shipping section hidden
    return false;

}, 99 );

/**
 * Order notes visibility.
 */
add_filter( 'woocommerce_enable_order_notes_field', function( $enabled ) {

    if ( LARISSA_CHECKOUT_PROFILE === 'minimal' ) {
        return false;
    }

    // billing_only or full: show notes (set to false if you prefer)
    return true;

}, 99 );

/**
 * Main checkout fields filter.
 * - full: do nothing, WooCommerce defaults
 * - billing_only: keep ALL billing fields, remove shipping fields
 * - minimal: keep only a few billing fields, remove shipping fields, remove notes via filter above
 */
add_filter( 'woocommerce_checkout_fields', function( $fields ) {

    // 1) FULL = return defaults untouched
    if ( LARISSA_CHECKOUT_PROFILE === 'full' ) {
        return $fields;
    }

    // 2) BILLING ONLY = keep all billing fields, remove shipping fields
    if ( LARISSA_CHECKOUT_PROFILE === 'billing_only' ) {
        $fields['shipping'] = []; // hide shipping fields
        return $fields;
    }

    // 3) MINIMAL = keep only a small set of billing fields, remove shipping
    // NOTE: This is your old behavior, but now it’s controlled by the profile switch.
    if ( LARISSA_CHECKOUT_PROFILE === 'minimal' ) {

        $keep = [
            'billing_first_name',
            'billing_last_name',
            // 'billing_country', // <-- uncomment if you want Country in minimal mode
            'billing_phone',
            'billing_email',
        ];

        if ( isset( $fields['billing'] ) && is_array( $fields['billing'] ) ) {
            foreach ( $fields['billing'] as $key => $data ) {
                if ( ! in_array( $key, $keep, true ) ) {
                    unset( $fields['billing'][ $key ] );
                }
            }
        }

        // Nice labels/placeholders/priorities (only for fields that exist in minimal)
        if ( isset( $fields['billing']['billing_first_name'] ) ) {
            $fields['billing']['billing_first_name']['label']       = __( 'First name', 'larissa24' );
            $fields['billing']['billing_first_name']['placeholder'] = __( 'First name', 'larissa24' );
            $fields['billing']['billing_first_name']['priority']    = 10;
            $fields['billing']['billing_first_name']['required']    = true;
            $fields['billing']['billing_first_name']['class']       = [ 'form-row-first' ];
        }
        if ( isset( $fields['billing']['billing_last_name'] ) ) {
            $fields['billing']['billing_last_name']['label']       = __( 'Last name', 'larissa24' );
            $fields['billing']['billing_last_name']['placeholder'] = __( 'Last name', 'larissa24' );
            $fields['billing']['billing_last_name']['priority']    = 20;
            $fields['billing']['billing_last_name']['required']    = true;
            $fields['billing']['billing_last_name']['class']       = [ 'form-row-last' ];
        }
        if ( isset( $fields['billing']['billing_country'] ) ) {
            $fields['billing']['billing_country']['label']       = __( 'Country/Region', 'larissa24' );
            $fields['billing']['billing_country']['priority']    = 30;
            $fields['billing']['billing_country']['required']    = true;
            $fields['billing']['billing_country']['class']       = [ 'form-row-wide' ];
            $fields['billing']['billing_country']['clear']       = true;
        }
        if ( isset( $fields['billing']['billing_phone'] ) ) {
            $fields['billing']['billing_phone']['label']       = __( 'Phone', 'larissa24' );
            $fields['billing']['billing_phone']['placeholder'] = __( 'Phone', 'larissa24' );
            $fields['billing']['billing_phone']['priority']    = 40;
            $fields['billing']['billing_phone']['required']    = true;
            $fields['billing']['billing_phone']['class']       = [ 'form-row-first' ];
        }
        if ( isset( $fields['billing']['billing_email'] ) ) {
            $fields['billing']['billing_email']['label']       = __( 'Email address', 'larissa24' );
            $fields['billing']['billing_email']['placeholder'] = __( 'Email address', 'larissa24' );
            $fields['billing']['billing_email']['priority']    = 50;
            $fields['billing']['billing_email']['required']    = true;
            $fields['billing']['billing_email']['class']       = [ 'form-row-last' ];
        }

        // Hide shipping fields entirely in minimal mode
        $fields['shipping'] = [];

        return $fields;
    }

    // Fallback: return as-is
    return $fields;

}, 20 );

/* -------------------------------------------------------
 * 3) Other features you mentioned (ID/License uploads, etc.)
 * ----------------------------------------------------- */
/*
 * If your real plugin has more sections below (uploads, verification workflow,
 * BACS instructions, etc.), keep them exactly as they are.
 * The only part we replaced is the "Minimal checkout" block to make it switchable.
 */

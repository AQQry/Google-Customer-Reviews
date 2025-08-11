<?php
/*
 * Plugin Name: TP Google Customer Reviews for WooCommerce
 * Description: Integrates Google Customer Reviews with WooCommerce, collecting customer feedback after purchase with configurable Merchant ID, language, and delivery time.
 * Version: 1.4.0
 * Author: TopPosition.eu
 * Author URI: https://www.topposition.eu/
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0
 * Text Domain: tp-gcr
 */

// Load plugin translations
function tp_gcr_load_textdomain() {
    load_plugin_textdomain( 'tp-gcr', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'tp_gcr_load_textdomain' );

// Retrieve plugin settings with minimized get_option calls
function tp_gcr_get_options() {
    static $options = null;
    if ( null === $options ) {
        $options = [
            'enabled'       => (bool) get_option( 'tp_gcr_enabled', 1 ),
            'merchant_id'   => get_option( 'tp_gcr_merchant_id', '' ),
            'language'      => get_option( 'tp_gcr_language', 'en' ),
            'delivery_days' => (int) get_option( 'tp_gcr_delivery_days', 3 ),
        ];
    }
    return $options;
}

// Add plugin settings menu
function tp_gcr_add_admin_menu() {
    add_options_page(
        __( 'TP Google Customer Reviews', 'tp-gcr' ),
        __( 'TP Google Customer Reviews', 'tp-gcr' ),
        'manage_options',
        'tp-google-customer-reviews',
        'tp_gcr_options_page'
    );
}
add_action( 'admin_menu', 'tp_gcr_add_admin_menu' );

// Sanitization functions
function tp_gcr_sanitize_merchant_id( $value ) {
    return absint( $value );
}

function tp_gcr_sanitize_language( $value ) {
    return sanitize_text_field( $value );
}

function tp_gcr_sanitize_delivery_days( $value ) {
    return absint( $value );
}

function tp_gcr_sanitize_enabled( $value ) {
    return $value ? 1 : 0;
}

// Register settings
function tp_gcr_settings_init() {
    register_setting( 'tp_gcr_settings', 'tp_gcr_enabled', 'tp_gcr_sanitize_enabled' );
    register_setting( 'tp_gcr_settings', 'tp_gcr_merchant_id', 'tp_gcr_sanitize_merchant_id' );
    register_setting( 'tp_gcr_settings', 'tp_gcr_language', 'tp_gcr_sanitize_language' );
    register_setting( 'tp_gcr_settings', 'tp_gcr_delivery_days', 'tp_gcr_sanitize_delivery_days' );

    add_settings_section(
        'tp_gcr_section',
        __( 'Google Customer Reviews Settings', 'tp-gcr' ),
        null,
        'tp-google-customer-reviews'
    );

    add_settings_field(
        'tp_gcr_enabled',
        __( 'Enable Google Customer Reviews', 'tp-gcr' ),
        'tp_gcr_enabled_render',
        'tp-google-customer-reviews',
        'tp_gcr_section'
    );

    add_settings_field(
        'tp_gcr_merchant_id',
        __( 'Google Merchant ID', 'tp-gcr' ),
        'tp_gcr_merchant_id_render',
        'tp-google-customer-reviews',
        'tp_gcr_section'
    );

    add_settings_field(
        'tp_gcr_language',
        __( 'Language', 'tp-gcr' ),
        'tp_gcr_language_render',
        'tp-google-customer-reviews',
        'tp_gcr_section'
    );

    add_settings_field(
        'tp_gcr_delivery_days',
        __( 'Estimated delivery time (days)', 'tp-gcr' ),
        'tp_gcr_delivery_days_render',
        'tp-google-customer-reviews',
        'tp_gcr_section'
    );
}
add_action( 'admin_init', 'tp_gcr_settings_init' );

// Field rendering functions
function tp_gcr_enabled_render() {
    $options = tp_gcr_get_options();
    echo "<input type='checkbox' name='tp_gcr_enabled' value='1' " . checked( $options['enabled'], 1, false ) . " />";
}

function tp_gcr_merchant_id_render() {
    $options = tp_gcr_get_options();
    echo "<input type='text' name='tp_gcr_merchant_id' value='" . esc_attr( $options['merchant_id'] ) . "' />";
}

function tp_gcr_language_render() {
    $options = tp_gcr_get_options();
    echo "<input type='text' name='tp_gcr_language' value='" . esc_attr( $options['language'] ) . "' />";
}

function tp_gcr_delivery_days_render() {
    $options = tp_gcr_get_options();
    echo "<input type='number' name='tp_gcr_delivery_days' value='" . esc_attr( $options['delivery_days'] ) . "' min='1' />";
}

// Settings page
function tp_gcr_options_page() {
    ?>
    <form action='options.php' method='post'>
        <h2><?php esc_html_e( 'TP Google Customer Reviews for WooCommerce', 'tp-gcr' ); ?></h2>
        <?php
        settings_fields( 'tp_gcr_settings' );
        do_settings_sections( 'tp-google-customer-reviews' );
        submit_button();
        ?>
    </form>
    <?php
}

// Opt-in form language settings
function tp_google_customer_reviews_language() {
    $options = tp_gcr_get_options();
    if ( ! $options['enabled'] ) {
        return;
    }
    wp_enqueue_script(
        'tp-gcr-lang',
        plugin_dir_url( __FILE__ ) . 'assets/js/tp-gcr.js',
        [],
        '1.0',
        true
    );
    wp_localize_script( 'tp-gcr-lang', 'tpGcrConfig', [ 'lang' => $options['language'] ] );
}
add_action( 'wp_enqueue_scripts', 'tp_google_customer_reviews_language', 20 );

// Add Google Customer Reviews opt-in form to WooCommerce page
function tp_google_customer_reviews_optin( $order_id ) {
    $options = tp_gcr_get_options();
    if ( ! $options['enabled'] ) {
        return;
    }
    if ( ! function_exists( 'wc_get_order' ) || ! function_exists( 'wc_get_product' ) ) {
        return;
    }

    $order = wc_get_order( $order_id );
    if ( ! $order || ! $order->has_status( [ 'completed', 'processing' ] ) ) {
        return;
    }

    $merchant_id   = $options['merchant_id'];
    $delivery_days = $options['delivery_days'];
    if ( empty( $merchant_id ) ) {
        return;
    }

    $products = [];

    foreach ( $order->get_items() as $item ) {
        $product = wc_get_product( $item->get_product_id() );
        if ( $product ) {
            $gtin = $product->get_meta('_gtin');
            if ( $gtin ) {
                $products[] = [ 'gtin' => $gtin ];
            }
        }
    }

    $delivery_date = $order->get_date_created();
    if ( $delivery_date instanceof WC_DateTime ) {
        $delivery_date->modify( '+' . $delivery_days . ' days' );
        $delivery_date = $delivery_date->date_i18n( 'Y-m-d' );
    } else {
        $delivery_date = date( 'Y-m-d', strtotime( '+' . $delivery_days . ' days' ) );
    }

    ?>
    <script src="https://apis.google.com/js/platform.js?onload=renderOptIn" async defer></script>
    <script>
        function renderOptIn() {
            window.gapi.load('surveyoptin', function() {
                window.gapi.surveyoptin.render({
                    "merchant_id": "<?php echo esc_js( $merchant_id ); ?>",
                    "order_id": "<?php echo esc_js( $order->get_order_number() ); ?>",
                    "email": "<?php echo esc_js( $order->get_billing_email() ); ?>",
                    "delivery_country": "<?php echo esc_js( $order->get_billing_country() ); ?>",
                    "estimated_delivery_date": "<?php echo esc_js( $delivery_date ); ?>",
                    "opt_in_style": "CENTER_DIALOG",
                    <?php if ( ! empty( $products ) ) : ?>
                    "products": <?php echo wp_json_encode( $products ); ?>
                    <?php endif; ?>
                });
            });
        }
    </script>
    <?php
}

if ( function_exists( 'wc_get_order' ) ) {
    add_action( 'woocommerce_thankyou', 'tp_google_customer_reviews_optin' );
}

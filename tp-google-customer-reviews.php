<?php
/*
 * Plugin Name: TP Google Customer Reviews dla WooCommerce
 * Description: Integracja Google Customer Reviews z WooCommerce, zbierająca opinie klientów po zakupie, z możliwością zarządzania Merchant ID, językiem i czasem dostawy.
 * Version: 1.3.0
 * Author: TopPosition.eu
 * Author URI: https://www.topposition.eu/
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0
 * Text Domain: tp-gcr
 */

// Ładowanie tłumaczeń wtyczki
function tp_gcr_load_textdomain() {
    load_plugin_textdomain( 'tp-gcr', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'tp_gcr_load_textdomain' );

// Pobieranie ustawień wtyczki z minimalizacją wywołań get_option
function tp_gcr_get_options() {
    static $options = null;
    if ( null === $options ) {
        $options = [
            'merchant_id'   => get_option( 'tp_gcr_merchant_id', '' ),
            'language'      => get_option( 'tp_gcr_language', 'pl' ),
            'delivery_days' => (int) get_option( 'tp_gcr_delivery_days', 3 ),
        ];
    }
    return $options;
}

// Dodanie menu ustawień wtyczki
function tp_gcr_add_admin_menu() {
    add_options_page(
        'TP Google Customer Reviews',
        'TP Google Customer Reviews',
        'manage_options',
        'tp-google-customer-reviews',
        'tp_gcr_options_page'
    );
}
add_action( 'admin_menu', 'tp_gcr_add_admin_menu' );

// Funkcje sanitizujące
function tp_gcr_sanitize_merchant_id( $value ) {
    return absint( $value );
}

function tp_gcr_sanitize_language( $value ) {
    return sanitize_text_field( $value );
}

function tp_gcr_sanitize_delivery_days( $value ) {
    return absint( $value );
}

// Rejestracja ustawień
function tp_gcr_settings_init() {
    register_setting( 'tp_gcr_settings', 'tp_gcr_merchant_id', 'tp_gcr_sanitize_merchant_id' );
    register_setting( 'tp_gcr_settings', 'tp_gcr_language', 'tp_gcr_sanitize_language' );
    register_setting( 'tp_gcr_settings', 'tp_gcr_delivery_days', 'tp_gcr_sanitize_delivery_days' );

    add_settings_section(
        'tp_gcr_section',
        __( 'Ustawienia Google Customer Reviews', 'tp-gcr' ),
        null,
        'tp-google-customer-reviews'
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
        __( 'Język', 'tp-gcr' ),
        'tp_gcr_language_render',
        'tp-google-customer-reviews',
        'tp_gcr_section'
    );

    add_settings_field(
        'tp_gcr_delivery_days',
        __( 'Przewidywany czas dostawy (dni)', 'tp-gcr' ),
        'tp_gcr_delivery_days_render',
        'tp-google-customer-reviews',
        'tp_gcr_section'
    );
}
add_action( 'admin_init', 'tp_gcr_settings_init' );

// Funkcje renderujące pola
function tp_gcr_merchant_id_render() {
    $merchant_id = get_option( 'tp_gcr_merchant_id', '' );
    echo "<input type='text' name='tp_gcr_merchant_id' value='" . esc_attr( $merchant_id ) . "' />";
}

function tp_gcr_language_render() {
    $language = get_option( 'tp_gcr_language', 'pl' );
    echo "<input type='text' name='tp_gcr_language' value='" . esc_attr( $language ) . "' />";
}

function tp_gcr_delivery_days_render() {
    $delivery_days = get_option( 'tp_gcr_delivery_days', 3 );
    echo "<input type='number' name='tp_gcr_delivery_days' value='" . esc_attr( $delivery_days ) . "' min='1' />";
}

// Strona ustawień
function tp_gcr_options_page() {
    ?>
    <form action='options.php' method='post'>
        <h2>TP Google Customer Reviews dla WooCommerce</h2>
        <?php
        settings_fields( 'tp_gcr_settings' );
        do_settings_sections( 'tp-google-customer-reviews' );
        submit_button();
        ?>
    </form>
    <?php
}

// Ustawienia języka formularza opt-in
function tp_google_customer_reviews_language() {
    $options = tp_gcr_get_options();
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

// Dodanie formularza opt-in Google Customer Reviews do strony WooCommerce
function tp_google_customer_reviews_optin( $order_id ) {
    if ( ! function_exists( 'wc_get_order' ) || ! function_exists( 'wc_get_product' ) ) {
        return;
    }

    $order = wc_get_order( $order_id );
    if ( ! $order || ! $order->has_status( [ 'completed', 'processing' ] ) ) {
        return;
    }

    $options = tp_gcr_get_options();
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

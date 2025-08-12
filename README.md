# TP Google Customer Reviews for WooCommerce

## English

### Overview
TP Google Customer Reviews integrates Google Customer Reviews with WooCommerce. It displays an opt-in widget after checkout to collect customer feedback and can show a rating badge on your site.

### Requirements
- WordPress 5.0 or higher
- PHP 7.0 or higher
- WooCommerce
- Google Merchant ID

### Options
- **Enable Google Customer Reviews** – toggle the integration on or off.
- **Google Merchant ID** – identifier used to link your shop with Google.
- **Language** – language code for the opt-in widget.
- **Estimated delivery time (days)** – number of days after purchase before the review invitation is sent.
- **Display rating badge** – show a Google rating badge on your pages.
- **Show badge only on shop pages** – limit the badge to WooCommerce areas.
- **Badge position** – choose `bottom_left` or `bottom_right`.
- **Opt-in style** – choose `CENTER_DIALOG`, `BOTTOM_LEFT_DIALOG`, or `BOTTOM_RIGHT_DIALOG`.

### Installation
1. Upload the plugin to `/wp-content/plugins` or install it via the WordPress plugin screen.
2. Activate the plugin through the **Plugins** menu.
3. Go to **Settings → TP Google Customer Reviews** and configure the options.

### Usage
After configuration, the plugin adds the Google Customer Reviews opt-in script to completed orders, sends review invitations after the estimated delivery time, and optionally displays the rating badge.

### Filters
Use these filters to customize how GTIN values are retrieved from products.

- `tpgcr_gtin_meta_key` – change the meta key used to fetch the GTIN (default `_gtin`).
- `tpgcr_gtin_value` – modify the GTIN value before it is sent to Google.

```php
add_filter( 'tpgcr_gtin_meta_key', function ( $meta_key, $product ) {
    return '_barcode';
}, 10, 2 );

add_filter( 'tpgcr_gtin_value', function ( $gtin, $product ) {
    return trim( $gtin );
}, 10, 2 );
```

---

## Polski

### Przegląd
TP Google Customer Reviews integruje Google Customer Reviews z WooCommerce. Wyświetla okno zgody po dokonaniu zakupu w celu zbierania opinii klientów i może pokazywać odznakę ocen na stronie.

### Wymagania
- WordPress 5.0 lub nowszy
- PHP 7.0 lub nowszy
- WooCommerce
- Google Merchant ID

### Opcje
- **Włącz Google Customer Reviews** – włącza lub wyłącza integrację.
- **Google Merchant ID** – identyfikator Twojego sklepu w Google.
- **Język** – kod języka używany przez okno zgody.
- **Szacowany czas dostawy (dni)** – liczba dni przed wysłaniem zaproszenia do opinii.
- **Wyświetl odznakę ocen** – pokazuje odznakę Google na stronie.
- **Pokazuj odznakę tylko w sklepie** – ogranicza odznakę do stron WooCommerce.
- **Pozycja odznaki** – wybierz `bottom_left` lub `bottom_right`.
- **Styl okna zgody** – wybierz `CENTER_DIALOG`, `BOTTOM_LEFT_DIALOG` lub `BOTTOM_RIGHT_DIALOG`.

### Instalacja
1. Wgraj wtyczkę do `/wp-content/plugins` lub zainstaluj ją z poziomu ekranu wtyczek WordPressa.
2. Aktywuj wtyczkę w menu **Wtyczki**.
3. Przejdź do **Ustawienia → TP Google Customer Reviews** i skonfiguruj opcje.

### Użycie
Po konfiguracji wtyczka dodaje skrypt Google Customer Reviews do zamówień, wysyła zaproszenia do opinii po upływie szacowanego czasu dostawy oraz opcjonalnie wyświetla odznakę ocen.

### Filtry
Wtyczka udostępnia filtry pozwalające dostosować sposób pobierania wartości GTIN z produktu.

- `tpgcr_gtin_meta_key` – zmienia klucz meta używany do pobierania GTIN (domyślnie `_gtin`).
- `tpgcr_gtin_value` – modyfikuje wartość GTIN przed wysłaniem do Google.

```php
add_filter( 'tpgcr_gtin_meta_key', function ( $meta_key, $product ) {
    return '_barcode';
}, 10, 2 );

add_filter( 'tpgcr_gtin_value', function ( $gtin, $product ) {
    return trim( $gtin );
}, 10, 2 );
```


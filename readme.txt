=== WebDokan Device Compatibility & Fit for WooCommerce ===
Contributors: webdokan
Tags: woocommerce, device compatibility, phone accessories, charger compatibility, phone cases
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

100% accurate, certified smartphone hardware compatibility and fit scoring for chargers, cases, and gadget accessories on WooCommerce.

== Description ==

**WebDokan Device Compatibility & Fit for WooCommerce** provides an interactive hardware compatibility scoring widget on single product pages.

Customers can select their smartphone brand and model to immediately see if a charger, cable, case, or accessory is guaranteed to fit, what charging speed is supported, and safety verification.

= Key Features =
* **100% Certified Lab Accuracy:** Bind products to a certified WebDokan Product ID (`WDP90950`).
* **Official Split-Pill Status Badges:** Display certified match tiers (`BEST MATCH`, `HIGHLY COMPATIBLE`, `FUNCTIONAL MATCH`, `INCOMPATIBLE`).
* **Real-time API Analytics Dashboard:** Monitor buyer engagement, total device lookups, and top queried smartphone models directly in WP-Admin.
* **Direct Breakdown Link Toggle:** Allow customers to click through to deep technical test results on WebDokan or view inline summaries.
* **Zero Bloat:** Only products with a configured WDP ID render the widget. Zero extra load on products without it.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/webdokan-device-compatibility` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Edit any WooCommerce product under **Product Data -> Inventory**, enter the WebDokan Product ID (e.g. `WDP90950`), and click **Verify WDP ID**.
4. Save the product. The compatibility widget will now render on the product page.

== Frequently Asked Questions ==

= Where do I find my product's WDP ID? =
You can search or view your product on [WebDokan](https://webdokan.com) to find its certified `WDP90950` code.

= Can I use a shortcode? =
Yes! Use `[webdokan_compatibility]` on any product page or custom layout.

== Changelog ==

= 1.0.0 =
* Initial official release. Certified hardware compatibility engine and analytics dashboard.

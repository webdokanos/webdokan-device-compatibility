# WebDokan Device Compatibility & Fit for WooCommerce

![WebDokan Device Compatibility Banner](assets/icon-256x256.jpg)

> **Official WordPress & WooCommerce Plugin** for verified smartphone compatibility, charging speed scoring, and fit verification for phone accessories.

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-Compatible-96588a.svg)](https://woocommerce.com)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

---

## ⚡ Overview

**WebDokan Device Compatibility & Fit for WooCommerce** provides an interactive hardware compatibility scoring widget on single product pages.

Customers can select their smartphone brand and model to immediately see if a charger, cable, case, or accessory is guaranteed to fit, what charging speed is supported, and safety verification.

---

## Key Features

* **100% Certified Lab Accuracy:** Bind products to a certified WebDokan Product ID (`WDP90950`) without fuzzy guessing.
* **Official Split-Pill Status Badges:** Display certified match tiers (`BEST MATCH`, `HIGHLY COMPATIBLE`, `FUNCTIONAL MATCH`, `INCOMPATIBLE`).
* **Real-time API Analytics Dashboard:** Monitor buyer engagement, total device lookups, and top queried smartphone models directly in WP-Admin.
* **Direct Breakdown Link Toggle:** Allow customers to click through to deep technical test results on WebDokan or view inline summaries.
* **Zero Bloat & SEO Safe:** Only products with a configured WDP ID render the widget. Zero extra load on products without it.

---

## 🚀 Installation

1. Download the latest `webdokan-device-compatibility.zip` from [Releases](https://github.com/webdokanos/webdokan-device-compatibility/releases) or the [WebDokan Developer Hub](https://webdokan.com/developer?tab=woocommerce).
2. In your WordPress admin, go to **Plugins &rarr; Add New &rarr; Upload Plugin**.
3. Select the `.zip` file and click **Install Now**, then **Activate**.
4. Go to **WooCommerce &rarr; WebDokan** to enter your API key and customize settings.
5. On any product edit page under **Product Data &rarr; Inventory**, enter the certified **WDP ID** (e.g. `WDP90950`) and click **Verify WDP ID**.

---

## 💻 Shortcode Usage

To display the compatibility widget anywhere in custom Elementor, Divi, or Gutenberg templates:

```php
[webdokan_compatibility wdp_id="WDP90950"]
```

---

## 📄 License

This project is licensed under the **GNU General Public License v2.0 or later** - see the [LICENSE](LICENSE) file for details.

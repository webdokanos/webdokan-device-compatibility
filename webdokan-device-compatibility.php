<?php
/**
 * Plugin Name: WebDokan Device Compatibility & Fit for WooCommerce
 * Plugin URI: https://webdokan.com/docs
 * Description: 100% verified hardware compatibility and fit scoring for smartphone chargers, cases, and accessories on WooCommerce. Powered by WebDokan Hardware Intelligence Cloud API.
 * Version: 1.0.0
 * Author: WebDokan
 * Author URI: https://webdokan.com
 * License: GPL-2.0+
 * Text Domain: webdokan-device-compatibility-fit-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('WEBDOKAN_COMPAT_VERSION', '1.0.0');
define('WEBDOKAN_COMPAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WEBDOKAN_COMPAT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WEBDOKAN_DEFAULT_API_URL', 'https://webdokan.com');

class WebDokan_Device_Compatibility {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    private function includes() {
        require_once WEBDOKAN_COMPAT_PLUGIN_DIR . 'includes/class-webdokan-admin.php';
        require_once WEBDOKAN_COMPAT_PLUGIN_DIR . 'includes/class-webdokan-frontend.php';
    }

    private function init_hooks() {
        add_action('init', array($this, 'load_textdomain'));

        // Declare HPOS compatibility (WooCommerce High-Performance Order Storage)
        add_action('before_woocommerce_init', function() {
            if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            }
        });

        // Initialize Admin and Frontend modules
        if (is_admin()) {
            WebDokan_Compat_Admin::get_instance();
        }
        WebDokan_Compat_Frontend::get_instance();
    }

    public function load_textdomain() {
        load_plugin_textdomain('webdokan-device-compatibility-fit-for-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}

// Bootstrap plugin
function run_webdokan_device_compatibility() {
    return WebDokan_Device_Compatibility::get_instance();
}
add_action('plugins_loaded', 'run_webdokan_device_compatibility');

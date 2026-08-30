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
 * Tested up to: 6.7
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!defined('WEBDOKAN_COMPAT_VERSION')) {
    define('WEBDOKAN_COMPAT_VERSION', '1.0.0');
}
if (!defined('WEBDOKAN_COMPAT_PLUGIN_FILE')) {
    define('WEBDOKAN_COMPAT_PLUGIN_FILE', __FILE__);
}
if (!defined('WEBDOKAN_COMPAT_PLUGIN_DIR')) {
    define('WEBDOKAN_COMPAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('WEBDOKAN_COMPAT_PLUGIN_URL')) {
    define('WEBDOKAN_COMPAT_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('WEBDOKAN_DEFAULT_API_URL')) {
    define('WEBDOKAN_DEFAULT_API_URL', 'https://webdokan.com');
}

// Declare HPOS compatibility (WooCommerce High-Performance Order Storage) early
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', WEBDOKAN_COMPAT_PLUGIN_FILE, true);
    }
});

if (!class_exists('WebDokan_Device_Compatibility')) {

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

            // Check if WooCommerce is active before initializing modules
            if (!$this->is_woocommerce_active()) {
                add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
                return;
            }

            // Initialize Admin and Frontend modules
            if (is_admin()) {
                WebDokan_Compat_Admin::get_instance();
            }
            WebDokan_Compat_Frontend::get_instance();
        }

        public function is_woocommerce_active() {
            return class_exists('WooCommerce') || in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins', array())));
        }

        public function woocommerce_missing_notice() {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php esc_html_e('WebDokan Device Compatibility & Fit', 'webdokan-device-compatibility-fit-for-woocommerce'); ?>:</strong>
                    <?php esc_html_e('WooCommerce is required for this plugin to work. Please install and activate WooCommerce.', 'webdokan-device-compatibility-fit-for-woocommerce'); ?>
                </p>
            </div>
            <?php
        }

        public function load_textdomain() {
            load_plugin_textdomain('webdokan-device-compatibility-fit-for-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages');
        }
    }
}

// Bootstrap plugin safely
if (!function_exists('run_webdokan_device_compatibility')) {
    function run_webdokan_device_compatibility() {
        return WebDokan_Device_Compatibility::get_instance();
    }
    add_action('plugins_loaded', 'run_webdokan_device_compatibility');
}

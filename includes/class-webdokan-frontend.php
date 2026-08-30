<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WebDokan_Compat_Frontend')) {

    class WebDokan_Compat_Frontend {

        private static $instance = null;

        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function __construct() {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));

            // Hook based on admin position setting
            $position = get_option('webdokan_widget_position', 'woocommerce_before_add_to_cart_button');
            if ('shortcode_only' !== $position) {
                add_action($position, array($this, 'render_product_widget'), 25);
            }

            // Shortcode support
            add_shortcode('webdokan_compatibility', array($this, 'shortcode_widget'));

            // Public AJAX for ultra-fast local device search
            add_action('wp_ajax_webdokan_search_devices', array($this, 'ajax_search_devices'));
            add_action('wp_ajax_nopriv_webdokan_search_devices', array($this, 'ajax_search_devices'));
        }

        public function ajax_search_devices() {
            $q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
            $q_lower = strtolower(trim($q));

            $synced_devices = get_option('webdokan_synced_devices', array());

            if (!empty($synced_devices) && is_array($synced_devices)) {
                $results = array();
                foreach ($synced_devices as $d) {
                    $brand = strtolower($d['brand'] ?? '');
                    $model = strtolower($d['model'] ?? '');
                    $marketingName = strtolower($d['marketingName'] ?? '');
                    $name = strtolower($d['name'] ?? '');
                    $wddId = strtolower($d['wddId'] ?? '');

                    if (empty($q_lower) ||
                        strpos($brand, $q_lower) !== false ||
                        strpos($model, $q_lower) !== false ||
                        strpos($marketingName, $q_lower) !== false ||
                        strpos($name, $q_lower) !== false ||
                        strpos($wddId, $q_lower) !== false) {
                        $results[] = $d;
                        if (count($results) >= 15) {
                            break;
                        }
                    }
                }

                wp_send_json(array(
                    'success' => true,
                    'source'  => 'local',
                    'devices' => $results
                ));
            }

            // Fallback to Cloud API if local sync not yet performed
            $api_url = get_option('webdokan_api_url', WEBDOKAN_DEFAULT_API_URL);
            $api_key = class_exists('WebDokan_Compat_Admin') ? WebDokan_Compat_Admin::normalize_api_key(get_option('webdokan_api_key', '')) : get_option('webdokan_api_key', '');
            $cloud_url = rtrim($api_url, '/') . '/api/v1/compatibility/search-devices?q=' . urlencode($q) . '&api_key=' . urlencode($api_key);
            
            $response = wp_remote_get($cloud_url, array('timeout' => 5));
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                wp_send_json($body);
            }

            wp_send_json(array('success' => true, 'devices' => array()));
        }

        public function enqueue_frontend_assets() {
            $is_product_page = (function_exists('is_product') && is_product()) || is_singular('product');

            if ($is_product_page || is_singular()) {
                global $post;
                $wdp_id = ($post && isset($post->ID)) ? get_post_meta($post->ID, '_webdokan_wdp_id', true) : '';

                // Only enqueue if product has active WDP ID
                if (!empty($wdp_id)) {
                    wp_enqueue_style(
                        'webdokan-widget-css',
                        WEBDOKAN_COMPAT_PLUGIN_URL . 'assets/css/webdokan-widget.css',
                        array(),
                        WEBDOKAN_COMPAT_VERSION
                    );

                    wp_enqueue_script(
                        'webdokan-widget-js',
                        WEBDOKAN_COMPAT_PLUGIN_URL . 'assets/js/webdokan-widget.js',
                        array(),
                        WEBDOKAN_COMPAT_VERSION,
                        true
                    );

                    $api_url = get_option('webdokan_api_url', WEBDOKAN_DEFAULT_API_URL);
                    if (empty($api_url)) {
                        $api_url = WEBDOKAN_DEFAULT_API_URL;
                    }
                    $site_domain = parse_url(home_url(), PHP_URL_HOST);
                    if (empty($site_domain)) {
                        $site_domain = $_SERVER['HTTP_HOST'] ?? 'unknown';
                    }

                    wp_localize_script('webdokan-widget-js', 'webdokanData', array(
                        'apiUrl'       => rtrim($api_url, '/'),
                        'ajaxUrl'      => admin_url('admin-ajax.php'),
                        'apiKey'       => class_exists('WebDokan_Compat_Admin') ? WebDokan_Compat_Admin::normalize_api_key(get_option('webdokan_api_key', '')) : get_option('webdokan_api_key', ''),
                        'linkToDetail' => get_option('webdokan_link_to_detail', 'yes') === 'yes',
                        'siteDomain'   => $site_domain
                    ));
                }
            }
        }

        public function shortcode_widget($atts) {
            $atts = shortcode_atts(array(
                'wdp_id' => ''
            ), $atts, 'webdokan_compatibility');

            global $post;
            $wdp_id = !empty($atts['wdp_id']) ? strtoupper(trim($atts['wdp_id'])) : (($post && isset($post->ID)) ? get_post_meta($post->ID, '_webdokan_wdp_id', true) : '');

            if (empty($wdp_id)) {
                return '';
            }

            ob_start();
            $this->output_widget_html($wdp_id);
            return ob_get_clean();
        }

        public function render_product_widget() {
            global $product;
            $product_id = 0;

            if (is_a($product, 'WC_Product')) {
                $product_id = $product->get_id();
            } elseif (is_numeric($product)) {
                $product_id = (int)$product;
            } else {
                $product_id = get_the_ID();
            }

            if (!$product_id) {
                return;
            }

            $wdp_id = get_post_meta($product_id, '_webdokan_wdp_id', true);
            if (empty($wdp_id)) {
                return; // Strict rule: No badge rendered if no verified WDP ID exists
            }

            $this->output_widget_html($wdp_id);
        }

        private function output_widget_html($wdp_id) {
            $default_wdd = 'WDD2388'; // Certified default flagship device ID (Apple iPhone 15 Pro)
            ?>
            <div class="webdokan-compat-container" 
                 data-wdp-id="<?php echo esc_attr($wdp_id); ?>"
                 data-default-wdd="<?php echo esc_attr($default_wdd); ?>"
                 style="max-width: 540px; margin: 18px auto; width: 100%; box-sizing: border-box;">
                
                <!-- Single Sleek Search Input with Embedded Change Button -->
                <div class="webdokan-search-container" style="position: relative; width: 100%; margin-bottom: 8px;">
                    <div class="webdokan-search-input-wrapper" style="display: flex; align-items: center; width: 100%; background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 9999px; padding: 4px 6px 4px 14px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04); box-sizing: border-box;">
                        <span class="webdokan-search-prefix-icon" style="font-size: 14px; margin-right: 6px; flex-shrink: 0; line-height: 1;">📱</span>
                        <input type="text" 
                               class="webdokan-device-search-input" 
                               value="Apple iPhone 15 Pro"
                               data-selected-name="Apple iPhone 15 Pro"
                               placeholder="Search your phone model (e.g. Oppo, Galaxy, iPhone)..." 
                               autocomplete="off" 
                               aria-label="Search phone model for compatibility score"
                               style="flex: 1; border: none !important; outline: none !important; background: transparent !important; font-size: 13px !important; font-weight: 700 !important; color: #0f172a !important; height: 36px !important; padding: 0 4px !important; margin: 0 !important; width: 100% !important; box-shadow: none !important;" />
                        <button type="button" class="webdokan-search-action-btn" title="Change device model"
                                style="background: #0f172a !important; color: #ffffff !important; border: none !important; border-radius: 9999px !important; padding: 6px 14px !important; font-size: 11px !important; font-weight: 700 !important; cursor: pointer !important; flex-shrink: 0 !important; line-height: 1.4 !important;">
                            Change
                        </button>
                    </div>
                    <!-- Autocomplete Suggestions Dropdown -->
                    <div class="webdokan-suggestions-list" style="display: none;"></div>
                </div>

                <!-- Score Iframe Container -->
                <div class="webdokan-iframe-wrapper" style="position: relative; width: 100%; border-radius: 24px; overflow: hidden; background: #ffffff;">
                    <iframe class="webdokan-score-iframe"
                            src="<?php echo esc_url('https://webdokan.com/' . $wdp_id . '/' . $default_wdd . '/score?embed=1'); ?>" 
                            width="100%" 
                            height="580" 
                            frameborder="0" 
                            style="border-radius:24px; max-width:540px; border:none; display:block; margin: 0 auto; background:#ffffff; box-shadow: 0 4px 20px rgba(0,0,0,0.04);"
                            loading="lazy"
                            allowtransparency="true"
                            title="WebDokan Verified Hardware Compatibility Score">
                    </iframe>
                </div>
            </div>
            <?php
        }
    }
}

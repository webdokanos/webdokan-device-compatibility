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

                    $synced_devices = get_option('webdokan_synced_devices', array());

                    wp_localize_script('webdokan-widget-js', 'webdokanData', array(
                        'apiUrl'         => rtrim($api_url, '/'),
                        'apiKey'         => class_exists('WebDokan_Compat_Admin') ? WebDokan_Compat_Admin::normalize_api_key(get_option('webdokan_api_key', '')) : get_option('webdokan_api_key', ''),
                        'linkToDetail'   => get_option('webdokan_link_to_detail', 'yes') === 'yes',
                        'siteDomain'     => $site_domain,
                        'syncedDevices'  => !empty($synced_devices) ? $synced_devices : array()
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
            $default_wdd = 'WDD833335'; // Fallback default flagship device ID
            ?>
            <div class="webdokan-compat-container" 
                 data-wdp-id="<?php echo esc_attr($wdp_id); ?>"
                 data-default-wdd="<?php echo esc_attr($default_wdd); ?>">
                
                <!-- Single Sleek Search Input with Embedded Change Button -->
                <div class="webdokan-search-container">
                    <div class="webdokan-search-input-wrapper">
                        <span class="webdokan-search-prefix-icon">📱</span>
                        <input type="text" 
                               class="webdokan-device-search-input" 
                               value="Apple iPhone 15 Pro"
                               data-selected-name="Apple iPhone 15 Pro"
                               placeholder="Search your phone model..." 
                               autocomplete="off" 
                               aria-label="Search phone model for compatibility score" />
                        <button type="button" class="webdokan-search-action-btn" title="Change device model">
                            Change
                        </button>
                    </div>
                    <!-- Autocomplete Suggestions Dropdown -->
                    <div class="webdokan-suggestions-list" style="display: none;"></div>
                </div>

                <!-- Score Iframe Container -->
                <div class="webdokan-iframe-wrapper">
                    <iframe class="webdokan-score-iframe"
                            src="<?php echo esc_url('https://webdokan.com/' . $wdp_id . '/' . $default_wdd . '/score?embed=1'); ?>" 
                            width="100%" 
                            height="580" 
                            frameborder="0" 
                            style="border-radius:24px; max-width:540px; border:none; display:block; margin: 10px auto 0 auto; background:#ffffff; box-shadow: 0 4px 20px rgba(0,0,0,0.04);"
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

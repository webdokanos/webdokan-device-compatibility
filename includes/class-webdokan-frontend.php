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
            $link_to_detail = get_option('webdokan_link_to_detail', 'yes') === 'yes';
            $theme_mode = get_option('webdokan_theme_mode', 'auto');
            $default_wdd = 'WDD833335'; // Fallback default flagship device ID
            ?>
            <div class="webdokan-compat-container webdokan-theme-<?php echo esc_attr($theme_mode); ?>" 
                 data-wdp-id="<?php echo esc_attr($wdp_id); ?>"
                 data-default-wdd="<?php echo esc_attr($default_wdd); ?>"
                 data-link-enabled="<?php echo $link_to_detail ? '1' : '0'; ?>">
                
                <!-- Lightweight Header -->
                <div class="webdokan-compat-header">
                    <div class="webdokan-compat-title">
                        <span class="webdokan-shield-icon">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </span>
                        <span>Hardware Compatibility Lab</span>
                    </div>
                    <span class="webdokan-cert-tag">Verified • <?php echo esc_html($wdp_id); ?></span>
                </div>

                <!-- Clean Device Selection Bar with Quick Chips -->
                <div class="webdokan-device-selector-card">
                    <div class="webdokan-active-device-row">
                        <div class="webdokan-active-device-info">
                            <span class="webdokan-pulse-dot"></span>
                            <span class="webdokan-active-device-title">Device:</span>
                            <span class="webdokan-current-device-name">Apple iPhone 15 Pro</span>
                        </div>
                        <button type="button" class="webdokan-toggle-search-btn" title="Search another phone model">
                            🔍 Change Model
                        </button>
                    </div>

                    <!-- Popular Quick Chips for 1-Click Switch -->
                    <div class="webdokan-quick-chips-row">
                        <span class="webdokan-chip-label">Quick Pick:</span>
                        <button type="button" class="webdokan-quick-chip active" data-wdd-id="WDD833335" data-name="Apple iPhone 15 Pro">iPhone 15 Pro</button>
                        <button type="button" class="webdokan-quick-chip" data-wdd-id="WDD833336" data-name="Samsung Galaxy S24 Ultra">Galaxy S24</button>
                        <button type="button" class="webdokan-quick-chip" data-wdd-id="WDD833337" data-name="Google Pixel 8 Pro">Pixel 8</button>
                        <button type="button" class="webdokan-quick-chip" data-wdd-id="WDD833338" data-name="Xiaomi Redmi Note 13 Pro">Redmi Note 13</button>
                    </div>

                    <!-- Expandable Autocomplete Search Bar -->
                    <div class="webdokan-search-expand-wrap" style="display: none;">
                        <div class="webdokan-search-input-box">
                            <span class="webdokan-search-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"/>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                </svg>
                            </span>
                            <input type="text" 
                                   class="webdokan-device-search-input" 
                                   placeholder="Type phone name or model (e.g. iPhone 14, S23, Xiaomi)..." 
                                   autocomplete="off" 
                                   aria-label="Search phone model for compatibility score" />
                            <button type="button" class="webdokan-search-clear-btn" title="Close search">✕</button>
                        </div>
                        <div class="webdokan-suggestions-list" style="display: none;"></div>
                    </div>
                </div>

                <!-- Score Iframe Container -->
                <div class="webdokan-iframe-wrapper">
                    <iframe class="webdokan-score-iframe"
                            src="<?php echo esc_url('https://webdokan.com/' . $wdp_id . '/' . $default_wdd . '/score?embed=1'); ?>" 
                            width="100%" 
                            height="580" 
                            frameborder="0" 
                            style="border-radius:22px; max-width:540px; border:none; display:block; margin:auto; background:#ffffff; box-shadow: 0 4px 18px rgba(0,0,0,0.03);"
                            loading="lazy"
                            allowtransparency="true"
                            title="WebDokan Verified Hardware Compatibility Score">
                    </iframe>
                </div>

                <!-- Footer Attribution -->
                <div class="webdokan-compat-footer">
                    <a href="<?php echo esc_url('https://webdokan.com/products/' . strtolower($wdp_id)); ?>" target="_blank" rel="noopener nofollow" class="webdokan-footer-link">
                        <span class="webdokan-mini-shield">🛡️</span> Powered by WebDokan Certified Hardware Intelligence Engine
                    </a>
                </div>
            </div>
            <?php
        }
    }
}

<?php
if (!defined('ABSPATH')) {
    exit;
}

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
        if (is_product() || is_singular()) {
            global $post;
            $wdp_id = $post ? get_post_meta($post->ID, '_webdokan_wdp_id', true) : '';

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

                wp_localize_script('webdokan-widget-js', 'webdokanData', array(
                    'apiUrl'       => rtrim(get_option('webdokan_api_url', WEBDOKAN_DEFAULT_API_URL), '/'),
                    'apiKey'       => get_option('webdokan_api_key', ''),
                    'linkToDetail' => get_option('webdokan_link_to_detail', 'yes') === 'yes',
                    'siteDomain'   => parse_url(home_url(), PHP_URL_HOST)
                ));
            }
        }
    }

    public function shortcode_widget($atts) {
        $atts = shortcode_atts(array(
            'wdp_id' => ''
        ), $atts, 'webdokan_compatibility');

        global $post;
        $wdp_id = !empty($atts['wdp_id']) ? strtoupper(trim($atts['wdp_id'])) : ($post ? get_post_meta($post->ID, '_webdokan_wdp_id', true) : '');

        if (empty($wdp_id)) {
            return '';
        }

        ob_start();
        $this->output_widget_html($wdp_id);
        return ob_get_clean();
    }

    public function render_product_widget() {
        global $product;
        if (!$product) return;

        $wdp_id = get_post_meta($product->get_id(), '_webdokan_wdp_id', true);
        if (empty($wdp_id)) {
            return; // Strict rule: No badge rendered if no verified WDP ID exists
        }

        $this->output_widget_html($wdp_id);
    }

    private function output_widget_html($wdp_id) {
        $link_to_detail = get_option('webdokan_link_to_detail', 'yes') === 'yes';
        $theme_mode = get_option('webdokan_theme_mode', 'auto');
        ?>
        <div class="webdokan-compat-container webdokan-theme-<?php echo esc_attr($theme_mode); ?>" 
             data-wdp-id="<?php echo esc_attr($wdp_id); ?>"
             data-link-enabled="<?php echo $link_to_detail ? '1' : '0'; ?>">
            
            <div class="webdokan-compat-header">
                <div class="webdokan-compat-title">
                    <span class="webdokan-shield-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </span>
                    <span>Check Compatibility with Your Device</span>
                </div>
                <span class="webdokan-cert-tag">Verified • <?php echo esc_html($wdp_id); ?></span>
            </div>

            <!-- Device Selectors -->
            <div class="webdokan-selectors-row">
                <div class="webdokan-select-wrap">
                    <select class="webdokan-brand-select" aria-label="Select Phone Brand">
                        <option value="">Select Brand...</option>
                    </select>
                </div>
                <div class="webdokan-select-wrap">
                    <select class="webdokan-model-select" aria-label="Select Phone Model" disabled>
                        <option value="">Select Model...</option>
                    </select>
                </div>
            </div>

            <!-- Dynamic Exact Split-Pill Score Capsule Badge (Initially Hidden until device is selected) -->
            <div class="webdokan-badge-container" style="display: none;">
                <div class="webdokan-badge-interactive-wrap">
                    <div class="webdokan-compat-badge-pill" data-type="functional">
                        <div class="webdokan-badge-score">
                            <svg class="webdokan-lightning-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                            </svg>
                            <span class="webdokan-score-num">--%</span>
                        </div>
                        <div class="webdokan-badge-label">
                            CHECKING...
                        </div>
                    </div>
                </div>

                <div class="webdokan-insight-box">
                    <p class="webdokan-insight-text"></p>
                </div>
            </div>

            <!-- Footer Attribution & Verified Engine Link -->
            <div class="webdokan-compat-footer">
                <a href="<?php echo esc_url('https://webdokan.com/products/' . strtolower($wdp_id)); ?>" target="_blank" rel="noopener nofollow" class="webdokan-footer-link">
                    <span class="webdokan-mini-shield">🛡️</span> Verified by WebDokan Hardware Intelligence Engine
                </a>
            </div>
        </div>
        <?php
    }
}

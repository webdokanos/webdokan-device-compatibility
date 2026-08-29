<?php
if (!defined('ABSPATH')) {
    exit;
}

class WebDokan_Compat_Admin {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('woocommerce_product_options_inventory_product_data', array($this, 'add_wdp_product_field'));
        add_action('woocommerce_process_product_meta', array($this, 'save_wdp_product_field'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_menu', array($this, 'add_admin_pages'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_webdokan_verify_wdp', array($this, 'ajax_verify_wdp'));
    }

    public function enqueue_admin_assets($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook || strpos($hook, 'webdokan') !== false) {
            wp_enqueue_style(
                'webdokan-admin-css',
                WEBDOKAN_COMPAT_PLUGIN_URL . 'assets/css/webdokan-widget.css',
                array(),
                WEBDOKAN_COMPAT_VERSION
            );
            wp_enqueue_script(
                'webdokan-admin-js',
                WEBDOKAN_COMPAT_PLUGIN_URL . 'assets/js/webdokan-admin.js',
                array('jquery'),
                WEBDOKAN_COMPAT_VERSION,
                true
            );
            wp_localize_script('webdokan-admin-js', 'webdokanAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('webdokan_admin_nonce'),
                'apiUrl'  => get_option('webdokan_api_url', WEBDOKAN_DEFAULT_API_URL),
                'apiKey'  => get_option('webdokan_api_key', '')
            ));
        }
    }

    public function add_wdp_product_field() {
        global $post;
        $wdp_id = get_post_meta($post->ID, '_webdokan_wdp_id', true);
        $api_key = get_option('webdokan_api_key', '');
        ?>
        <div class="options_group show_if_simple show_if_variable" style="border-top: 1px solid #e2e8f0; padding-top: 12px; margin-top: 12px;">
            <p class="form-field _webdokan_wdp_id_field">
                <label for="_webdokan_wdp_id">
                    <strong><?php esc_html_e('WebDokan Product ID (WDP ID)', 'webdokan-device-compatibility-fit-for-woocommerce'); ?></strong>
                </label>
                <span class="wrap" style="display: flex; gap: 8px; align-items: center;">
                    <input type="text" 
                           class="short" 
                           style="width: 180px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;" 
                           name="_webdokan_wdp_id" 
                           id="_webdokan_wdp_id" 
                           value="<?php echo esc_attr($wdp_id); ?>" 
                           placeholder="e.g. WDP90950" />
                    <button type="button" class="button button-secondary" id="webdokan-verify-btn">
                        <?php esc_html_e('Verify WDP ID', 'webdokan-device-compatibility-fit-for-woocommerce'); ?>
                    </button>
                </span>
                <span class="description" style="display: block; margin-top: 6px; font-size: 12px; color: #64748b;">
                    <?php if (empty($api_key)): ?>
                        <span style="color: #b91c1c; font-weight: 600;">⚠️ API Key required: Please enter your WebDokan API Key in <a href="<?php echo esc_url(admin_url('admin.php?page=webdokan')); ?>">WebDokan Hub</a> to activate verification.</span>
                    <?php else: ?>
                        <?php esc_html_e('Enter certified WebDokan ID (e.g. WDP90950) to render 100% verified compatibility badge on this product page. Leave blank to disable.', 'webdokan-device-compatibility-fit-for-woocommerce'); ?>
                    <?php endif; ?>
                </span>
            </p>
            <div id="webdokan-verify-result" style="margin: 8px 12px 16px 12px; display: none;"></div>
        </div>
        <?php
    }

    public function save_wdp_product_field($post_id) {
        if (isset($_POST['_webdokan_wdp_id'])) {
            $clean_id = sanitize_text_field($_POST['_webdokan_wdp_id']);
            if (!empty($clean_id)) {
                $clean_id = strtoupper(trim($clean_id));
            }
            update_post_meta($post_id, '_webdokan_wdp_id', $clean_id);
        }
    }

    public function ajax_verify_wdp() {
        check_ajax_referer('webdokan_admin_nonce', 'security');
        $wdp_id = sanitize_text_field($_POST['wdp_id'] ?? '');
        $api_key = get_option('webdokan_api_key', '');

        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'WebDokan API Key is missing. Please configure it in WebDokan Settings.'));
        }

        if (empty($wdp_id)) {
            wp_send_json_error(array('message' => 'Please enter a WDP ID (e.g. WDP90950)'));
        }

        $api_base = rtrim(get_option('webdokan_api_url', WEBDOKAN_DEFAULT_API_URL), '/');
        $url = $api_base . '/api/v1/compatibility/product-lookup?wdp_id=' . urlencode($wdp_id) . '&api_key=' . urlencode($api_key);

        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/json',
                'X-WebDokan-Key' => $api_key
            )
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Could not connect to WebDokan API: ' . $response->get_error_message()));
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($status_code === 403) {
            wp_send_json_error(array('message' => $data['error'] ?? 'Your store domain has been blocked by WebDokan administrator.'));
        }

        if (isset($data['verified']) && $data['verified']) {
            wp_send_json_success($data);
        } else {
            wp_send_json_error(array('message' => $data['error'] ?? 'WDP ID not found in certified catalog.'));
        }
    }

    public function add_admin_pages() {
        // Unified single page under WooCommerce named "WebDokan"
        add_submenu_page(
            'woocommerce',
            __('WebDokan Compatibility & Analytics', 'webdokan-device-compatibility-fit-for-woocommerce'),
            __('WebDokan', 'webdokan-device-compatibility-fit-for-woocommerce'),
            'manage_woocommerce',
            'webdokan',
            array($this, 'render_unified_page')
        );
    }

    public function register_settings() {
        register_setting('webdokan_settings_group', 'webdokan_api_key', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => ''
        ));
        register_setting('webdokan_settings_group', 'webdokan_api_url', array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => WEBDOKAN_DEFAULT_API_URL
        ));
        register_setting('webdokan_settings_group', 'webdokan_widget_position', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'woocommerce_before_add_to_cart_button'
        ));
        register_setting('webdokan_settings_group', 'webdokan_link_to_detail', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'yes'
        ));
        register_setting('webdokan_settings_group', 'webdokan_theme_mode', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'auto'
        ));
    }

    public function render_unified_page() {
        $api_key = get_option('webdokan_api_key', '');
        $api_url = get_option('webdokan_api_url', WEBDOKAN_DEFAULT_API_URL);
        $position = get_option('webdokan_widget_position', 'woocommerce_before_add_to_cart_button');
        $link_to_detail = get_option('webdokan_link_to_detail', 'yes');
        $theme_mode = get_option('webdokan_theme_mode', 'auto');
        $site_domain = parse_url(home_url(), PHP_URL_HOST);

        // Fetch live analytics if API key exists
        $analytics = array(
            'totalChecks' => 0,
            'todayChecks' => 0,
            'topDevices' => array(),
            'topProducts' => array(),
            'dailyTrend' => array()
        );

        $is_connected = false;
        $connection_error = '';

        if (!empty($api_key)) {
            $api_base = rtrim($api_url, '/');
            $response = wp_remote_get($api_base . '/api/v1/compatibility/analytics?domain=' . urlencode($site_domain) . '&api_key=' . urlencode($api_key), array(
                'timeout' => 8,
                'headers' => array('Accept' => 'application/json', 'X-WebDokan-Key' => $api_key)
            ));

            if (!is_wp_error($response)) {
                $status_code = wp_remote_retrieve_response_code($response);
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if ($status_code === 200 && !empty($body)) {
                    $analytics = wp_parse_args($body, $analytics);
                    $is_connected = true;
                } else if ($status_code === 403) {
                    $connection_error = $body['error'] ?? 'Your store domain has been blocked by WebDokan administrator.';
                } else {
                    $connection_error = $body['error'] ?? 'Invalid API Key or connection error.';
                }
            } else {
                $connection_error = 'Could not connect to WebDokan Cloud API: ' . $response->get_error_message();
            }
        }
        ?>
        <div class="wrap" style="max-width: 1050px;">
            
            <!-- Top Header Banner -->
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid #cbd5e1; padding-bottom: 18px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <img src="<?php echo esc_url(WEBDOKAN_COMPAT_PLUGIN_URL . 'assets/icon-128x128.jpg'); ?>" style="width: 52px; height: 52px; border-radius: 14px; border: 1px solid #cbd5e1; box-shadow: 0 2px 6px rgba(0,0,0,0.06);" alt="WebDokan Logo" />
                    <div>
                        <h1 style="margin: 0; font-size: 24px; font-weight: 900; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                            WebDokan Compatibility Hub
                        </h1>
                        <p style="margin: 4px 0 0 0; font-size: 13px; color: #64748b;">
                            Live Hardware Intelligence, Device Scoring & API Configuration for <strong><?php echo esc_html($site_domain); ?></strong>
                        </p>
                    </div>
                </div>

                <div>
                    <?php if ($is_connected): ?>
                        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 9999px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; font-size: 12px; font-weight: 800; letter-spacing: 0.3px;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span>
                            API Connected & Active
                        </span>
                    <?php elseif (!empty($connection_error)): ?>
                        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 9999px; background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; font-size: 12px; font-weight: 800;">
                            ⚠️ <?php echo esc_html($connection_error); ?>
                        </span>
                    <?php else: ?>
                        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 9999px; background: #fffbeb; border: 1px solid #fde68a; color: #92400e; font-size: 12px; font-weight: 800;">
                            ⚠️ Enter API Key to Connect
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SECTION 1: Analytics & Live Activity -->
            <div style="margin-bottom: 32px;">
                <h2 style="font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 14px; display: flex; align-items: center; gap: 6px;">
                    📊 Buyer Engagement & API Usage Analytics
                </h2>

                <!-- KPI Metric Cards -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px;">
                    <div style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">Total Compatibility Checks</span>
                        <div style="font-size: 30px; font-weight: 900; color: #0f172a; margin-top: 6px;"><?php echo number_format($analytics['totalChecks']); ?></div>
                    </div>
                    <div style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #059669;">Today's Checks</span>
                        <div style="font-size: 30px; font-weight: 900; color: #059669; margin-top: 6px;"><?php echo number_format($analytics['todayChecks']); ?></div>
                    </div>
                    <div style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #6366f1;">Hardware Specs Engine</span>
                        <div style="font-size: 16px; font-weight: 800; color: #6366f1; margin-top: 14px; display: flex; align-items: center; gap: 6px;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; display: inline-block;"></span> 100% Certified Lab Match
                        </div>
                    </div>
                </div>

                <!-- Usage Graph & Top Devices Grid -->
                <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px;">
                    <!-- Activity Graph -->
                    <div style="background: #fff; padding: 22px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <h3 style="margin: 0 0 14px 0; font-size: 14px; font-weight: 800; color: #0f172a;">API Activity Trend (Last 14 Days)</h3>
                        <?php
                        $trend = $analytics['dailyTrend'];
                        if (empty($trend)) {
                            ?>
                            <div style="text-align: center; padding: 40px 10px; color: #94a3b8; font-size: 12px;">
                                No checks recorded yet. Customer device lookups on single product pages will automatically populate this graph.
                            </div>
                            <?php
                        } else {
                            $maxCount = max(1, max(array_column($trend, 'count')));
                            ?>
                            <div style="display: flex; align-items: flex-end; gap: 10px; height: 140px; padding-top: 16px; border-bottom: 1px solid #e2e8f0;">
                                <?php foreach ($trend as $point): 
                                    $heightPct = round(($point['count'] / $maxCount) * 100);
                                    $dateLabel = date('M d', strtotime($point['date']));
                                ?>
                                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; height: 100%; justify-content: flex-end;">
                                        <span style="font-size: 9px; font-weight: 700; color: #059669;"><?php echo esc_html($point['count']); ?></span>
                                        <div style="width: 100%; max-width: 28px; background: linear-gradient(to top, #059669, #34d399); border-radius: 4px 4px 0 0; height: <?php echo max(8, $heightPct); ?>%;"></div>
                                        <span style="font-size: 8px; color: #64748b; margin-top: 3px; white-space: nowrap;"><?php echo esc_html($dateLabel); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php
                        }
                        ?>
                    </div>

                    <!-- Top Queried Devices -->
                    <div style="background: #fff; padding: 22px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <h3 style="margin: 0 0 14px 0; font-size: 14px; font-weight: 800; color: #0f172a;">Top Queried Phone Models</h3>
                        <?php if (empty($analytics['topDevices'])): ?>
                            <p style="color: #94a3b8; font-size: 12px; margin: 0; padding: 30px 0; text-align: center;">No device lookup history recorded yet.</p>
                        <?php else: ?>
                            <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                                <thead>
                                    <tr style="border-bottom: 1px solid #e2e8f0; text-align: left; color: #64748b; font-size: 10px; text-transform: uppercase;">
                                        <th style="padding: 6px 8px;">Model</th>
                                        <th style="padding: 6px 8px; text-align: right;">Checks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analytics['topDevices'] as $device): ?>
                                        <tr style="border-bottom: 1px solid #f1f5f9;">
                                            <td style="padding: 8px 8px; font-weight: 600; color: #0f172a;">📱 <?php echo esc_html($device['name']); ?></td>
                                            <td style="padding: 8px 8px; text-align: right; font-weight: 700; color: #059669;"><?php echo number_format($device['count']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Plugin Settings & Configuration -->
            <div style="margin-bottom: 32px;">
                <h2 style="font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 14px; display: flex; align-items: center; gap: 6px;">
                    ⚙️ Cloud Connection & Widget Settings
                </h2>

                <form method="post" action="options.php" style="background: #fff; padding: 26px 32px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <?php settings_fields('webdokan_settings_group'); ?>
                    
                    <table class="form-table" style="margin-top: 0;">
                        <tr>
                            <th scope="row"><label for="webdokan_api_key"><strong>WebDokan API Key <span style="color: #ef4444;">*</span></strong></label></th>
                            <td>
                                <input name="webdokan_api_key" type="password" id="webdokan_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" style="border-radius: 8px; font-family: monospace;" placeholder="wdk_live_..." required />
                                <p class="description">
                                    Required to authenticate requests for domain <code><?php echo esc_html($site_domain); ?></code>. Generate your key on <a href="https://webdokan.com" target="_blank">WebDokan Partner Portal</a>.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="webdokan_api_url"><strong>WebDokan Cloud API URL</strong></label></th>
                            <td>
                                <input name="webdokan_api_url" type="url" id="webdokan_api_url" value="<?php echo esc_attr($api_url); ?>" class="regular-text" style="border-radius: 8px;" />
                                <p class="description">Default: <code>https://webdokan.com</code></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="webdokan_widget_position"><strong>Widget Position</strong></label></th>
                            <td>
                                <select name="webdokan_widget_position" id="webdokan_widget_position" style="border-radius: 8px; min-width: 280px;">
                                    <option value="woocommerce_before_add_to_cart_button" <?php selected($position, 'woocommerce_before_add_to_cart_button'); ?>>Above Add to Cart Button (Recommended)</option>
                                    <option value="woocommerce_after_add_to_cart_button" <?php selected($position, 'woocommerce_after_add_to_cart_button'); ?>>Below Add to Cart Button</option>
                                    <option value="woocommerce_single_product_summary" <?php selected($position, 'woocommerce_single_product_summary'); ?>>End of Product Summary</option>
                                    <option value="shortcode_only" <?php selected($position, 'shortcode_only'); ?>>Manual Shortcode Only ([webdokan_compatibility])</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><strong>Detail Page Direct Link</strong></th>
                            <td>
                                <label style="display: flex; align-items: center; gap: 8px;">
                                    <input name="webdokan_link_to_detail" type="checkbox" value="yes" <?php checked($link_to_detail, 'yes'); ?> />
                                    <span><strong>Open WebDokan verified score breakdown page on badge click</strong></span>
                                </label>
                                <p class="description">When checked, clicking the score badge opens the detailed lab breakdown on WebDokan in a new tab.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="webdokan_theme_mode"><strong>Visual Theme</strong></label></th>
                            <td>
                                <select name="webdokan_theme_mode" id="webdokan_theme_mode" style="border-radius: 8px; min-width: 200px;">
                                    <option value="auto" <?php selected($theme_mode, 'auto'); ?>>Adaptive (Follows Store Theme)</option>
                                    <option value="light" <?php selected($theme_mode, 'light'); ?>>Light Glass</option>
                                    <option value="dark" <?php selected($theme_mode, 'dark'); ?>>Dark Glass</option>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                        <?php submit_button('Save Changes & Connect', 'primary', 'submit', false, array('style' => 'border-radius: 10px; font-weight: 800; padding: 6px 24px;')); ?>
                    </div>
                </form>
            </div>

            <!-- SECTION 3: How to Tag Products -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 22px; margin-bottom: 24px;">
                <h3 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 800; color: #0f172a;">💡 How to Enable Compatibility Scoring on Products</h3>
                <ol style="margin: 0; padding-left: 20px; font-size: 12px; color: #475569; line-height: 1.8;">
                    <li>Open any product in WooCommerce (<strong>Products -> Edit Product</strong>).</li>
                    <li>In the <strong>Product Data -> Inventory</strong> section next to SKU, enter the certified <strong>WDP ID</strong> (e.g. <code>WDP90950</code>).</li>
                    <li>Click <strong>Verify WDP ID</strong> to confirm the lab profile, then click <strong>Update / Publish</strong>.</li>
                    <li>The verified split-pill compatibility badge will render automatically on your product page!</li>
                </ol>
            </div>

        </div>
        <?php
    }
}

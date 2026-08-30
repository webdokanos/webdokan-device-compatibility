/**
 * WebDokan WordPress Admin Helper Script
 */

jQuery(document).ready(function ($) {
    // -------------------------------------------------------------
    // 1. WDP Product ID Verification (Product Edit Screen)
    // -------------------------------------------------------------
    var $verifyBtn = $('#webdokan-verify-btn');
    var $wdpInput = $('#_webdokan_wdp_id');
    var $resultBox = $('#webdokan-verify-result');

    if ($verifyBtn.length && $wdpInput.length) {
        $verifyBtn.on('click', function (e) {
            e.preventDefault();
            var wdpId = $.trim($wdpInput.val());

            if (!wdpId) {
                alert('Please enter a WebDokan Product ID (e.g. WDP90950)');
                $wdpInput.focus();
                return;
            }

            $verifyBtn.prop('disabled', true).text('Verifying...');
            $resultBox.show().html('<div style="color: #64748b; font-size: 12px; padding: 6px 0;">⏳ Connecting to WebDokan Cloud Engine...</div>');

            $.ajax({
                url: window.webdokanAdmin ? window.webdokanAdmin.ajaxUrl : ajaxurl,
                type: 'POST',
                data: {
                    action: 'webdokan_verify_wdp',
                    security: window.webdokanAdmin ? window.webdokanAdmin.nonce : '',
                    wdp_id: wdpId
                },
                success: function (res) {
                    $verifyBtn.prop('disabled', false).text('Verify WDP ID');
                    if (res.success && res.data) {
                        var d = res.data;
                        var html = '<div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 10px; padding: 10px 14px; font-size: 12px; color: #166534; display: flex; align-items: center; justify-content: space-between; gap: 10px;">' +
                            '<div>' +
                            '<strong>✅ Verified WebDokan Profile:</strong> ' + (d.name || d.wdp_id) +
                            '<div style="margin-top: 2px; color: #15803d; font-size: 11px;">Category: <strong>' + (d.category || 'Mobile Accessory') + '</strong> ' +
                            (d.maxWattage ? ' • Max Wattage: <strong>' + d.maxWattage + 'W</strong>' : '') +
                            '</div>' +
                            '</div>' +
                            '<a href="' + (d.verifiedUrl || '#') + '" target="_blank" class="button button-small" style="font-size: 11px; white-space: nowrap;">View Specs ↗</a>' +
                            '</div>';
                        $resultBox.html(html);
                    } else {
                        var errMsg = res.data ? res.data.message : 'Product not found in certified catalog.';
                        $resultBox.html('<div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 10px; padding: 10px 14px; font-size: 12px; color: #991b1b;">❌ ' + errMsg + '</div>');
                    }
                },
                error: function () {
                    $verifyBtn.prop('disabled', false).text('Verify WDP ID');
                    $resultBox.html('<div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 10px; padding: 10px 14px; font-size: 12px; color: #991b1b;">❌ Server communication failed. Check your internet connection.</div>');
                }
            });
        });
    }

    // -------------------------------------------------------------
    // 2. API Key Checker & Visibility Toggle (WebDokan Hub Settings)
    // -------------------------------------------------------------
    var $testKeyBtn = $('#webdokan-test-api-key-btn');
    var $apiKeyInput = $('#webdokan_api_key');
    var $keyResultBox = $('#webdokan-api-key-test-result');
    var $toggleVisibilityBtn = $('#webdokan-toggle-key-visibility');

    if ($toggleVisibilityBtn.length && $apiKeyInput.length) {
        $toggleVisibilityBtn.on('click', function (e) {
            e.preventDefault();
            var currentType = $apiKeyInput.attr('type');
            if (currentType === 'password') {
                $apiKeyInput.attr('type', 'text');
                $toggleVisibilityBtn.text('🙈');
            } else {
                $apiKeyInput.attr('type', 'password');
                $toggleVisibilityBtn.text('👁️');
            }
        });
    }

    if ($testKeyBtn.length && $apiKeyInput.length) {
        $testKeyBtn.on('click', function (e) {
            e.preventDefault();
            var keyVal = $.trim($apiKeyInput.val());

            if (!keyVal) {
                $keyResultBox.show().html('<div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #92400e;">⚠️ Please paste or enter your WebDokan API Key first.</div>');
                $apiKeyInput.focus();
                return;
            }

            $testKeyBtn.prop('disabled', true).text('Testing...');
            $keyResultBox.show().html('<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #64748b;">⏳ Validating API key with WebDokan Cloud...</div>');

            $.ajax({
                url: window.webdokanAdmin ? window.webdokanAdmin.ajaxUrl : ajaxurl,
                type: 'POST',
                data: {
                    action: 'webdokan_test_api_key',
                    security: window.webdokanAdmin ? window.webdokanAdmin.nonce : '',
                    api_key: keyVal
                },
                success: function (res) {
                    $testKeyBtn.prop('disabled', false).text('⚡ Test API Key');
                    if (res.success) {
                        var msg = res.data && res.data.message ? res.data.message : 'API Key is valid and active!';
                        $keyResultBox.html('<div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #166534; font-weight: 600; line-height: 1.5;">✅ ' + msg + '</div>');
                    } else {
                        var errMsg = res.data && res.data.message ? res.data.message : 'Invalid API Key. Please verify your key on WebDokan.';
                        $keyResultBox.html('<div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #991b1b; line-height: 1.5;">❌ ' + errMsg + '</div>');
                    }
                },
                error: function () {
                    $testKeyBtn.prop('disabled', false).text('⚡ Test API Key');
                    $keyResultBox.html('<div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #991b1b; line-height: 1.5;">❌ Connection failed. Please check your internet connection or try again.</div>');
                }
            });
        });
    }
});

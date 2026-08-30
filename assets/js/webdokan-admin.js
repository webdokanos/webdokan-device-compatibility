/**
 * WebDokan WordPress Admin Helper Script
 */

(function ($) {
    'use strict';

    function getAjaxUrl() {
        if (window.webdokanAdmin && window.webdokanAdmin.ajaxUrl) {
            return window.webdokanAdmin.ajaxUrl;
        }
        if (typeof ajaxurl !== 'undefined') {
            return ajaxurl;
        }
        return '/wp-admin/admin-ajax.php';
    }

    function getNonce() {
        return (window.webdokanAdmin && window.webdokanAdmin.nonce) ? window.webdokanAdmin.nonce : '';
    }

    // -------------------------------------------------------------
    // 1. WDP Product ID Verification (Product Edit Screen)
    // -------------------------------------------------------------
    $(document).on('click', '#webdokan-verify-btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var $wdpInput = $('#_webdokan_wdp_id');
        var $resultBox = $('#webdokan-verify-result');
        var wdpId = $.trim($wdpInput.val());

        if (!wdpId) {
            alert('Please enter a WebDokan Product ID (e.g. WDP90950)');
            $wdpInput.focus();
            return;
        }

        $btn.prop('disabled', true).text('Verifying...');
        $resultBox.show().html('<div style="color: #64748b; font-size: 12px; padding: 6px 0;">⏳ Connecting to WebDokan Cloud Engine...</div>');

        $.ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: {
                action: 'webdokan_verify_wdp',
                security: getNonce(),
                wdp_id: wdpId
            },
            success: function (res) {
                $btn.prop('disabled', false).text('Verify WDP ID');
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
                $btn.prop('disabled', false).text('Verify WDP ID');
                $resultBox.html('<div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 10px; padding: 10px 14px; font-size: 12px; color: #991b1b;">❌ Server communication failed. Check your internet connection.</div>');
            }
        });
    });

    // -------------------------------------------------------------
    // 2. API Key Visibility Toggle (WebDokan Hub Settings)
    // -------------------------------------------------------------
    $(document).on('click', '#webdokan-toggle-key-visibility', function (e) {
        e.preventDefault();
        var $apiKeyInput = $('#webdokan_api_key');
        if ($apiKeyInput.length) {
            var currentType = $apiKeyInput.attr('type');
            if (currentType === 'password') {
                $apiKeyInput.attr('type', 'text');
                $(this).text('🙈');
            } else {
                $apiKeyInput.attr('type', 'password');
                $(this).text('👁️');
            }
        }
    });

    // -------------------------------------------------------------
    // 3. API Key Tester (WebDokan Hub Settings)
    // -------------------------------------------------------------
    $(document).on('click', '#webdokan-test-api-key-btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var $apiKeyInput = $('#webdokan_api_key');
        var $keyResultBox = $('#webdokan-api-key-test-result');
        var keyVal = $.trim($apiKeyInput.val());

        if (!keyVal) {
            $keyResultBox.show().html('<div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #92400e;">⚠️ Please paste or enter your WebDokan API Key first.</div>');
            $apiKeyInput.focus();
            return;
        }

        $btn.prop('disabled', true).text('Testing...');
        $keyResultBox.show().html('<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #64748b;">⏳ Validating API key with WebDokan Cloud...</div>');

        $.ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: {
                action: 'webdokan_test_api_key',
                security: getNonce(),
                api_key: keyVal
            },
            success: function (res) {
                $btn.prop('disabled', false).text('⚡ Test API Key');
                if (res.success) {
                    var msg = res.data && res.data.message ? res.data.message : 'API Key is valid and active!';
                    $keyResultBox.html('<div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #166534; font-weight: 600; line-height: 1.5;">✅ ' + msg + '</div>');
                } else {
                    var errMsg = res.data && res.data.message ? res.data.message : 'Invalid API Key. Please verify your key on WebDokan.';
                    $keyResultBox.html('<div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #991b1b; line-height: 1.5;">❌ ' + errMsg + '</div>');
                }
            },
            error: function (xhr, status, error) {
                $btn.prop('disabled', false).text('⚡ Test API Key');
                $keyResultBox.html('<div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #991b1b; line-height: 1.5;">❌ Connection error (' + (error || status) + '). Try saving first.</div>');
            }
        });
    });

})(jQuery);

/**
 * WebDokan WordPress Admin Helper Script
 */

jQuery(document).ready(function ($) {
    var $verifyBtn = $('#webdokan-verify-btn');
    var $wdpInput = $('#_webdokan_wdp_id');
    var $resultBox = $('#webdokan-verify-result');

    if (!$verifyBtn.length) return;

    $verifyBtn.on('click', function (e) {
        e.preventDefault();
        var wdpId = $.trim($wdpInput.val());

        if (!wdpId) {
            alert('Please enter a WebDokan Product ID (e.g. WDP90950)');
            $wdpInput.focus();
            return;
        }

        $verifyBtn.prop('disabled', true).text('Verifying...');
        $resultBox.show().html('<div style="color: #64748b; font-size: 12px;">Connecting to WebDokan Cloud Engine...</div>');

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
});

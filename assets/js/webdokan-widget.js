/**
 * WebDokan Device Compatibility & Hardware Lab Client
 * Ultra-clean search pill input with live suggestions and score iframe sync.
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var containers = document.querySelectorAll('.webdokan-compat-container');
        if (!containers.length) return;

        var config = window.webdokanData || {
            apiUrl: 'https://webdokan.com',
            apiKey: '',
            linkToDetail: true,
            siteDomain: window.location.hostname,
            syncedDevices: []
        };

        var localCatalog = Array.isArray(config.syncedDevices) ? config.syncedDevices : [];
        var defaultWddId = 'WDD2388';
        var defaultDeviceName = 'Apple iPhone 15 Pro';

        containers.forEach(function (container) {
            var wdpId = container.getAttribute('data-wdp-id') || '';
            var fallbackWdd = container.getAttribute('data-default-wdd') || defaultWddId;
            var searchInput = container.querySelector('.webdokan-device-search-input');
            var actionBtn = container.querySelector('.webdokan-search-action-btn');
            var suggestionsList = container.querySelector('.webdokan-suggestions-list');
            var iframe = container.querySelector('.webdokan-score-iframe');

            var searchDebounceTimer = null;
            var activeWddId = fallbackWdd;
            var selectedName = defaultDeviceName;

            function updateScoreIframe(newWddId, deviceName) {
                activeWddId = newWddId || fallbackWdd;
                selectedName = deviceName || defaultDeviceName;

                if (searchInput) {
                    searchInput.value = selectedName;
                    searchInput.setAttribute('data-selected-name', selectedName);
                }

                if (iframe) {
                    var iframeUrl = config.apiUrl + '/' + encodeURIComponent(wdpId) + '/' + encodeURIComponent(activeWddId) + '/score' +
                        '?embed=1' +
                        (config.apiKey ? '&api_key=' + encodeURIComponent(config.apiKey) : '') +
                        (config.siteDomain ? '&domain=' + encodeURIComponent(config.siteDomain) : '');

                    iframe.style.opacity = '0.4';
                    iframe.src = iframeUrl;
                    iframe.onload = function () {
                        iframe.style.opacity = '1';
                    };
                }
            }

            // Search autocomplete handler
            function performSearch(query) {
                var q = (query || '').trim();

                // 1. Try local WordPress AJAX search (fast local query from wp_options synced catalog)
                var ajaxSearchUrl = (config.ajaxUrl || '/wp-admin/admin-ajax.php') + '?action=webdokan_search_devices&q=' + encodeURIComponent(q);

                fetch(ajaxSearchUrl)
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data && data.devices && data.devices.length > 0) {
                            renderSuggestions(data.devices);
                        } else {
                            fallbackCloudSearch(q);
                        }
                    })
                    .catch(function () {
                        fallbackCloudSearch(q);
                    });
            }

            function fallbackCloudSearch(query) {
                var searchUrl = config.apiUrl + '/api/v1/compatibility/search-devices?q=' + encodeURIComponent(query || '') +
                    (config.apiKey ? '&api_key=' + encodeURIComponent(config.apiKey) : '');

                fetch(searchUrl)
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        renderSuggestions(data.devices || []);
                    })
                    .catch(function () {
                        renderSuggestions([]);
                    });
            }

            function renderSuggestions(devices) {
                if (!devices || !devices.length) {
                    suggestionsList.innerHTML = '<div style="padding: 10px 12px; font-size: 12px; color: #94a3b8; text-align: center;">No matching phone models found.</div>';
                    suggestionsList.style.display = 'block';
                    return;
                }

                var html = '';
                devices.forEach(function (d) {
                    var wdd = d.wddId || d.sku || ('WDD' + d.id);
                    var brand = d.brand || 'Phone';
                    var displayName = d.name || d.marketingName || d.model || (brand + ' ' + (d.model || ''));
                    
                    html += '<div class="webdokan-suggestion-item" data-wdd-id="' + wdd + '" data-device-name="' + displayName.trim() + '">' +
                        '<div class="webdokan-suggestion-info">' +
                        '<span class="webdokan-suggestion-brand">' + brand + '</span>' +
                        '<span class="webdokan-suggestion-name">' + displayName.trim() + '</span>' +
                        '</div>' +
                        '<span class="webdokan-suggestion-sku">' + wdd + '</span>' +
                        '</div>';
                });

                suggestionsList.innerHTML = html;
                suggestionsList.style.display = 'block';

                var items = suggestionsList.querySelectorAll('.webdokan-suggestion-item');
                items.forEach(function (item) {
                    item.addEventListener('click', function () {
                        var targetWdd = this.getAttribute('data-wdd-id');
                        var targetName = this.getAttribute('data-device-name');
                        updateScoreIframe(targetWdd, targetName);
                        suggestionsList.style.display = 'none';
                    });
                });
            }

            // Input typing and focus
            if (searchInput) {
                searchInput.addEventListener('focus', function () {
                    this.select();
                    performSearch('');
                });

                searchInput.addEventListener('input', function () {
                    var val = this.value.trim();
                    clearTimeout(searchDebounceTimer);
                    searchDebounceTimer = setTimeout(function () {
                        performSearch(val);
                    }, 120);
                });
            }

            // Action "Change" button click
            if (actionBtn) {
                actionBtn.addEventListener('click', function () {
                    if (searchInput) {
                        searchInput.focus();
                        searchInput.select();
                        performSearch('');
                    }
                });
            }

            // Close suggestions on outside click and restore selected name if left empty
            document.addEventListener('click', function (e) {
                if (!container.contains(e.target)) {
                    if (suggestionsList) {
                        suggestionsList.style.display = 'none';
                    }
                    if (searchInput && !searchInput.value.trim()) {
                        searchInput.value = selectedName;
                    }
                }
            });
        });
    });
})();


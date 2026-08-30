/**
 * WebDokan Device Compatibility & Hardware Lab Client
 * Ultra-clean search pill input with live suggestions and score iframe sync.
 */

(function () {
    'use strict';

    function initWidgets() {
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

        containers.forEach(function (container) {
            var wdpId = container.getAttribute('data-wdp-id') || '';
            var defaultWdd = container.getAttribute('data-default-wdd') || '';
            var searchInput = container.querySelector('.webdokan-device-search-input');
            var actionBtn = container.querySelector('.webdokan-search-action-btn');
            var suggestionsList = container.querySelector('.webdokan-suggestions-list');
            var iframe = container.querySelector('.webdokan-score-iframe');

            var searchDebounceTimer = null;
            var activeWddId = defaultWdd;
            var selectedName = searchInput ? (searchInput.value || '') : '';

            function updateScoreIframe(newWddId, deviceName) {
                activeWddId = newWddId || '';
                selectedName = deviceName || '';

                if (searchInput) {
                    searchInput.value = selectedName;
                    searchInput.setAttribute('data-selected-name', selectedName);
                }

                if (iframe && activeWddId) {
                    var cleanWdd = String(activeWddId).trim();
                    var newSrc = config.apiUrl + '/' + encodeURIComponent(wdpId) + '/' + encodeURIComponent(cleanWdd) + '/score';
                    iframe.setAttribute('src', newSrc);
                    iframe.src = newSrc;
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
                var q = (query || '').trim();
                if (!q) {
                    renderSuggestions([]);
                    return;
                }

                // Query standard /api/v1/compatibility/models?brand= or /search-devices
                var modelsUrl = config.apiUrl + '/api/v1/compatibility/models?brand=' + encodeURIComponent(q) +
                    (config.apiKey ? '&api_key=' + encodeURIComponent(config.apiKey) : '');

                fetch(modelsUrl)
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data && data.models && data.models.length > 0) {
                            renderSuggestions(data.models);
                        } else {
                            var searchUrl = config.apiUrl + '/api/v1/compatibility/search-devices?q=' + encodeURIComponent(q) +
                                (config.apiKey ? '&api_key=' + encodeURIComponent(config.apiKey) : '');
                            fetch(searchUrl)
                                .then(function (r) { return r.json(); })
                                .then(function (sd) {
                                    renderSuggestions(sd.devices || []);
                                })
                                .catch(function () {
                                    renderSuggestions([]);
                                });
                        }
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
                    var wdd = d.sku || d.wddId || (d.entryId ? ('WDD' + d.entryId) : ('WDD' + (d.id || '')));
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
                    function selectItem(e) {
                        if (e && e.preventDefault) e.preventDefault();
                        var targetWdd = item.getAttribute('data-wdd-id');
                        var targetName = item.getAttribute('data-device-name');
                        updateScoreIframe(targetWdd, targetName);
                        suggestionsList.style.display = 'none';
                    }

                    item.addEventListener('mousedown', selectItem);
                    item.addEventListener('click', selectItem);
                });
            }

            // Input typing, focus and Enter key handling
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
                    }, 80);
                });

                searchInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.keyCode === 13) {
                        e.preventDefault();
                        if (suggestionsList && suggestionsList.style.display !== 'none') {
                            var firstItem = suggestionsList.querySelector('.webdokan-suggestion-item');
                            if (firstItem) {
                                var targetWdd = firstItem.getAttribute('data-wdd-id');
                                var targetName = firstItem.getAttribute('data-device-name');
                                updateScoreIframe(targetWdd, targetName);
                                suggestionsList.style.display = 'none';
                            }
                        }
                    }
                });
            }

            // Action "Change" button click
            if (actionBtn) {
                actionBtn.addEventListener('click', function (e) {
                    e.preventDefault();
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
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWidgets);
    } else {
        initWidgets();
    }
})();


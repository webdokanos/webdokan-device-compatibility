/**
 * WebDokan Device Compatibility & Hardware Lab Client
 * Auto-detects visitor device, supports local synced devices, quick-pick chips, and iframe synchronization.
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
        var defaultWddId = 'WDD833335';
        var defaultDeviceName = 'Apple iPhone 15 Pro';

        // Detect user device from user agent
        function detectVisitorBrandOrModel() {
            var ua = navigator.userAgent || '';
            if (/iPhone/i.test(ua)) {
                return 'iPhone';
            }
            if (/SM-|Samsung|Galaxy/i.test(ua)) {
                return 'Samsung';
            }
            if (/Pixel/i.test(ua)) {
                return 'Pixel';
            }
            if (/Xiaomi|Redmi|POCO/i.test(ua)) {
                return 'Redmi';
            }
            if (/OnePlus/i.test(ua)) {
                return 'OnePlus';
            }
            return null;
        }

        containers.forEach(function (container) {
            var wdpId = container.getAttribute('data-wdp-id') || '';
            var fallbackWdd = container.getAttribute('data-default-wdd') || defaultWddId;
            var searchExpandWrap = container.querySelector('.webdokan-search-expand-wrap');
            var searchInput = container.querySelector('.webdokan-device-search-input');
            var clearBtn = container.querySelector('.webdokan-search-clear-btn');
            var toggleSearchBtn = container.querySelector('.webdokan-toggle-search-btn');
            var suggestionsList = container.querySelector('.webdokan-suggestions-list');
            var currentDeviceLabel = container.querySelector('.webdokan-current-device-name');
            var quickChips = container.querySelectorAll('.webdokan-quick-chip');
            var iframe = container.querySelector('.webdokan-score-iframe');

            var searchDebounceTimer = null;
            var activeWddId = fallbackWdd;

            function updateScoreIframe(newWddId, deviceName) {
                activeWddId = newWddId || fallbackWdd;
                if (currentDeviceLabel && deviceName) {
                    currentDeviceLabel.textContent = deviceName;
                }

                // Update active state on quick chips
                quickChips.forEach(function (chip) {
                    if (chip.getAttribute('data-wdd-id') === activeWddId) {
                        chip.classList.add('active');
                    } else {
                        chip.classList.remove('active');
                    }
                });

                if (iframe) {
                    var iframeUrl = config.apiUrl + '/' + encodeURIComponent(wdpId) + '/' + encodeURIComponent(activeWddId) + '/score' +
                        '?embed=1' +
                        (config.apiKey ? '&api_key=' + encodeURIComponent(config.apiKey) : '') +
                        (config.siteDomain ? '&domain=' + encodeURIComponent(config.siteDomain) : '');

                    iframe.style.opacity = '0.6';
                    iframe.src = iframeUrl;
                    iframe.onload = function () {
                        iframe.style.opacity = '1';
                    };
                }
            }

            // Perform auto-detection
            var detectedKeyword = detectVisitorBrandOrModel();
            if (detectedKeyword) {
                if (localCatalog.length > 0) {
                    var match = localCatalog.find(function (d) {
                        var str = ((d.brand || '') + ' ' + (d.marketingName || d.model || '')).toLowerCase();
                        return str.indexOf(detectedKeyword.toLowerCase()) !== -1;
                    });
                    if (match) {
                        updateScoreIframe(match.wddId || ('WDD' + match.id), match.name || (match.brand + ' ' + match.model));
                    }
                } else {
                    fetch(config.apiUrl + '/api/v1/compatibility/search-devices?q=' + encodeURIComponent(detectedKeyword))
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            if (data && data.devices && data.devices.length > 0) {
                                var firstMatch = data.devices[0];
                                updateScoreIframe(firstMatch.wddId || firstMatch.sku, firstMatch.name);
                            }
                        })
                        .catch(function () {});
                }
            }

            // Quick Chips Click Event
            quickChips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    var targetWdd = this.getAttribute('data-wdd-id');
                    var targetName = this.getAttribute('data-name');
                    updateScoreIframe(targetWdd, targetName);
                    if (searchExpandWrap) searchExpandWrap.style.display = 'none';
                });
            });

            // Toggle Search Bar
            if (toggleSearchBtn) {
                toggleSearchBtn.addEventListener('click', function () {
                    if (searchExpandWrap) {
                        var isVisible = searchExpandWrap.style.display !== 'none';
                        searchExpandWrap.style.display = isVisible ? 'none' : 'block';
                        if (!isVisible && searchInput) {
                            searchInput.focus();
                            performSearch(searchInput.value.trim());
                        }
                    }
                });
            }

            // Search autocomplete handler (searches local catalog first for 0ms speed)
            function performSearch(query) {
                var q = (query || '').toLowerCase().trim();

                if (localCatalog.length > 0) {
                    var filtered = [];
                    if (!q) {
                        filtered = localCatalog.slice(0, 10);
                    } else {
                        filtered = localCatalog.filter(function (d) {
                            var str = ((d.brand || '') + ' ' + (d.marketingName || d.model || '') + ' ' + (d.wddId || '')).toLowerCase();
                            return str.indexOf(q) !== -1;
                        }).slice(0, 15);
                    }
                    renderSuggestions(filtered);
                    return;
                }

                // Fallback to Cloud Search API
                var searchUrl = config.apiUrl + '/api/v1/compatibility/search-devices?q=' + encodeURIComponent(query || '');
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
                    var displayName = d.name || ((d.brand || '') + ' ' + (d.marketingName || d.model || ''));
                    html += '<div class="webdokan-suggestion-item" data-wdd-id="' + wdd + '" data-device-name="' + displayName.trim() + '">' +
                        '<div class="webdokan-suggestion-info">' +
                        '<span class="webdokan-suggestion-brand">' + (d.brand || 'Phone') + '</span>' +
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
                        if (searchExpandWrap) searchExpandWrap.style.display = 'none';
                    });
                });
            }

            // Input typing event
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    var val = this.value.trim();
                    clearTimeout(searchDebounceTimer);
                    searchDebounceTimer = setTimeout(function () {
                        performSearch(val);
                    }, 120);
                });

                searchInput.addEventListener('focus', function () {
                    performSearch(this.value.trim());
                });
            }

            // Clear / Close button
            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    if (searchExpandWrap) {
                        searchExpandWrap.style.display = 'none';
                    }
                });
            }

            // Close suggestions on outside click
            document.addEventListener('click', function (e) {
                if (!container.contains(e.target)) {
                    if (suggestionsList) {
                        suggestionsList.style.display = 'none';
                    }
                }
            });
        });
    });
})();


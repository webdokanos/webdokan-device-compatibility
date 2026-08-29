/**
 * WebDokan Device Compatibility Frontend Client
 * Asynchronously checks compatibility and renders official split-pill capsule badges.
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var containers = document.querySelectorAll('.webdokan-compat-container');
        if (!containers.length) return;

        var config = window.webdokanData || {
            apiUrl: 'https://webdokan.com',
            linkToDetail: true,
            siteDomain: window.location.hostname
        };

        var brandCache = null;
        var modelCache = {};

        // Fetch Brands once and cache
        function fetchBrands(callback) {
            if (brandCache) {
                return callback(brandCache);
            }
            fetch(config.apiUrl + '/api/v1/compatibility/brands')
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    brandCache = data.brands || [];
                    callback(brandCache);
                })
                .catch(function (err) {
                    console.warn('[WebDokan] Failed to load brands:', err);
                });
        }

        // Initialize each widget on page
        containers.forEach(function (container) {
            var wdpId = container.getAttribute('data-wdp-id');
            var linkEnabled = container.getAttribute('data-link-enabled') === '1';
            var brandSelect = container.querySelector('.webdokan-brand-select');
            var modelSelect = container.querySelector('.webdokan-model-select');
            var badgeContainer = container.querySelector('.webdokan-badge-container');
            var badgePill = container.querySelector('.webdokan-compat-badge-pill');
            var scoreNum = container.querySelector('.webdokan-score-num');
            var badgeLabel = container.querySelector('.webdokan-badge-label');
            var insightText = container.querySelector('.webdokan-insight-text');
            var currentDetailUrl = '';

            // Populate Brands
            fetchBrands(function (brands) {
                brands.forEach(function (b) {
                    var opt = document.createElement('option');
                    opt.value = b.name;
                    opt.textContent = b.name + ' (' + b.count + ')';
                    brandSelect.appendChild(opt);
                });
            });

            // Handle Brand Selection
            brandSelect.addEventListener('change', function () {
                var brand = this.value;
                modelSelect.innerHTML = '<option value="">Select Model...</option>';
                badgeContainer.style.display = 'none';

                if (!brand) {
                    modelSelect.disabled = true;
                    return;
                }

                modelSelect.disabled = true;

                if (modelCache[brand]) {
                    populateModels(modelCache[brand]);
                } else {
                    fetch(config.apiUrl + '/api/v1/compatibility/models?brand=' + encodeURIComponent(brand))
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            var models = data.models || [];
                            modelCache[brand] = models;
                            populateModels(models);
                        })
                        .catch(function (err) {
                            console.warn('[WebDokan] Failed to load models:', err);
                        });
                }
            });

            function populateModels(models) {
                models.forEach(function (m) {
                    var opt = document.createElement('option');
                    opt.value = m.model;
                    opt.textContent = m.displayName;
                    opt.setAttribute('data-device-id', m.id || m.sku || '');
                    modelSelect.appendChild(opt);
                });
                modelSelect.disabled = false;
            }

            // Handle Model Selection (Triggers Score Calculation)
            modelSelect.addEventListener('change', function () {
                var model = this.value;
                var brand = brandSelect.value;
                if (!model || !brand) {
                    badgeContainer.style.display = 'none';
                    return;
                }

                // Show loading state
                badgeContainer.style.display = 'block';
                scoreNum.textContent = '...';
                badgeLabel.textContent = 'CALCULATING...';
                badgePill.setAttribute('data-type', 'functional');
                insightText.textContent = 'Analyzing charging protocols and hardware fit...';

                var checkUrl = config.apiUrl + '/api/v1/compatibility/check' +
                    '?wdp_id=' + encodeURIComponent(wdpId) +
                    '&brand=' + encodeURIComponent(brand) +
                    '&model=' + encodeURIComponent(model) +
                    '&api_key=' + encodeURIComponent(config.apiKey || '') +
                    '&domain=' + encodeURIComponent(config.siteDomain);

                fetch(checkUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-WebDokan-Key': config.apiKey || ''
                    }
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (!data.success) {
                            scoreNum.textContent = '0%';
                            badgeLabel.textContent = 'INCOMPATIBLE';
                            badgePill.setAttribute('data-type', 'incompatible');
                            insightText.textContent = data.error || 'No verified profile match found.';
                            return;
                        }

                        scoreNum.textContent = data.score + '%';
                        badgeLabel.textContent = data.label;
                        badgePill.setAttribute('data-type', data.badgeType || 'functional');
                        insightText.textContent = data.insight;
                        currentDetailUrl = data.detailUrl || '';
                    })
                    .catch(function (err) {
                        console.error('[WebDokan] Check error:', err);
                        scoreNum.textContent = '--%';
                        badgeLabel.textContent = 'ERROR';
                    });
            });

            // Handle Click on Split-Pill Badge
            badgePill.addEventListener('click', function () {
                if (linkEnabled && currentDetailUrl) {
                    window.open(currentDetailUrl, '_blank', 'noopener,noreferrer');
                }
            });
        });
    });
})();

document.addEventListener('DOMContentLoaded', function () {
    const app = document.getElementById('heatmap-app');

    if (!app) {
        return;
    }

    const form = document.getElementById('heatmap-form');
    const generateButton = document.getElementById('generate-button');
    const pollButton = document.getElementById('poll-button');
    const predictionButton = document.getElementById('prediction-button');
    const predictionPollButton = document.getElementById('prediction-poll-button');
    const comparisonModeField = document.getElementById('comparison_mode');
    const includeTrendField = form ? form.querySelector('input[name="include_trend"]') : null;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const feedback = document.getElementById('heatmap-feedback');
    const mapContainer = document.getElementById('heatmap-map');
    const rankingList = document.getElementById('ranking-list');
    const trendList = document.getElementById('trend-list');
    const heatmapResultsSection = document.getElementById('heatmap-results');

    const urls = {
        generate: app.dataset.generateUrl || '',
        resultTemplate: app.dataset.resultUrlTemplate || '',
        predictionGenerate: app.dataset.predictionGenerateUrl || '',
        predictionResultTemplate: app.dataset.predictionResultUrlTemplate || '',
    };

    const nodes = {
        statusChip: document.getElementById('job-status-chip'),
        pointsCountChip: document.getElementById('points-count-chip'),
        metricJobId: document.getElementById('metric-job-id'),
        metricPeriod: document.getElementById('metric-period'),
        metricTotalViolations: document.getElementById('metric-total-violations'),
        metricCriticalAreas: document.getElementById('metric-critical-areas'),
        timelineCity: document.getElementById('timeline-city'),
        timelineRange: document.getElementById('timeline-range'),
        detailArea: document.getElementById('detail-area'),
        detailCellId: document.getElementById('detail-cell-id'),
        detailLat: document.getElementById('detail-lat'),
        detailLng: document.getElementById('detail-lng'),
        detailIntensity: document.getElementById('detail-intensity'),
        detailRisk: document.getElementById('detail-risk'),
        metricPredictionRisk: document.getElementById('metric-prediction-risk'),
        metricPredictionSource: document.getElementById('metric-prediction-source'),
        predictionSourceBadge: document.getElementById('prediction-source-badge'),
        predictionPanel: document.getElementById('prediction-panel'),
        predictionJobId: document.getElementById('prediction-job-id'),
        predictionStatus: document.getElementById('prediction-status'),
        predictionSummary: document.getElementById('prediction-summary'),
        predictionHotspotsList: document.getElementById('prediction-hotspots-list'),
        predictionRecommendationsList: document.getElementById('prediction-recommendations-list'),
        predictionLimitationsList: document.getElementById('prediction-limitations-list'),
    };

    const DEFAULT_MAP_CENTER = [35.0, 38.5];
    const DEFAULT_MAP_ZOOM = 6;
    const state = {
        jobId: '',
        predictionJobId: '',
        heatmapResult: null,
        predictionResult: null,
        map: null,
        mapLayerGroup: null,
        activeMarker: null,
        renderedPoints: [],
        markerByCellId: {},
        timer: null,
        predictionTimer: null,
        predictionPollingStartedAt: 0,
    };

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function scrollToSection(target) {
        if (!target) {
            return;
        }

        target.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    }

    function translateStatus(value) {
        const map = {
            idle: 'في الانتظار',
            queued: 'قيد الانتظار',
            processing: 'قيد المعالجة',
            pending: 'قيد الانتظار',
            success: 'ناجحة',
            failed: 'فاشلة',
            invalid: 'غير صالحة',
        };
        return map[String(value || '').toLowerCase()] || String(value || '-');
    }

    function translateRiskLevel(value) {
        const map = {
            low: 'منخفض',
            medium: 'متوسط',
            high: 'عالي',
            critical: 'حرج',
        };
        return map[String(value || '').toLowerCase()] || 'غير متاح';
    }

    function translateTimeBucket(value) {
        const map = {
            all_day: 'كل اليوم',
            morning: 'صباحاً',
            afternoon: 'ظهراً',
            evening: 'مساءً',
            night: 'ليلاً',
        };
        return map[String(value || '').toLowerCase()] || 'غير محددة';
    }

    function translateTrend(value) {
        const map = {
            up: 'متزايد',
            increasing: 'متزايد',
            down: 'منخفض',
            decreasing: 'منخفض',
            stable: 'مستقر',
        };
        return map[String(value || '').toLowerCase()] || 'مستقر';
    }

    function translatePredictionSource(value) {
        const normalized = String(value || '').toLowerCase();
        if (normalized === 'qwen_api') {
            return 'Qwen API';
        }
        if (normalized === 'ollama') {
            return 'Ollama';
        }
        if (normalized.startsWith('fallback')) {
            return 'احتياطي';
        }
        return '-';
    }

    function isPlaceholderAreaName(value) {
        const text = String(value || '').trim().toLowerCase();
        return !text || text.startsWith('demo hotspot') || text.startsWith('cell ');
    }

    function displayAreaLabel(item) {
        const candidates = [
            item?.area_name,
            item?.area_label,
            item?.location_label,
            item?.street_name,
            item?.nearest_street,
            item?.nearest_area,
            item?.name,
            item?.label,
        ];

        for (let index = 0; index < candidates.length; index += 1) {
            const candidate = String(candidates[index] || '').trim();
            if (candidate && !isPlaceholderAreaName(candidate)) {
                return candidate;
            }
        }

        return 'منطقة غير محددة';
    }

    function getSelectedViolationLabel() {
        const select = document.getElementById('violation_type_id');
        const option = select ? select.options[select.selectedIndex] : null;
        const label = option ? String(option.textContent || '').trim() : '';
        return label || 'كل الأنواع';
    }

    function estimateRiskLevelFromIntensity(intensity) {
        const score = Number(intensity) || 0;
        if (score >= 0.82) {
            return 'critical';
        }
        if (score >= 0.62) {
            return 'high';
        }
        if (score >= 0.42) {
            return 'medium';
        }
        return 'low';
    }

    function buildRiskBadge(level) {
        const normalized = String(level || 'medium').toLowerCase();
        return '<span class="risk-badge risk-badge--' + escapeHtml(normalized) + '">' + escapeHtml(translateRiskLevel(normalized)) + '</span>';
    }

    function setBusy(isBusy) {
        if (!generateButton) {
            return;
        }

        generateButton.disabled = isBusy;
        generateButton.textContent = isBusy ? 'جاري التوليد...' : 'توليد الخريطة الحرارية';
    }

    function setPredictionBusy(isBusy) {
        if (predictionButton) {
            predictionButton.disabled = isBusy || !state.heatmapResult;
            predictionButton.textContent = isBusy ? 'جاري التوليد...' : 'توليد توصيات الذكاء الاصطناعي';
        }

        if (predictionPollButton) {
            predictionPollButton.disabled = !state.predictionJobId;
        }
    }

    function setStatus(status, errorMessage) {
        if (nodes.statusChip) {
            nodes.statusChip.textContent = translateStatus(status || 'idle');
        }

        if (errorMessage) {
            showFeedback('تعذر تنفيذ العملية', errorMessage);
        }
    }

    function clearTimer() {
        if (state.timer) {
            window.clearTimeout(state.timer);
            state.timer = null;
        }
    }

    function clearPredictionTimer() {
        if (state.predictionTimer) {
            window.clearTimeout(state.predictionTimer);
            state.predictionTimer = null;
        }
    }

    function schedulePoll() {
        clearTimer();
        state.timer = window.setTimeout(function () {
            pollResult().catch(handleAsyncError);
        }, 2500);
    }

    function schedulePredictionPoll() {
        clearPredictionTimer();
        state.predictionTimer = window.setTimeout(function () {
            pollPredictionResult().catch(handleAsyncError);
        }, 3000);
    }

    function getResultUrl(jobId) {
        return urls.resultTemplate.replace('__JOB_ID__', encodeURIComponent(jobId));
    }

    function getPredictionResultUrl(jobId) {
        return urls.predictionResultTemplate.replace('__JOB_ID__', encodeURIComponent(jobId));
    }

    function getFormPayload() {
        const formData = new FormData(form);

        return {
            city: String(formData.get('city') || '').trim(),
            date_from: String(formData.get('date_from') || '').trim(),
            date_to: String(formData.get('date_to') || '').trim(),
            violation_type_id: String(formData.get('violation_type_id') || '').trim() || null,
            time_bucket: String(formData.get('time_bucket') || '').trim(),
            grid_size_meters: String(formData.get('grid_size_meters') || '300').trim(),
            include_ranking: formData.get('include_ranking') === '1',
            include_trend: formData.get('include_trend') === '1',
            comparison_mode: String(formData.get('comparison_mode') || '').trim(),
            include_synthetic: false,
        };
    }

    function validatePayload(payload) {
        if (!payload.city || !payload.date_from || !payload.date_to) {
            return 'المدينة وتاريخ البداية وتاريخ النهاية حقول مطلوبة.';
        }

        if (payload.date_from > payload.date_to) {
            return 'يجب أن يكون تاريخ البداية قبل أو مساويًا لتاريخ النهاية.';
        }

        if (payload.include_trend && !payload.comparison_mode) {
            return 'يجب اختيار نوع المقارنة عند تفعيل اتجاه المخالفات.';
        }

        return '';
    }

    function showFeedback(title, description) {
        if (!feedback) {
            return;
        }

        const titleNode = feedback.querySelector('h2');
        const descriptionNode = feedback.querySelector('p');

        if (titleNode) {
            titleNode.textContent = title;
        }

        if (descriptionNode) {
            descriptionNode.textContent = description;
        }

        feedback.classList.remove('is-hidden');
    }

    function hideFeedback() {
        if (feedback) {
            feedback.classList.add('is-hidden');
        }
    }

    function resetDetails() {
        if (nodes.detailArea) nodes.detailArea.textContent = '-';
        if (nodes.detailCellId) nodes.detailCellId.textContent = '-';
        if (nodes.detailLat) nodes.detailLat.textContent = '-';
        if (nodes.detailLng) nodes.detailLng.textContent = '-';
        if (nodes.detailIntensity) nodes.detailIntensity.textContent = '-';
        if (nodes.detailRisk) nodes.detailRisk.textContent = '-';
    }

    function syncTrendControls() {
        if (!comparisonModeField || !includeTrendField) {
            return;
        }

        comparisonModeField.disabled = !includeTrendField.checked;
        if (!includeTrendField.checked) {
            comparisonModeField.value = '';
        }
    }

    function resetPredictionView(message) {
        if (nodes.predictionPanel) nodes.predictionPanel.classList.add('is-hidden');
        if (nodes.metricPredictionRisk) nodes.metricPredictionRisk.textContent = '-';
        if (nodes.metricPredictionSource) nodes.metricPredictionSource.textContent = '-';
        if (nodes.predictionSourceBadge) nodes.predictionSourceBadge.textContent = '-';
        if (nodes.predictionJobId) nodes.predictionJobId.textContent = '-';
        if (nodes.predictionStatus) nodes.predictionStatus.textContent = 'في الانتظار';
        if (nodes.predictionSummary) nodes.predictionSummary.textContent = '-';
        if (nodes.predictionRecommendationsList) {
            nodes.predictionRecommendationsList.innerHTML = '<div class="empty-state">' + escapeHtml(message || 'ستظهر هنا توصيات توزيع الدوريات بعد التوليد.') + '</div>';
        }
        if (nodes.predictionHotspotsList) {
            nodes.predictionHotspotsList.innerHTML = '';
        }
        if (nodes.predictionLimitationsList) {
            nodes.predictionLimitationsList.innerHTML = '<div class="empty-state">ستظهر هنا الملاحظات عند الحاجة.</div>';
            nodes.predictionLimitationsList.classList.add('is-hidden');
        }
    }

    function parseDateRange(fromDate, toDate) {
        if (!fromDate || !toDate) {
            return '-';
        }
        return fromDate + ' - ' + toDate;
    }

    function parseJsonResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (contentType.toLowerCase().includes('application/json')) {
            return response.json();
        }

        return response.text().then(function (text) {
            if (!text) {
                return {};
            }

            try {
                return JSON.parse(text);
            } catch (error) {
                return { message: text };
            }
        });
    }

    function buildPredictionSummaryPayload() {
        const heatmapData = state.heatmapResult && state.heatmapResult.data ? state.heatmapResult.data : null;
        if (!heatmapData) {
            return null;
        }

        const meta = heatmapData.meta || {};
        const ranking = Array.isArray(heatmapData.ranking) ? heatmapData.ranking : [];
        const trend = Array.isArray(heatmapData.trend) ? heatmapData.trend : [];
        const trendByCellId = {};

        trend.forEach(function (item) {
            const key = String(item.cell_id || item.label || '').trim();
            if (key) {
                trendByCellId[key] = item;
            }
        });

        const hotspots = ranking
            .filter(function (item) { return !isPlaceholderAreaName(displayAreaLabel(item)); })
            .slice(0, 2)
            .map(function (item, index) {
                const trendItem = trendByCellId[String(item.cell_id || '').trim()] || {};
                const currentIntensity = Number(trendItem.current_intensity);
                const previousIntensity = Number(trendItem.previous_intensity);
                const difference = Number(trendItem.difference);
                const delta = Number.isFinite(difference)
                    ? difference
                    : (Number.isFinite(currentIntensity) && Number.isFinite(previousIntensity) ? currentIntensity - previousIntensity : 0);
                const percentageChange = Number.isFinite(previousIntensity) && previousIntensity > 0
                    ? (delta / previousIntensity) * 100
                    : ((Number(item.intensity) || 0) * 100);
                const recentCount = Math.max(1, Math.round((Number(item.intensity) || 0) * (Number(meta.total_violations) || 10)));
                const previousCount = Math.max(1, Math.round(recentCount / (1 + Math.max(percentageChange, -90) / 100)));

                return {
                    area_name: displayAreaLabel(item),
                    density_score: Number((Number(item.intensity) || 0).toFixed(4)),
                    rank: index + 1,
                    trend: String(trendItem.trend || (percentageChange > 5 ? 'increasing' : percentageChange < -5 ? 'decreasing' : 'stable')).toLowerCase(),
                    percentage_change: Number(percentageChange.toFixed(2)),
                    dominant_violation_type: getSelectedViolationLabel(),
                    dominant_time_bucket: String(meta.time_bucket || '').trim() || 'all_day',
                    recent_count: recentCount,
                    previous_count: previousCount,
                    moving_average_score: Number((Number(item.intensity) || 0).toFixed(4)),
                };
            });

        if (!hotspots.length) {
            return null;
        }

        return {
            heatmap_summary: {
                city: heatmapData.city || '-',
                from_date: meta.date_from || '',
                to_date: meta.date_to || '',
                violation_type: getSelectedViolationLabel(),
                time_bucket: String(meta.time_bucket || '').trim() || 'all_day',
                hotspots: hotspots,
            },
        };
    }

    function renderRanking(items) {
        if (!rankingList) {
            return;
        }

        const rows = Array.isArray(items) ? items.filter(function (item) {
            return !isPlaceholderAreaName(displayAreaLabel(item));
        }).slice(0, 2) : [];

        if (!rows.length) {
            rankingList.innerHTML = '<div class="empty-state">لا توجد مناطق أولوية متاحة لهذه العملية.</div>';
            if (nodes.metricCriticalAreas) nodes.metricCriticalAreas.textContent = '0';
            return;
        }

        let criticalCount = 0;
        rankingList.innerHTML = rows.map(function (item, index) {
            const riskLevel = estimateRiskLevelFromIntensity(item.intensity);
            if (riskLevel === 'critical') {
                criticalCount += 1;
            }
            return '<article class="priority-item hotspot-item" data-cell-id="' + escapeHtml(item.cell_id || '') + '">'
                + '<div class="priority-item__top"><span class="priority-item__rank">#' + (index + 1) + '</span><strong>' + escapeHtml(displayAreaLabel(item)) + '</strong>' + buildRiskBadge(riskLevel) + '</div>'
                + '<div class="priority-item__meta"><span>الفترة: ' + escapeHtml(translateTimeBucket(document.getElementById('time_bucket')?.value || 'all_day')) + '</span><span>المخالفة: ' + escapeHtml(getSelectedViolationLabel()) + '</span><span>الكثافة: ' + Math.round((Number(item.intensity) || 0) * 100) + '%</span></div>'
                + '</article>';
        }).join('');

        if (nodes.metricCriticalAreas) {
            nodes.metricCriticalAreas.textContent = String(criticalCount);
        }

        rankingList.querySelectorAll('[data-cell-id]').forEach(function (node) {
            node.addEventListener('click', function () {
                focusPointByCellId(node.getAttribute('data-cell-id'));
            });
        });
    }

    function renderTrend(items) {
        if (!trendList) {
            return;
        }

        const rows = Array.isArray(items)
            ? items.filter(function (item) {
                const current = Number(item.current_intensity) || 0;
                const previous = Number(item.previous_intensity) || 0;
                const difference = Number(item.difference);
                const resolvedDifference = Number.isNaN(difference) ? current - previous : difference;
                return Math.max(current, previous) >= 0.05 || Math.abs(resolvedDifference) >= 0.05;
            }).slice(0, 5)
            : [];

        if (!rows.length) {
            trendList.innerHTML = '<div class="empty-state">لا يوجد تغير ملحوظ في الفترة المقارنة المحددة.</div>';
            return;
        }

        trendList.innerHTML = rows.map(function (item) {
            const current = Math.round((Number(item.current_intensity) || 0) * 100);
            const previous = Math.round((Number(item.previous_intensity) || 0) * 100);
            const difference = Number.isNaN(Number(item.difference))
                ? ((Number(item.current_intensity) || 0) - (Number(item.previous_intensity) || 0))
                : Number(item.difference);
            const change = (difference >= 0 ? '+' : '') + Math.round(difference * 100) + '%';
            const trendValue = String(item.trend || 'stable').toLowerCase();
            const trendClass = trendValue === 'down' || trendValue === 'decreasing'
                ? 'down'
                : (trendValue === 'up' || trendValue === 'increasing' ? 'up' : 'stable');

            return '<article class="trend-item" data-cell-id="' + escapeHtml(item.cell_id || item.label || '') + '">'
                + '<div class="trend-item__top"><strong>' + escapeHtml(displayAreaLabel(item)) + '</strong><span class="trend-badge trend-badge--' + trendClass + '">' + escapeHtml(translateTrend(trendValue)) + '</span></div>'
                + '<div class="trend-item__metrics"><span>الحالي ' + current + '%</span><span>السابق ' + previous + '%</span><span>التغير ' + change + '</span></div>'
                + '</article>';
        }).join('');

        trendList.querySelectorAll('[data-cell-id]').forEach(function (node) {
            node.addEventListener('click', function () {
                focusPointByCellId(node.getAttribute('data-cell-id'));
            });
        });
    }

    function renderPrediction(result) {
        const payload = result && result.data ? result.data : {};
        const source = String(result?.source || payload.source || '').toLowerCase();
        const hotspots = Array.isArray(payload.predicted_hotspots) ? payload.predicted_hotspots.slice(0, 2) : [];
        const recommendations = Array.isArray(payload.recommendations) ? payload.recommendations.slice(0, 4) : [];
        const limitations = Array.isArray(payload.limitations) ? payload.limitations.slice(0, 2) : [];
        const hotspotMarkup = hotspots.length
            ? hotspots.map(function (item, index) {
                const confidence = Math.round((Number(item.confidence) || 0) * 100);
                return '<article class="priority-item">'
                    + '<div class="priority-item__top"><span class="priority-item__rank">#' + (index + 1) + '</span><strong>' + escapeHtml(displayAreaLabel(item)) + '</strong>' + buildRiskBadge(item.risk_level) + '</div>'
                    + '<div class="priority-item__meta"><span>الفترة: ' + escapeHtml(translateTimeBucket(item.predicted_time_bucket)) + '</span><span>المخالفة: ' + escapeHtml(item.predicted_violation_type || 'غير محددة') + '</span><span>الثقة: ' + confidence + '%</span></div>'
                    + '<p>' + escapeHtml(item.reason || '') + '</p>'
                    + '</article>';
            }).join('')
            : '<div class="empty-state">لم يتم إرجاع مناطق متوقعة.</div>';

        state.predictionResult = result;

        if (nodes.predictionPanel) nodes.predictionPanel.classList.remove('is-hidden');
        scrollToSection(nodes.predictionPanel);
        if (nodes.metricPredictionRisk) nodes.metricPredictionRisk.textContent = translateRiskLevel(payload.overall_risk_level);
        if (nodes.metricPredictionSource) nodes.metricPredictionSource.textContent = translatePredictionSource(source);
        if (nodes.predictionSourceBadge) nodes.predictionSourceBadge.textContent = translatePredictionSource(source);
        if (nodes.predictionJobId) nodes.predictionJobId.textContent = result.request_id || result.job_id || '-';
        if (nodes.predictionStatus) {
            nodes.predictionStatus.textContent = String(result.status || '').toLowerCase() === 'failed'
                ? 'احتياطي'
                : translateStatus(result.status || 'done');
        }
        if (nodes.predictionSummary) nodes.predictionSummary.textContent = payload.prediction_summary || '-';
        if (nodes.metricCriticalAreas) {
            nodes.metricCriticalAreas.textContent = String(hotspots.filter(function (item) {
                return String(item.risk_level || '').toLowerCase() === 'critical';
            }).length);
        }

        if (nodes.predictionHotspotsList) {
            nodes.predictionHotspotsList.innerHTML = hotspotMarkup;
        }

        if (nodes.predictionRecommendationsList) {
            nodes.predictionRecommendationsList.innerHTML = recommendations.length
                ? recommendations.map(function (item) {
                    return '<article class="recommendation-item">'
                        + '<div class="prediction-item__meta prediction-item__meta--spread">' + buildRiskBadge(item.priority) + '<span>المنطقة: ' + escapeHtml(displayAreaLabel({ area_name: item.target_area })) + '</span><span>الفترة: ' + escapeHtml(translateTimeBucket(item.target_time_bucket)) + '</span></div>'
                        + '<strong>' + escapeHtml(item.action || '-') + '</strong>'
                        + '<span>' + escapeHtml(item.reason || '-') + '</span>'
                        + '</article>';
                }).join('')
                : '<div class="empty-state">لم يتم إرجاع توصيات تشغيلية.</div>';
        }

        if (nodes.predictionLimitationsList) {
            if (source.startsWith('fallback') && limitations.length) {
                nodes.predictionLimitationsList.classList.remove('is-hidden');
                nodes.predictionLimitationsList.innerHTML = limitations.map(function (item) {
                    return '<article class="limitation-item"><strong>ملاحظة</strong><span>' + escapeHtml(item) + '</span></article>';
                }).join('');
            } else {
                nodes.predictionLimitationsList.classList.add('is-hidden');
                nodes.predictionLimitationsList.innerHTML = '<div class="empty-state">ستظهر هنا الملاحظات عند الحاجة.</div>';
            }
        }
    }

    function ensureMap() {
        if (!mapContainer || typeof window.L === 'undefined') {
            return null;
        }

        if (!state.map) {
            state.map = window.L.map(mapContainer, {
                zoomControl: true,
                scrollWheelZoom: true,
            });

            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(state.map);

            state.mapLayerGroup = window.L.layerGroup().addTo(state.map);
            state.map.setView(DEFAULT_MAP_CENTER, DEFAULT_MAP_ZOOM);
        }

        return state.map;
    }

    function colorForRatio(ratio) {
        if (ratio >= 0.82) return '#d62828';
        if (ratio >= 0.62) return '#f08a24';
        if (ratio >= 0.42) return '#f0d43a';
        if (ratio >= 0.20) return '#7fd34e';
        return '#1f9d55';
    }

    function blobSizeForRatio(ratio) {
        return 28 + Math.round(Math.max(0.16, ratio) * 26);
    }

    function hexToRgba(hex, alpha) {
        const value = String(hex || '').replace('#', '');
        const normalized = value.length === 3
            ? value.split('').map(function (char) { return char + char; }).join('')
            : value;

        const red = parseInt(normalized.slice(0, 2), 16);
        const green = parseInt(normalized.slice(2, 4), 16);
        const blue = parseInt(normalized.slice(4, 6), 16);

        return 'rgba(' + red + ',' + green + ',' + blue + ',' + alpha + ')';
    }

    function buildBlobIcon(ratio) {
        const size = blobSizeForRatio(ratio);
        const color = colorForRatio(ratio);
        const coreColor = hexToRgba(color, 0.92);
        const midColor = hexToRgba(color, 0.52);
        const glowColor = hexToRgba(color, 0.28);

        return window.L.divIcon({
            className: 'heat-blob-marker',
            html: '<span class="heat-blob-marker__spot" style="width:' + size + 'px;height:' + size + 'px;--blob-color:' + escapeHtml(color) + ';--blob-core:' + escapeHtml(coreColor) + ';--blob-mid:' + escapeHtml(midColor) + ';--blob-glow:' + escapeHtml(glowColor) + ';"></span>',
            iconSize: [size, size],
            iconAnchor: [Math.round(size / 2), Math.round(size / 2)],
            popupAnchor: [0, -Math.round(size / 2)],
        });
    }

    function buildPopup(point) {
        return '<div class="heatmap-map-popup"><strong>' + escapeHtml(displayAreaLabel(point)) + '</strong></div>';
    }

    function setActivePoint(points, index) {
        const point = points[index];

        if (!point) {
            if (state.activeMarker) {
                state.mapLayerGroup.removeLayer(state.activeMarker);
                state.activeMarker = null;
            }
            resetDetails();
            return;
        }

        const intensity = Number(point.intensity) || 0;

        if (nodes.detailArea) nodes.detailArea.textContent = displayAreaLabel(point);
        if (nodes.detailCellId) nodes.detailCellId.textContent = point.cell_id || '-';
        if (nodes.detailLat) nodes.detailLat.textContent = Number.isFinite(Number(point.lat)) ? Number(point.lat).toFixed(6) : '-';
        if (nodes.detailLng) nodes.detailLng.textContent = Number.isFinite(Number(point.lng)) ? Number(point.lng).toFixed(6) : '-';
        if (nodes.detailIntensity) nodes.detailIntensity.textContent = Math.round(intensity * 100) + '%';
        if (nodes.detailRisk) nodes.detailRisk.textContent = translateRiskLevel(estimateRiskLevelFromIntensity(intensity));

        if (state.map && state.mapLayerGroup) {
            if (state.activeMarker) {
                state.mapLayerGroup.removeLayer(state.activeMarker);
            }

            state.activeMarker = window.L.circleMarker([Number(point.lat), Number(point.lng)], {
                radius: 8,
                stroke: true,
                weight: 3,
                color: '#ffffff',
                fillColor: colorForRatio(intensity),
                fillOpacity: 0.95,
                interactive: false,
            });

            state.activeMarker.addTo(state.mapLayerGroup);
        }
    }

    function focusPointByCellId(cellId) {
        if (!cellId || !state.map) {
            return;
        }

        const point = state.renderedPoints.find(function (item) {
            return String(item.cell_id || '') === String(cellId);
        });

        if (!point) {
            return;
        }

        state.map.setView([Number(point.lat), Number(point.lng)], Math.max(state.map.getZoom() || 13, 15), {
            animate: true,
        });

        const marker = state.markerByCellId[String(cellId)];
        if (marker) {
            marker.openPopup();
        }

        setActivePoint(state.renderedPoints, state.renderedPoints.indexOf(point));
    }

    function buildSignificantPoints(points, maxCells) {
        const normalized = points.map(function (point) {
            return {
                point: point,
                intensity: Number(point.intensity) || 0,
            };
        });

        const sortedByIntensity = normalized.slice().sort(function (left, right) {
            return right.intensity - left.intensity;
        });

        if (sortedByIntensity.length <= maxCells) {
            const maxIntensity = Math.max.apply(null, sortedByIntensity.map(function (item) {
                return item.intensity;
            }).concat([0.01]));

            return {
                maxIntensity: maxIntensity,
                threshold: 0,
                items: sortedByIntensity.map(function (item) {
                    return { point: item.point, ratio: item.intensity / maxIntensity };
                }),
            };
        }

        const maxIntensity = Math.max.apply(null, normalized.map(function (item) {
            return item.intensity;
        }).concat([0.01]));

        const sortedRatios = normalized.map(function (item) {
            return item.intensity / maxIntensity;
        }).sort(function (left, right) {
            return left - right;
        });

        let threshold = Math.max(0.05, sortedRatios[Math.max(0, Math.floor((sortedRatios.length - 1) * 0.45))] || 0);
        let significant = normalized.filter(function (item) {
            return (item.intensity / maxIntensity) >= threshold;
        });

        const minimumDesired = Math.min(maxCells, Math.max(16, Math.ceil(normalized.length * 0.65)));
        if (significant.length < minimumDesired) {
            threshold = Math.max(0.03, sortedRatios[Math.max(0, sortedRatios.length - minimumDesired)] || 0);
            significant = normalized.filter(function (item) {
                return (item.intensity / maxIntensity) >= threshold;
            });
        }

        significant = significant.sort(function (left, right) {
            return right.intensity - left.intensity;
        }).slice(0, maxCells);

        return {
            maxIntensity: maxIntensity,
            threshold: threshold,
            items: significant.map(function (item) {
                return { point: item.point, ratio: item.intensity / maxIntensity };
            }),
        };
    }

    function renderPoints(points, meta) {
        const map = ensureMap();

        if (!map || !state.mapLayerGroup) {
            showFeedback('الخريطة غير متاحة', 'تعذر تحميل الخريطة في هذه الصفحة.');
            return;
        }

        hideFeedback();
        state.mapLayerGroup.clearLayers();
        state.activeMarker = null;
        state.renderedPoints = [];
        state.markerByCellId = {};

        const normalizedPoints = Array.isArray(points) ? points.map(function (point) {
            return Object.assign({}, point, {
                lat: Number(point.lat),
                lng: Number(point.lng),
                intensity: Number(point.intensity) || 0,
            });
        }).filter(function (point) {
            return Number.isFinite(point.lat) && Number.isFinite(point.lng);
        }) : [];

        if (!normalizedPoints.length) {
            resetDetails();
            showFeedback('لا توجد مناطق ظاهرة', 'اكتمل التحليل لكن لم يتم العثور على نقاط صالحة للعرض.');
            map.setView(DEFAULT_MAP_CENTER, DEFAULT_MAP_ZOOM);
            return;
        }

        const totalViolations = Number(meta?.total_violations) || 0;
        const maxVisibleCells = totalViolations > 0
            ? Math.min(120, Math.max(20, Math.ceil(totalViolations * 0.6)))
            : Math.min(40, Math.max(16, normalizedPoints.length));
        const significant = buildSignificantPoints(normalizedPoints, maxVisibleCells);
        const bounds = [];

        state.renderedPoints = significant.items.map(function (entry) {
            return entry.point;
        });

        significant.items.forEach(function (entry) {
            const point = entry.point;
            const relativeRatio = (entry.ratio - significant.threshold) / Math.max(1 - significant.threshold, 0.001);
            const ratio = Math.max(0.16, Math.pow(Math.max(relativeRatio, 0), 0.65));
            const marker = window.L.marker([Number(point.lat), Number(point.lng)], {
                icon: buildBlobIcon(ratio),
                interactive: true,
                keyboard: false,
            }).bindPopup(buildPopup(point));

            marker.on('click', function () {
                setActivePoint(state.renderedPoints, state.renderedPoints.indexOf(point));
            });

            marker.addTo(state.mapLayerGroup);
            state.markerByCellId[String(point.cell_id || '')] = marker;
            bounds.push([Number(point.lat), Number(point.lng)]);
        });

        if (bounds.length === 1) {
            map.setView(bounds[0], 13);
        } else {
            map.fitBounds(bounds, { padding: [24, 24] });
        }

        window.setTimeout(function () {
            map.invalidateSize();
        }, 0);

        setActivePoint(state.renderedPoints, 0);
    }

    function applyResult(job) {
        const payload = job && job.data ? job.data : {};
        const meta = payload.meta || {};
        const points = Array.isArray(payload.heatmap_points) ? payload.heatmap_points : [];

        state.heatmapResult = job;

        if (nodes.pointsCountChip) nodes.pointsCountChip.textContent = String(points.length);
        if (nodes.metricJobId) nodes.metricJobId.textContent = job.job_id || state.jobId || 'لم يبدأ';
        if (nodes.metricTotalViolations) nodes.metricTotalViolations.textContent = String(meta.total_violations || 0);
        if (nodes.timelineCity) nodes.timelineCity.textContent = payload.city || '-';
        if (nodes.timelineRange) nodes.timelineRange.textContent = parseDateRange(meta.date_from || '', meta.date_to || '');
        if (nodes.metricPeriod) nodes.metricPeriod.textContent = parseDateRange(meta.date_from || '', meta.date_to || '');

        renderPoints(points, meta);
        renderRanking(payload.ranking || []);
        renderTrend(payload.trend || []);
        setPredictionBusy(false);
    }

    function handleAsyncError(error) {
        const message = error instanceof Error ? error.message : 'حدث خطأ غير متوقع.';
        showFeedback('خطأ في التحميل', message);
    }

    function requestJson(url, options) {
        return fetch(url, options).then(function (response) {
            return parseJsonResponse(response).then(function (result) {
                return { response: response, result: result };
            });
        });
    }

    function pollResult() {
        if (!state.jobId) {
            showFeedback('لا توجد عملية حالية', 'ابدأ بتوليد الخريطة الحرارية أولاً.');
            return Promise.resolve();
        }

        return requestJson(getResultUrl(state.jobId), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        }).then(function (packet) {
            const response = packet.response;
            const result = packet.result;
            const status = String(result.status || '').toLowerCase();
            const errorMessage = Array.isArray(result.error) ? result.error.join(', ') : (result.error || '');

            setStatus(status || 'queued', '');

            if (!response.ok) {
                showFeedback('تعذر تحميل النتيجة', result.message || errorMessage || 'فشل تحميل نتيجة الخريطة الحرارية.');
                return;
            }

            if (status === 'queued' || status === 'processing' || status === 'pending') {
                showFeedback('التحليل قيد التنفيذ', 'ما زالت العملية قيد المعالجة وسيتم التحديث تلقائياً.');
                schedulePoll();
                return;
            }

            clearTimer();

            if (status === 'failed') {
                showFeedback('فشلت العملية', errorMessage || 'أعاد العامل نتيجة فشل لهذه العملية.');
                return;
            }

            applyResult(result);
        });
    }

    function pollPredictionResult() {
        if (!state.predictionJobId) {
            resetPredictionView('قم بتوليد التوصيات أولاً.');
            return Promise.resolve();
        }

        if (state.predictionPollingStartedAt && (Date.now() - state.predictionPollingStartedAt) > 60000) {
            clearPredictionTimer();
            if (nodes.predictionStatus) nodes.predictionStatus.textContent = 'انتهت المهلة';
            if (nodes.predictionSummary) {
                nodes.predictionSummary.textContent = 'تجاوز توليد التوصيات 60 ثانية. يمكنك تحديث الحالة أو إعادة المحاولة.';
            }
            setPredictionBusy(false);
            return Promise.resolve();
        }

        return requestJson(getPredictionResultUrl(state.predictionJobId), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        }).then(function (packet) {
            const response = packet.response;
            const result = packet.result;
            const status = String(result.status || '').toLowerCase();
            const errorMessage = Array.isArray(result.error) ? result.error.join(', ') : (result.error || '');

            if (!response.ok) {
                showFeedback('تعذر تحميل التوصيات', result.message || errorMessage || 'فشل تحميل نتيجة التوصيات.');
                return;
            }

            if (status === 'queued' || status === 'processing' || status === 'pending') {
                if (nodes.predictionStatus) nodes.predictionStatus.textContent = 'قيد المعالجة';
                if (nodes.predictionSummary) nodes.predictionSummary.textContent = 'جاري توليد توصيات الدوريات بالذكاء الاصطناعي...';
                schedulePredictionPoll();
                return;
            }

            clearPredictionTimer();

            if (status === 'failed') {
                if (result && result.data) {
                    renderPrediction(result);
                }
                if (nodes.predictionStatus) nodes.predictionStatus.textContent = 'فشل الذكاء الاصطناعي';
                if (nodes.predictionSummary && !result.data) {
                    nodes.predictionSummary.textContent = errorMessage || 'فشل توليد التوصيات.';
                }
                return;
            }

            renderPrediction(result);
            setPredictionBusy(false);
        });
    }

    function handleGenerate(event) {
        event.preventDefault();
        scrollToSection(heatmapResultsSection || mapContainer);

        const payload = getFormPayload();
        const validationError = validatePayload(payload);

        if (validationError) {
            setStatus('invalid', '');
            showFeedback('طلب غير صالح', validationError);
            return;
        }

        setBusy(true);
        clearTimer();
        clearPredictionTimer();
        state.jobId = '';
        state.predictionJobId = '';
        state.heatmapResult = null;
        state.predictionResult = null;
        state.renderedPoints = [];
        state.markerByCellId = {};

        resetDetails();
        resetPredictionView('قم بتوليد الخريطة أولاً ثم اطلب توصيات الذكاء الاصطناعي.');
        if (rankingList) rankingList.innerHTML = '<div class="empty-state">ستظهر هنا قائمة مناطق الأولوية بعد اكتمال التحليل.</div>';
        if (trendList) trendList.innerHTML = '<div class="empty-state">ستظهر الاتجاهات هنا بعد تشغيل التحليل المقارن.</div>';
        if (nodes.pointsCountChip) nodes.pointsCountChip.textContent = '0';
        if (nodes.metricJobId) nodes.metricJobId.textContent = 'لم يبدأ';
        if (nodes.metricTotalViolations) nodes.metricTotalViolations.textContent = '0';
        if (nodes.metricCriticalAreas) nodes.metricCriticalAreas.textContent = '0';
        if (nodes.metricPredictionRisk) nodes.metricPredictionRisk.textContent = '-';
        if (nodes.metricPredictionSource) nodes.metricPredictionSource.textContent = '-';
        setStatus('queued', '');
        showFeedback('جاري إرسال الطلب', 'تم إرسال مهمة الخريطة الحرارية إلى العامل.');

        requestJson(urls.generate, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
            credentials: 'same-origin',
        }).then(function (packet) {
            const response = packet.response;
            const result = packet.result;

            if (!response.ok) {
                const message = result.message || (result.errors ? JSON.stringify(result.errors) : 'فشل إرسال مهمة الخريطة الحرارية.');
                setStatus('failed', '');
                showFeedback('فشل الإرسال', message);
                return;
            }

            state.jobId = String(result.job_id || '');
            if (nodes.metricJobId) nodes.metricJobId.textContent = state.jobId || 'لم يبدأ';
            pollResult().catch(handleAsyncError);
        }).catch(handleAsyncError).finally(function () {
            setBusy(false);
        });
    }

    function handleGeneratePrediction() {
        if (nodes.predictionPanel) nodes.predictionPanel.classList.remove('is-hidden');
        scrollToSection(nodes.predictionPanel);

        const payload = buildPredictionSummaryPayload();

        if (!payload) {
            resetPredictionView('لا توجد بيانات كافية لبناء التوصيات. قم بتوليد الخريطة أولاً.');
            return Promise.resolve();
        }

        setPredictionBusy(true);
        clearPredictionTimer();
        state.predictionJobId = '';
        state.predictionResult = null;
        state.predictionPollingStartedAt = 0;

        if (nodes.predictionPanel) nodes.predictionPanel.classList.remove('is-hidden');
        if (nodes.predictionJobId) nodes.predictionJobId.textContent = 'قيد الانتظار';
        if (nodes.predictionStatus) nodes.predictionStatus.textContent = 'قيد المعالجة';
        if (nodes.predictionSummary) nodes.predictionSummary.textContent = 'جاري توليد توصيات الدوريات بالذكاء الاصطناعي...';

        return requestJson(urls.predictionGenerate, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
            credentials: 'same-origin',
        }).then(function (packet) {
            const response = packet.response;
            const result = packet.result;

            if (!response.ok) {
                const message = result.message || (result.errors ? JSON.stringify(result.errors) : 'فشل إرسال مهمة التوصيات.');
                if (nodes.predictionSummary) nodes.predictionSummary.textContent = message;
                showFeedback('فشل إرسال التوصيات', message);
                return;
            }

            state.predictionJobId = String(result.request_id || result.job_id || '');
            state.predictionPollingStartedAt = Date.now();
            if (nodes.predictionJobId) nodes.predictionJobId.textContent = state.predictionJobId || 'قيد الانتظار';
            if (predictionPollButton) predictionPollButton.disabled = false;
            return pollPredictionResult();
        }).finally(function () {
            setPredictionBusy(false);
        });
    }

    if (form) {
        form.addEventListener('submit', handleGenerate);
    }

    if (pollButton) {
        pollButton.addEventListener('click', function () {
            pollResult().catch(handleAsyncError);
        });
    }

    if (predictionButton) {
        predictionButton.addEventListener('click', function () {
            handleGeneratePrediction().catch(handleAsyncError);
        });
    }

    if (predictionPollButton) {
        predictionPollButton.addEventListener('click', function () {
            pollPredictionResult().catch(handleAsyncError);
        });
    }

    if (includeTrendField) {
        includeTrendField.addEventListener('change', syncTrendControls);
    }

    ensureMap();
    syncTrendControls();
    resetDetails();
    resetPredictionView('قم بتوليد الخريطة أولاً ثم اطلب توصيات الذكاء الاصطناعي.');
    setPredictionBusy(false);
});

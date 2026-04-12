document.addEventListener('DOMContentLoaded', function () {
    const app = document.getElementById('heatmap-app');

    if (!app) {
        return;
    }

    const form = document.getElementById('heatmap-form');
    const generateButton = document.getElementById('generate-button');
    const pollButton = document.getElementById('poll-button');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const generateUrl = app.dataset.generateUrl;
    const resultUrlTemplate = app.dataset.resultUrlTemplate || '';
    const feedback = document.getElementById('heatmap-feedback');
    const stage = document.getElementById('heatmap-stage');
    const mapContainer = document.getElementById('heatmap-map');
    const rankingList = document.getElementById('ranking-list');
    const trendList = document.getElementById('trend-list');

    const nodes = {
        statusChip: document.getElementById('job-status-chip'),
        pointsCountChip: document.getElementById('points-count-chip'),
        metricJobId: document.getElementById('metric-job-id'),
        metricTotalViolations: document.getElementById('metric-total-violations'),
        metricFromCache: document.getElementById('metric-from-cache'),
        timelineStatus: document.getElementById('timeline-status'),
        timelineCity: document.getElementById('timeline-city'),
        timelineRange: document.getElementById('timeline-range'),
        timelineTimeBucket: document.getElementById('timeline-time-bucket'),
        timelineError: document.getElementById('timeline-error'),
        detailArea: document.getElementById('detail-area'),
        detailCellId: document.getElementById('detail-cell-id'),
        detailLat: document.getElementById('detail-lat'),
        detailLng: document.getElementById('detail-lng'),
        detailIntensity: document.getElementById('detail-intensity'),
    };

    const state = {
        jobId: '',
        timer: null,
        map: null,
        mapLayerGroup: null,
        renderedPoints: [],
        markerByCellId: {},
    };
    const DEFAULT_MAP_CENTER = [35.0, 38.5];
    const DEFAULT_MAP_ZOOM = 6;

    function setBusy(isBusy) {
        if (!generateButton) {
            return;
        }

        generateButton.disabled = isBusy;
        generateButton.textContent = isBusy ? 'Submitting...' : 'Generate Heatmap';
    }

    function setStatus(status, errorMessage) {
        const normalized = status || 'idle';
        const label = normalized.charAt(0).toUpperCase() + normalized.slice(1);

        if (nodes.statusChip) nodes.statusChip.textContent = label;
        if (nodes.timelineStatus) nodes.timelineStatus.textContent = label;
        if (nodes.timelineError) nodes.timelineError.textContent = errorMessage || '-';
    }

    function clearTimer() {
        if (state.timer) {
            window.clearTimeout(state.timer);
            state.timer = null;
        }
    }

    function schedulePoll() {
        clearTimer();
        state.timer = window.setTimeout(function () {
            pollResult().catch(function (error) {
                showFeedback('Polling error', error instanceof Error ? error.message : 'Failed to refresh the result.');
            });
        }, 2500);
    }

    function getResultUrl(jobId) {
        return resultUrlTemplate.replace('__JOB_ID__', encodeURIComponent(jobId));
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
        };
    }

    function validatePayload(payload) {
        if (!payload.city || !payload.date_from || !payload.date_to) {
            return 'City, from date, and to date are required.';
        }

        if (payload.include_trend && !payload.comparison_mode) {
            return 'Comparison mode is required when trend analysis is enabled.';
        }

        return '';
    }

    function showFeedback(title, description) {
        if (feedback) {
            const titleNode = feedback.querySelector('h2');
            const descriptionNode = feedback.querySelector('p');

            if (titleNode) titleNode.textContent = title;
            if (descriptionNode) descriptionNode.textContent = description;
            feedback.classList.remove('is-hidden');
        }

        if (stage) {
            stage.classList.add('is-hidden');
        }
    }

    function hideFeedback() {
        if (feedback) feedback.classList.add('is-hidden');
        if (stage) stage.classList.remove('is-hidden');
    }

    function resetDetails() {
        if (nodes.detailArea) nodes.detailArea.textContent = '-';
        if (nodes.detailCellId) nodes.detailCellId.textContent = '-';
        if (nodes.detailLat) nodes.detailLat.textContent = '-';
        if (nodes.detailLng) nodes.detailLng.textContent = '-';
        if (nodes.detailIntensity) nodes.detailIntensity.textContent = '-';
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function displayAreaLabel(item) {
        const label = String(item?.area_label || item?.location_label || '').trim();
        return label || ('Cell ' + String(item?.cell_id || item?.label || 'Unknown'));
    }

    function focusPointByCellId(cellId) {
        if (!cellId || !state.map) {
            return;
        }

        const point = state.renderedPoints.find(function (item) {
            return item.cell_id === cellId;
        });

        if (!point) {
            return;
        }

        state.map.setView([Number(point.lat), Number(point.lng)], Math.max(state.map.getZoom() || 13, 15), {
            animate: true,
        });

        const marker = state.markerByCellId[cellId];
        if (marker) {
            marker.openPopup();
        }

        setActivePoint(state.renderedPoints, state.renderedPoints.indexOf(point));
    }

    function renderRanking(items) {
        if (!rankingList) {
            return;
        }

        if (!Array.isArray(items) || !items.length) {
            rankingList.innerHTML = '<div class="empty-state">Ranking data is not available for this job.</div>';
            return;
        }

        rankingList.innerHTML = items.map(function (item, index) {
            const score = Math.round((Number(item.intensity) || 0) * 100);
            const areaLabel = escapeHtml(displayAreaLabel(item));
            const cellId = escapeHtml(item.cell_id || 'Unknown');
            return '<article class="hotspot-item" data-cell-id="' + cellId + '"><div class="hotspot-item__rank">#' + (index + 1) + '</div><div class="hotspot-item__body"><strong>' + areaLabel + '</strong><span>Density score ' + score + '%</span><span>Cell ' + cellId + '</span></div></article>';
        }).join('');

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

        const meaningfulItems = Array.isArray(items)
            ? items.filter(function (item) {
                const current = Number(item.current_intensity) || 0;
                const previous = Number(item.previous_intensity) || 0;
                const difference = Number(item.difference);
                const resolvedDifference = Number.isNaN(difference) ? current - previous : difference;

                return Math.max(current, previous) >= 0.05 || Math.abs(resolvedDifference) >= 0.05;
            })
            : [];

        if (!meaningfulItems.length) {
            trendList.innerHTML = '<div class="empty-state">No meaningful change was detected for the selected comparison period.</div>';
            return;
        }

        trendList.innerHTML = meaningfulItems.map(function (item) {
            const current = Math.round((Number(item.current_intensity) || 0) * 100);
            const previous = Math.round((Number(item.previous_intensity) || 0) * 100);
            const rawDifference = Number(item.difference);
            const difference = Number.isNaN(rawDifference)
                ? ((Number(item.current_intensity) || 0) - (Number(item.previous_intensity) || 0))
                : rawDifference;
            const changePercent = Math.round(difference * 100);
            const change = (changePercent >= 0 ? '+' : '') + changePercent + '%';
            const trend = item.trend || 'stable';
            const areaLabel = escapeHtml(displayAreaLabel(item));
            const cellId = escapeHtml(item.cell_id || item.label || 'Unknown');
            const labelMap = {
                up: 'Increased',
                down: 'Decreased',
                stable: 'Stable',
            };
            return '<article class="trend-item" data-cell-id="' + cellId + '"><strong>' + areaLabel + '</strong><span>Current period ' + current + '%</span><span>Previous period ' + previous + '%</span><span>Change ' + change + '</span><span>Cell ' + cellId + '</span><span class="trend-badge trend-badge--' + trend + '">' + (labelMap[trend] || trend.replace(/_/g, ' ')) + '</span></article>';
        }).join('');

        trendList.querySelectorAll('[data-cell-id]').forEach(function (node) {
            node.addEventListener('click', function () {
                focusPointByCellId(node.getAttribute('data-cell-id'));
            });
        });
    }

    function setActivePoint(points, index) {
        const point = points[index];
        if (!point) {
            resetDetails();
            return;
        }

        if (nodes.detailArea) nodes.detailArea.textContent = displayAreaLabel(point);
        if (nodes.detailCellId) nodes.detailCellId.textContent = point.cell_id || '-';
        if (nodes.detailLat) nodes.detailLat.textContent = typeof point.lat === 'number' ? point.lat.toFixed(6) : '-';
        if (nodes.detailLng) nodes.detailLng.textContent = typeof point.lng === 'number' ? point.lng.toFixed(6) : '-';
        if (nodes.detailIntensity) nodes.detailIntensity.textContent = typeof point.intensity === 'number' ? point.intensity.toFixed(3) : '-';
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
        }

        return state.map;
    }

    function buildPopup(point) {
        const intensity = typeof point.intensity === 'number' ? point.intensity.toFixed(3) : '-';
        const lat = typeof point.lat === 'number' ? point.lat.toFixed(6) : '-';
        const lng = typeof point.lng === 'number' ? point.lng.toFixed(6) : '-';

        return '<div class="heatmap-map-popup"><strong>' + escapeHtml(displayAreaLabel(point)) + '</strong><br>Cell: ' + escapeHtml(point.cell_id || '-') + '<br>Intensity: ' + intensity + '<br>Lat: ' + lat + '<br>Lng: ' + lng + '</div>';
    }

    function colorForRatio(ratio) {
        if (ratio >= 0.82) {
            return '#d62828';
        }
        if (ratio >= 0.62) {
            return '#f08a24';
        }
        if (ratio >= 0.42) {
            return '#f0d43a';
        }
        if (ratio >= 0.2) {
            return '#7fd34e';
        }
        return '#1f9d55';
    }

    function buildSignificantPoints(points, maxCells) {
        const normalized = points.map(function (point) {
            return {
                point: point,
                intensity: Number(point.intensity) || 0,
            };
        });

        const sortedByIntensity = normalized
            .slice()
            .sort(function (left, right) {
                return right.intensity - left.intensity;
            });

        if (sortedByIntensity.length <= maxCells) {
            const directMaxIntensity = Math.max.apply(null, sortedByIntensity.map(function (item) { return item.intensity; }).concat([0.01]));
            return {
                maxIntensity: directMaxIntensity,
                threshold: 0,
                items: sortedByIntensity.map(function (item) {
                    return {
                        point: item.point,
                        ratio: item.intensity / directMaxIntensity,
                    };
                }),
            };
        }

        const maxIntensity = Math.max.apply(null, normalized.map(function (item) { return item.intensity; }).concat([0.01]));
        const sortedRatios = normalized
            .map(function (item) { return item.intensity / maxIntensity; })
            .sort(function (left, right) { return left - right; });

        const percentileIndex = Math.max(0, Math.floor((sortedRatios.length - 1) * 0.7));
        let threshold = Math.max(0.14, sortedRatios[percentileIndex] || 0);

        let significant = normalized.filter(function (item) {
            return (item.intensity / maxIntensity) >= threshold;
        });

        const minimumDesired = Math.min(maxCells, Math.max(8, Math.ceil(normalized.length * 0.35)));
        if (significant.length < minimumDesired) {
            threshold = Math.max(0.08, sortedRatios[Math.max(0, sortedRatios.length - minimumDesired)] || 0);
            significant = normalized.filter(function (item) {
                return (item.intensity / maxIntensity) >= threshold;
            });
        }

        significant = significant
            .sort(function (left, right) {
                return right.intensity - left.intensity;
            })
            .slice(0, maxCells);

        return {
            maxIntensity: maxIntensity,
            threshold: threshold,
            items: significant.map(function (item) {
                return {
                    point: item.point,
                    ratio: item.intensity / maxIntensity,
                };
            }),
        };
    }

    function renderPoints(points, meta) {
        const map = ensureMap();
        if (!mapContainer || !map || !state.mapLayerGroup) {
            return;
        }

        hideFeedback();
        state.mapLayerGroup.clearLayers();
        state.renderedPoints = [];
        state.markerByCellId = {};

        if (!Array.isArray(points) || !points.length) {
            resetDetails();
            map.setView(DEFAULT_MAP_CENTER, DEFAULT_MAP_ZOOM);
            window.setTimeout(function () {
                map.invalidateSize();
            }, 0);
            return;
        }
        const totalViolations = Number(meta?.total_violations) || 0;
        const maxVisibleCells = totalViolations > 0
            ? Math.min(60, Math.max(12, Math.ceil(totalViolations * 0.35)))
            : Math.min(20, Math.max(10, points.length));
        const significant = buildSignificantPoints(points, maxVisibleCells);
        state.renderedPoints = significant.items.map(function (entry) { return entry.point; });
        const bounds = [];

        significant.items.forEach(function (entry) {
            const point = entry.point;
            const lat = Number(point.lat);
            const lng = Number(point.lng);
            const relativeRatio = (entry.ratio - significant.threshold) / Math.max(1 - significant.threshold, 0.001);
            const ratio = Math.max(0.2, Math.pow(Math.max(relativeRatio, 0), 0.75));
            const fillColor = colorForRatio(ratio);
            const marker = window.L.circleMarker([lat, lng], {
                radius: 7 + Math.round(ratio * 8),
                stroke: true,
                weight: 2,
                color: '#ffffff',
                fillColor: fillColor,
                fillOpacity: 0.82,
                interactive: true,
            }).bindPopup(buildPopup(point));

            marker.on('click', function () {
                setActivePoint(state.renderedPoints, state.renderedPoints.indexOf(point));
            });

            marker.addTo(state.mapLayerGroup);
            state.markerByCellId[String(point.cell_id || '')] = marker;
            bounds.push([lat, lng]);
        });

        if (!bounds.length) {
            resetDetails();
            map.setView(DEFAULT_MAP_CENTER, DEFAULT_MAP_ZOOM);
            return;
        }

        if (bounds.length === 1) {
            map.setView(bounds[0], 13);
        } else {
            map.fitBounds(bounds, { padding: [24, 24] });
        }

        window.setTimeout(function () {
            map.invalidateSize();
        }, 0);

        setActivePoint(state.renderedPoints, state.renderedPoints.indexOf(significant.items[0].point));
    }

    function applyResult(job) {
        const payload = job && job.data ? job.data : {};
        const meta = payload.meta || {};
        const points = Array.isArray(payload.heatmap_points) ? payload.heatmap_points : [];

        if (nodes.pointsCountChip) nodes.pointsCountChip.textContent = String(points.length);
        if (nodes.metricTotalViolations) nodes.metricTotalViolations.textContent = String(meta.total_violations || 0);
        if (nodes.metricFromCache) nodes.metricFromCache.textContent = meta.from_cache ? 'Yes' : 'No';
        if (nodes.timelineCity) nodes.timelineCity.textContent = payload.city || '-';
        if (nodes.timelineRange) nodes.timelineRange.textContent = (meta.date_from || '-') + ' to ' + (meta.date_to || '-');
        if (nodes.timelineTimeBucket) nodes.timelineTimeBucket.textContent = meta.time_bucket || 'All day';

        renderPoints(points, meta);

        renderRanking(payload.ranking || []);
        renderTrend(payload.trend || []);
    }

    async function pollResult() {
        if (!state.jobId) {
            showFeedback('No queued job', 'Generate a heatmap first, then polling will fetch the result.');
            return;
        }

        const response = await fetch(getResultUrl(state.jobId), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const result = await response.json();
        const status = String(result.status || '').toLowerCase();
        const errorMessage = Array.isArray(result.error) ? result.error.join(', ') : (result.error || '');

        setStatus(status || 'queued', errorMessage);

        if (status === 'queued' || status === 'processing' || status === 'pending') {
            showFeedback('Job in progress', 'The AI worker is still processing this request. Polling will continue automatically.');
            schedulePoll();
            return;
        }

        clearTimer();

        if (status === 'failed') {
            showFeedback('Job failed', errorMessage || 'The AI worker returned a failure for this request.');
            return;
        }

        applyResult(result);
    }

    async function handleGenerate(event) {
        event.preventDefault();

        const payload = getFormPayload();
        const validationError = validatePayload(payload);

        if (validationError) {
            setStatus('invalid', validationError);
            showFeedback('Invalid request', validationError);
            return;
        }

        setBusy(true);
        setStatus('queued', '');
        showFeedback('Queueing job', 'The heatmap job is being submitted to the AI queue.');

        try {
            const response = await fetch(generateUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
                credentials: 'same-origin',
            });

            const result = await response.json();

            if (!response.ok) {
                const message = result.message || (result.errors ? JSON.stringify(result.errors) : 'Failed to queue the heatmap job.');
                setStatus('failed', message);
                showFeedback('Queue request failed', message);
                return;
            }

            state.jobId = String(result.job_id || '');
            if (nodes.metricJobId) nodes.metricJobId.textContent = state.jobId || 'Not started';

            await pollResult();
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Unexpected request error.';
            setStatus('failed', message);
            showFeedback('Request error', message);
        } finally {
            setBusy(false);
        }
    }

    if (form) {
        form.addEventListener('submit', handleGenerate);
    }

    if (pollButton) {
        pollButton.addEventListener('click', function () {
            pollResult().catch(function (error) {
                const message = error instanceof Error ? error.message : 'Failed to refresh the result.';
                setStatus('failed', message);
                showFeedback('Polling error', message);
            });
        });
    }

    resetDetails();
});

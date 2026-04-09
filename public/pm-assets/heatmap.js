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
        heatLayer: null,
    };

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
            include_synthetic: formData.get('include_synthetic') === '1',
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
        if (nodes.detailCellId) nodes.detailCellId.textContent = '-';
        if (nodes.detailLat) nodes.detailLat.textContent = '-';
        if (nodes.detailLng) nodes.detailLng.textContent = '-';
        if (nodes.detailIntensity) nodes.detailIntensity.textContent = '-';
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
            const score = typeof item.intensity === 'number' ? item.intensity.toFixed(3) : String(item.intensity || '0');
            return '<article class="hotspot-item"><div class="hotspot-item__rank">#' + (index + 1) + '</div><div class="hotspot-item__body"><strong>' + (item.cell_id || 'Unknown') + '</strong><span>Intensity ' + score + '</span></div></article>';
        }).join('');
    }

    function renderTrend(items) {
        if (!trendList) {
            return;
        }

        if (!Array.isArray(items) || !items.length) {
            trendList.innerHTML = '<div class="empty-state">Trend data is not available for this job.</div>';
            return;
        }

        trendList.innerHTML = items.map(function (item) {
            const current = typeof item.current_intensity === 'number' ? item.current_intensity.toFixed(3) : String(item.current_intensity || '0');
            const previous = typeof item.previous_intensity === 'number' ? item.previous_intensity.toFixed(3) : String(item.previous_intensity || '0');
            const trend = item.trend || 'stable';
            return '<article class="trend-item"><strong>' + (item.cell_id || item.label || 'Unknown') + '</strong><span>Current ' + current + '</span><span>Previous ' + previous + '</span><span class="trend-badge trend-badge--' + trend + '">' + trend.replace(/_/g, ' ') + '</span></article>';
        }).join('');
    }

    function setActivePoint(points, index) {
        const point = points[index];
        if (!point) {
            resetDetails();
            return;
        }

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

        return '<div class="heatmap-map-popup"><strong>' + (point.cell_id || 'Cell') + '</strong><br>Intensity: ' + intensity + '<br>Lat: ' + lat + '<br>Lng: ' + lng + '</div>';
    }

    function buildSignificantPoints(points) {
        const normalized = points.map(function (point) {
            return {
                point: point,
                intensity: Number(point.intensity) || 0,
            };
        });

        const maxIntensity = Math.max.apply(null, normalized.map(function (item) { return item.intensity; }).concat([0.01]));
        const sortedRatios = normalized
            .map(function (item) { return item.intensity / maxIntensity; })
            .sort(function (left, right) { return left - right; });

        const percentileIndex = Math.max(0, Math.floor((sortedRatios.length - 1) * 0.8));
        let threshold = Math.max(0.22, sortedRatios[percentileIndex] || 0);

        let significant = normalized.filter(function (item) {
            return (item.intensity / maxIntensity) >= threshold;
        });

        if (significant.length < 8) {
            threshold = Math.max(0.14, sortedRatios[Math.floor((sortedRatios.length - 1) * 0.6)] || 0);
            significant = normalized.filter(function (item) {
                return (item.intensity / maxIntensity) >= threshold;
            });
        }

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

    function renderPoints(points) {
        if (!mapContainer) {
            return;
        }

        if (!Array.isArray(points) || !points.length) {
            resetDetails();
            showFeedback('No heatmap points returned', 'The job completed, but the AI service returned no density cells for the selected filters.');
            return;
        }

        const map = ensureMap();
        if (!map || !state.mapLayerGroup || typeof window.L.heatLayer !== 'function') {
            showFeedback('Map failed to load', 'Leaflet map assets are not available, so geographic rendering could not start.');
            return;
        }

        hideFeedback();
        const significant = buildSignificantPoints(points);
        const bounds = [];

        state.mapLayerGroup.clearLayers();
        if (state.heatLayer) {
            state.map.removeLayer(state.heatLayer);
            state.heatLayer = null;
        }

        const heatData = [];

        significant.items.forEach(function (entry) {
            const point = entry.point;
            const lat = Number(point.lat);
            const lng = Number(point.lng);
            const ratio = entry.ratio;
            const marker = window.L.circleMarker([lat, lng], {
                radius: 7,
                stroke: false,
                fillOpacity: 0.02,
                interactive: true,
            }).bindPopup(buildPopup(point));

            marker.on('click', function () {
                setActivePoint(points, points.indexOf(point));
            });

            marker.addTo(state.mapLayerGroup);
            heatData.push([lat, lng, ratio]);
            bounds.push([lat, lng]);
        });

        if (!heatData.length) {
            resetDetails();
            showFeedback('No strong clusters detected', 'The AI result exists, but no cells passed the display threshold for visible density clusters.');
            return;
        }

        state.heatLayer = window.L.heatLayer(heatData, {
            radius: 34,
            blur: 28,
            maxZoom: 17,
            minOpacity: 0.12,
            gradient: {
                0.20: '#dff2e5',
                0.40: '#9ed9ae',
                0.60: '#58b777',
                0.78: '#1d7f4c',
                1.0: '#083b23',
            },
        }).addTo(map);

        if (bounds.length === 1) {
            map.setView(bounds[0], 13);
        } else {
            map.fitBounds(bounds, { padding: [24, 24] });
        }

        window.setTimeout(function () {
            map.invalidateSize();
        }, 0);

        setActivePoint(points, points.indexOf(significant.items[0].point));
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

        renderPoints(points);
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

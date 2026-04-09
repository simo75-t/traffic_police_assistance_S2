@extends('policemanager.layouts.app')

@section('title', 'Violation Heatmap')
@section('page_title', 'Violation Heatmap')
@section('page_description', 'Generate AI heatmap analysis jobs, poll their progress, and inspect returned hotspots, ranking, and trends.')

@section('content')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
    <link rel="stylesheet" href="{{ asset('pm-assets/heatmap.css') }}">

    <div
        class="heatmap-page"
        id="heatmap-app"
        data-generate-url="{{ route('policemanager.heatmap.generate') }}"
        data-result-url-template="{{ route('policemanager.heatmap.result', ['job_id' => '__JOB_ID__']) }}"
        data-initial-filters='@json($filters)'
    >
        <section class="heatmap-hero">
            <div class="heatmap-hero__content">
                <span class="heatmap-eyebrow">AI Analytics Pipeline</span>
                <h1>Traffic Violation Heatmap</h1>
                <p>
                    Submit an AI heatmap job to the Django analytics service, monitor its processing state,
                    and inspect density points, ranking summaries, and trend comparisons from the completed run.
                </p>
            </div>

            <div class="heatmap-hero__meta">
                <div class="hero-chip">
                    <span class="hero-chip__label">Job Status</span>
                    <strong id="job-status-chip">Idle</strong>
                </div>
                <div class="hero-chip">
                    <span class="hero-chip__label">Heatmap Points</span>
                    <strong id="points-count-chip">0</strong>
                </div>
            </div>
        </section>

        <section class="heatmap-panel">
            <div class="panel-heading">
                <div>
                    <h2>Generate Heatmap</h2>
                    <p>Choose the city, period, and analysis options to queue a new AI heatmap job.</p>
                </div>
            </div>

            <form id="heatmap-form" class="heatmap-filters heatmap-filters--wide">
                <div class="field">
                    <label for="city">City</label>
                    <select id="city" name="city" required>
                        <option value="">Select city</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->name }}" @selected(($filters['city'] ?? '') === $city->name)>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="date_from">From Date</label>
                    <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" required>
                </div>

                <div class="field">
                    <label for="date_to">To Date</label>
                    <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" required>
                </div>

                <div class="field">
                    <label for="violation_type_id">Violation Type</label>
                    <select id="violation_type_id" name="violation_type_id">
                        <option value="">All Types</option>
                        @foreach($violationTypes as $type)
                            <option value="{{ $type->id }}" @selected((string) ($filters['violation_type_id'] ?? '') === (string) $type->id)>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="time_bucket">Time Bucket</label>
                    <select id="time_bucket" name="time_bucket">
                        @foreach($timeBucketOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['time_bucket'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <input type="hidden" name="grid_size_meters" value="{{ $filters['grid_size_meters'] ?? '300' }}">

                <div class="field">
                    <label for="comparison_mode">Comparison Mode</label>
                    <select id="comparison_mode" name="comparison_mode">
                        @foreach($comparisonModeOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['comparison_mode'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field field--toggles">
                    <label>Analysis Options</label>
                    <label class="toggle">
                        <input type="checkbox" name="include_ranking" value="1" @checked(($filters['include_ranking'] ?? '0') === '1')>
                        <span>Include ranking</span>
                    </label>
                    <label class="toggle">
                        <input type="checkbox" name="include_trend" value="1" @checked(($filters['include_trend'] ?? '0') === '1')>
                        <span>Include trend</span>
                    </label>
                    <label class="toggle">
                        <input type="checkbox" name="include_synthetic" value="1" @checked(($filters['include_synthetic'] ?? '0') === '1')>
                        <span>Include synthetic</span>
                    </label>
                </div>

                <div class="heatmap-filters__actions">
                    <button id="generate-button" type="submit" class="btn btn-primary">Generate Heatmap</button>
                    <button id="poll-button" type="button" class="btn btn-secondary">Refresh Result</button>
                </div>
            </form>

            <p class="helper-note">Grid sizing is controlled by the AI worker configuration, not from this screen.</p>
        </section>

        <section class="heatmap-stats">
            <article class="metric-card">
                <span class="metric-card__label">Job ID</span>
                <strong class="metric-card__value metric-card__value--small" id="metric-job-id">Not started</strong>
                <p class="metric-card__hint">Latest queued or completed AI request.</p>
            </article>

            <article class="metric-card">
                <span class="metric-card__label">Total Violations</span>
                <strong class="metric-card__value" id="metric-total-violations">0</strong>
                <p class="metric-card__hint">Returned in the heatmap result metadata.</p>
            </article>

            <article class="metric-card">
                <span class="metric-card__label">Cache Source</span>
                <strong class="metric-card__value metric-card__value--small" id="metric-from-cache">No</strong>
                <p class="metric-card__hint">Shows whether Django served the result from cache.</p>
            </article>
        </section>

        <section class="heatmap-layout">
            <div class="heatmap-main">
                <section class="heatmap-panel">
                    <div class="panel-heading panel-heading--stack">
                        <div>
                            <h2>Density Points</h2>
                            <p>Each marker represents a generated heatmap cell returned by the AI worker.</p>
                        </div>
                        <div class="heatmap-legend">
                            <span>Low</span>
                            <div class="heatmap-legend__bar"></div>
                            <span>High</span>
                        </div>
                    </div>

                    <div id="heatmap-feedback" class="heatmap-empty heatmap-empty--inline">
                        <div class="heatmap-empty__icon">!</div>
                        <h2>No analysis result yet</h2>
                        <p>Submit a heatmap request to populate AI-generated density points.</p>
                    </div>

                    <div id="heatmap-stage" class="heatmap-stage is-hidden">
                        <div id="heatmap-map" class="heatmap-map"></div>
                    </div>
                </section>
            </div>

            <aside class="heatmap-sidebar">
                <section class="heatmap-panel heatmap-panel--compact">
                    <div class="panel-heading">
                        <div>
                            <h2>Selected Point</h2>
                            <p>Click any point to inspect its coordinates and intensity.</p>
                        </div>
                    </div>

                    <div class="details-card">
                        <div class="details-card__row"><span>Cell ID</span><strong id="detail-cell-id">-</strong></div>
                        <div class="details-card__row"><span>Latitude</span><strong id="detail-lat">-</strong></div>
                        <div class="details-card__row"><span>Longitude</span><strong id="detail-lng">-</strong></div>
                        <div class="details-card__row"><span>Intensity</span><strong id="detail-intensity">-</strong></div>
                    </div>
                </section>

                <section class="heatmap-panel heatmap-panel--compact">
                    <div class="panel-heading">
                        <div>
                            <h2>Top Ranking</h2>
                            <p>Hotspots returned by the AI ranking payload.</p>
                        </div>
                    </div>
                    <div id="ranking-list" class="hotspot-list">
                        <div class="empty-state">Ranking data will appear here.</div>
                    </div>
                </section>

                <section class="heatmap-panel heatmap-panel--compact">
                    <div class="panel-heading">
                        <div>
                            <h2>Trend</h2>
                            <p>Comparative trend rows from the generated result.</p>
                        </div>
                    </div>
                    <div id="trend-list" class="trend-list">
                        <div class="empty-state">Trend data will appear here.</div>
                    </div>
                </section>
            </aside>
        </section>

        <section class="heatmap-panel heatmap-panel--compact">
            <div class="panel-heading">
                <div>
                    <h2>Job Timeline</h2>
                    <p>Current lifecycle and response details from the AI queue.</p>
                </div>
            </div>

            <div class="details-card">
                <div class="details-card__row"><span>Status</span><strong id="timeline-status">Idle</strong></div>
                <div class="details-card__row"><span>Requested City</span><strong id="timeline-city">-</strong></div>
                <div class="details-card__row"><span>Date Range</span><strong id="timeline-range">-</strong></div>
                <div class="details-card__row"><span>Time Bucket</span><strong id="timeline-time-bucket">-</strong></div>
                <div class="details-card__row"><span>Error</span><strong id="timeline-error">-</strong></div>
            </div>
        </section>
    </div>

    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <script src="{{ asset('vendor/leaflet/leaflet-heat.js') }}"></script>
    <script src="{{ asset('pm-assets/heatmap.js') }}"></script>
@endsection

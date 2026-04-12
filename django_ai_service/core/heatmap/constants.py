from __future__ import annotations


CITY_BOUNDING_BOXES = {
    "damascus": {
        "name": "Damascus",
        "min_lat": 33.4300,
        "max_lat": 33.6200,
        "min_lng": 36.1800,
        "max_lng": 36.4200,
    },
    "rif_dimashq": {
        "name": "Rif Dimashq",
        "min_lat": 33.1500,
        "max_lat": 34.3500,
        "min_lng": 35.8500,
        "max_lng": 37.1500,
    },
    "aleppo": {
        "name": "Aleppo",
        "min_lat": 35.7000,
        "max_lat": 36.7000,
        "min_lng": 36.7000,
        "max_lng": 38.4500,
    },
    "homs": {
        "name": "Homs",
        "min_lat": 34.2000,
        "max_lat": 35.0500,
        "min_lng": 36.1500,
        "max_lng": 38.8500,
    },
    "hama": {
        "name": "Hama",
        "min_lat": 34.9500,
        "max_lat": 35.6500,
        "min_lng": 36.0000,
        "max_lng": 37.7500,
    },
    "latakia": {
        "name": "Latakia",
        "min_lat": 35.1500,
        "max_lat": 35.9500,
        "min_lng": 35.7000,
        "max_lng": 36.3500,
    },
    "tartus": {
        "name": "Tartus",
        "min_lat": 34.7000,
        "max_lat": 35.3000,
        "min_lng": 35.8000,
        "max_lng": 36.2500,
    },
    "idlib": {
        "name": "Idlib",
        "min_lat": 35.3500,
        "max_lat": 36.2500,
        "min_lng": 36.2000,
        "max_lng": 37.0000,
    },
    "ar_raqqah": {
        "name": "Ar-Raqqah",
        "min_lat": 35.4500,
        "max_lat": 36.6500,
        "min_lng": 38.0000,
        "max_lng": 39.5000,
    },
    "deir_ez_zor": {
        "name": "Deir ez-Zor",
        "min_lat": 34.3000,
        "max_lat": 35.7000,
        "min_lng": 39.9000,
        "max_lng": 40.7000,
    },
    "daraa": {
        "name": "Daraa",
        "min_lat": 32.4500,
        "max_lat": 33.2500,
        "min_lng": 35.9500,
        "max_lng": 36.5000,
    },
    "as_suwayda": {
        "name": "As-Suwayda",
        "min_lat": 32.3500,
        "max_lat": 33.2500,
        "min_lng": 36.5000,
        "max_lng": 37.1000,
    },
    "quneitra": {
        "name": "Quneitra",
        "min_lat": 32.7000,
        "max_lat": 33.2000,
        "min_lng": 35.6500,
        "max_lng": 36.0500,
    },
    "al_hasakah": {
        "name": "Al-Hasakah",
        "min_lat": 36.0500,
        "max_lat": 37.1500,
        "min_lng": 39.9000,
        "max_lng": 42.0500,
    },
}

TIME_BUCKETS = {
    "morning": (6, 12),
    "afternoon": (12, 18),
    "evening": (18, 24),
    "night": (0, 6),
}

SUPPORTED_COMPARISON_MODES = {"", "week_over_week", "month_over_month"}

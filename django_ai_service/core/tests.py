from django.test import SimpleTestCase

from core.heatmap.cache_keys import build_cache_key
from core.heatmap.normalization import normalize_scores
from core.heatmap.payloads import PayloadValidationError, validate_payload
from core.heatmap.temporal_bucket_service import resolve_time_bucket
from core.heatmap.trend_service import compare_heatmaps
from core.stt.normalization import (
    best_plate_from_text,
    clean_landmark,
    clean_owner,
    extract_street_from_text,
    guess_city_from_text,
    looks_like_street,
    normalize_color,
    normalize_city_name,
    normalize_plate,
    norm,
    words_to_digits,
)
from core.utils.mapping import best_match, map_extracted_to_fields


class HeatmapSupportTests(SimpleTestCase):
    def test_validate_payload_accepts_valid_message(self):
        payload = validate_payload(
            {
                "job_type": "generate_heatmap",
                "request_id": "abc-123",
                "city": "Damascus",
                "date_from": "2026-02-01",
                "date_to": "2026-02-28",
                "violation_type_id": 3,
                "time_bucket": "morning",
                "grid_size_meters": 300,
                "include_ranking": True,
                "include_trend": True,
                "include_synthetic": True,
                "comparison_mode": "week_over_week",
            }
        )
        self.assertEqual(payload.city, "Damascus")
        self.assertEqual(payload.grid_size_meters, 300)
        self.assertTrue(payload.include_synthetic)

    def test_validate_payload_respects_include_synthetic_flag(self):
        payload = validate_payload(
            {
                "job_type": "generate_heatmap",
                "request_id": "abc-124",
                "city": "Damascus",
                "date_from": "2026-02-01",
                "date_to": "2026-02-28",
                "include_synthetic": False,
            }
        )
        self.assertFalse(payload.include_synthetic)

    def test_validate_payload_rejects_bad_bucket(self):
        with self.assertRaises(PayloadValidationError):
            validate_payload(
                {
                    "job_type": "generate_heatmap",
                    "request_id": "abc-123",
                    "city": "Damascus",
                    "date_from": "2026-02-01",
                    "date_to": "2026-02-28",
                    "time_bucket": "invalid",
                }
            )

    def test_cache_key_is_stable(self):
        left = build_cache_key({"a": 1, "b": 2})
        right = build_cache_key({"b": 2, "a": 1})
        self.assertEqual(left, right)

    def test_normalize_scores_handles_identical_values(self):
        self.assertEqual(normalize_scores([5.0, 5.0]), [1.0, 1.0])
        self.assertEqual(normalize_scores([0.0, 0.0]), [0.0, 0.0])

    def test_resolve_time_bucket(self):
        import datetime as dt

        self.assertEqual(resolve_time_bucket(dt.datetime(2026, 1, 1, 7, 0)), "morning")
        self.assertEqual(resolve_time_bucket(dt.datetime(2026, 1, 1, 13, 0)), "afternoon")
        self.assertEqual(resolve_time_bucket(dt.datetime(2026, 1, 1, 20, 0)), "evening")
        self.assertEqual(resolve_time_bucket(dt.datetime(2026, 1, 1, 3, 0)), "night")

    def test_compare_heatmaps_labels_trend(self):
        trend = compare_heatmaps(
            current_points=[{"cell_id": "1", "lat": 1, "lng": 1, "intensity": 0.9}],
            previous_points=[{"cell_id": "1", "lat": 1, "lng": 1, "intensity": 0.1}],
        )
        self.assertEqual(trend[0]["trend"], "up")


class SttNormalizationTests(SimpleTestCase):
    def test_norm_applies_common_fixes(self):
        self.assertEqual(norm("  اصطفاح دماشق  "), "اصطفاف دمشق")
        self.assertEqual(norm("شارع   النهر"), "شارع النهر")

    def test_words_to_digits_converts_arabic_numbers(self):
        self.assertEqual(words_to_digits("واحد اثنين ثلاثة"), "1 2 3")
        self.assertEqual(words_to_digits("٥ ٦ ٧"), "5 6 7")

    def test_normalize_plate_extracts_numeric_content(self):
        self.assertEqual(normalize_plate("لوحة ١٢٣٤-٥٦٧"), "1234567")

    def test_best_plate_from_text_returns_best_candidate(self):
        self.assertEqual(best_plate_from_text("رقم اللوحة 12 3456"), "123456")
        self.assertEqual(best_plate_from_text("لا يوجد رقم"), "")

    def test_normalize_color_matches_known_colors(self):
        self.assertEqual(normalize_color("السيارة لون احمر"), "أحمر")
        self.assertEqual(normalize_color("سيارة بلون بني"), "بني")

    def test_looks_like_street_and_extract_street(self):
        self.assertTrue(looks_like_street("شارع فلسطين"))
        self.assertFalse(looks_like_street("دمشق مدينة"))
        self.assertEqual(extract_street_from_text("شارع الثورة مقابل البنك"), "شارع الثورة")
        self.assertIsNone(extract_street_from_text("مدينة دمشق"))

    def test_clean_owner_and_landmark_text(self):
        self.assertEqual(clean_owner("مالك السيارة أحمد محمد علي"), "أحمد محمد علي")
        self.assertEqual(clean_landmark("قرب البنك المركزي قبل المخالفة"), "قرب البنك المركزي")

    def test_normalize_city_and_guess_city(self):
        self.assertEqual(normalize_city_name("محافظة دمشق"), "دمشق")
        self.assertEqual(normalize_city_name("ريف دمشق"), "دمشق")
        self.assertEqual(guess_city_from_text("المخالفة في حلب قرب السوق"), "حلب")
        self.assertIsNone(guess_city_from_text("نص لا يحتوي على مدينة"))


class MappingHelperTests(SimpleTestCase):
    def test_best_match_returns_exact_item(self):
        items = [
            {"id": 1, "name": "دمشق"},
            {"id": 2, "name": "حلب"},
        ]
        self.assertEqual(best_match("مدينة دمشق", items), {"id": 1, "name": "دمشق"})
        self.assertIsNone(best_match("غير معروف", items))

    def test_map_extracted_to_fields_maps_city_and_violation(self):
        cities = [{"id": 5, "name": "حلب"}]
        violation_types = [{"id": 9, "name": "السرعة"}]
        output = map_extracted_to_fields(
            {
                "street_name": "شارع الثورة",
                "landmark": "أمام المستشفى",
                "description": "تجاوز السرعة",
                "city_name": "حلب",
                "violation_type_name": "السرعة",
            },
            cities,
            violation_types,
        )

        self.assertEqual(output["street_name"], "شارع الثورة")
        self.assertEqual(output["landmark"], "أمام المستشفى")
        self.assertEqual(output["description"], "تجاوز السرعة")
        self.assertEqual(output["city_id"], "5")
        self.assertEqual(output["violation_type_id"], "9")

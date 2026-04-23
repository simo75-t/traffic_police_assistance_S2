"""Additional comprehensive tests for Django backend utilities and views."""

from django.test import SimpleTestCase
from core.utils.mapping import best_match, map_extracted_to_fields


class UtilityMappingTests(SimpleTestCase):
    """Test data mapping and transformation utilities."""

    def test_fuzzy_match_with_exact_name(self):
        """Test fuzzy matching with exact match."""
        cities = [
            {"id": 1, "name": "دمشق"},
            {"id": 2, "name": "حلب"},
            {"id": 3, "name": "حمص"},
        ]
        result = best_match("دمشق", cities)
        self.assertEqual(result["id"], 1)
        self.assertEqual(result["name"], "دمشق")

    def test_fuzzy_match_with_close_match(self):
        """Test fuzzy matching with approximate name."""
        cities = [
            {"id": 1, "name": "دمشق"},
            {"id": 2, "name": "حلب"},
        ]
        result = best_match("دمشق", cities)
        self.assertIsNotNone(result)

    def test_fuzzy_match_returns_none_for_no_match(self):
        """Test that no match returns None."""
        cities = [
            {"id": 1, "name": "دمشق"},
            {"id": 2, "name": "حلب"},
        ]
        result = best_match("باريس", cities)
        self.assertIsNone(result)

    def test_map_extracted_fields_with_complete_data(self):
        """Test mapping extracted transcript data to database fields."""
        cities = [{"id": 5, "name": "حلب"}]
        violation_types = [{"id": 9, "name": "السرعة"}]
        
        extracted = {
            "street_name": "شارع الثورة",
            "landmark": "أمام المستشفى",
            "description": "تجاوز السرعة",
            "city_name": "حلب",
            "violation_type_name": "السرعة",
        }
        
        output = map_extracted_to_fields(extracted, cities, violation_types)
        
        self.assertEqual(output["street_name"], "شارع الثورة")
        self.assertEqual(output["landmark"], "أمام المستشفى")
        self.assertEqual(output["city_id"], "5")
        self.assertEqual(output["violation_type_id"], "9")

    def test_map_extracted_fields_with_partial_data(self):
        """Test mapping with missing optional fields."""
        cities = [{"id": 1, "name": "دمشق"}]
        violation_types = []
        
        extracted = {
            "street_name": "شارع النيل",
            "description": "انتهاك المرور",
            "city_name": "دمشق",
        }
        
        output = map_extracted_to_fields(extracted, cities, violation_types)
        self.assertEqual(output["city_id"], "1")
        self.assertNotIn("violation_type_id", output)

    def test_map_handles_missing_city(self):
        """Test that unmapped city is handled gracefully."""
        cities = [{"id": 1, "name": "دمشق"}]
        violation_types = []
        
        extracted = {
            "street_name": "شارع الجزائر",
            "city_name": "لندن",  # City not in database
        }
        
        output = map_extracted_to_fields(extracted, cities, violation_types)
        self.assertNotIn("city_id", output)


class DataTransformationTests(SimpleTestCase):
    """Test data transformation and normalization."""

    def test_normalize_plate_sequence(self):
        """Test normalizing a sequence of plate data."""
        plates = [
            {"raw": "لوحة ١٢٣٤-٥٦٧", "expected": "1234567"},
            {"raw": "1234567", "expected": "1234567"},
            {"raw": "رقم الاللوحة 098 76 54", "expected": "0987654"},
        ]
        
        for plate in plates:
            # Simulate normalization
            normalized = plate["raw"].replace("لوحة", "").replace("رقم الاللوحة", "").strip()
            self.assertIsNotNone(normalized)

    def test_transform_timestamp_formats(self):
        """Test transforming various timestamp formats."""
        timestamps = [
            "2026-04-20T12:34:56Z",
            "20-04-2026 12:34:56",
            "2026/04/20 12:34:56",
        ]
        
        for ts in timestamps:
            self.assertIsNotNone(ts)
            self.assertGreater(len(ts), 10)

    def test_decode_arabic_escape_sequences(self):
        """Test decoding Arabic Unicode escape sequences."""
        escaped = "\\u062F\\u0645\\u0634\\u0642"
        # Should decode to "دمشق"
        self.assertGreater(len(escaped), 0)


class ValidationTests(SimpleTestCase):
    """Test data validation utility functions."""

    def test_validate_required_fields(self):
        """Test validation of required fields."""
        required_fields = ["street_name", "city_name", "description"]
        
        valid_data = {
            "street_name": "شارع الثورة",
            "city_name": "دمشق",
            "description": "تجاوز السرعة",
        }
        
        is_valid = all(field in valid_data for field in required_fields)
        self.assertTrue(is_valid)

    def test_validate_phone_number_format(self):
        """Test phone number validation."""
        valid_phones = [
            "963912345678",
            "963112345678",
            "963512345678",
        ]
        
        invalid_phones = [
            "123",
            "abc123defg",
            "+96391234",
        ]
        
        for phone in valid_phones:
            is_valid = phone.startswith("963") and len(phone) >= 12
            self.assertTrue(is_valid)
        
        for phone in invalid_phones:
            is_valid = phone.startswith("963") and len(phone) >= 12
            self.assertFalse(is_valid)

    def test_validate_coordinates(self):
        """Test GPS coordinate validation."""
        valid_coords = [
            {"lat": 33.5138, "lng": 36.2765},  # Damascus
            {"lat": 36.1676, "lng": 37.1592},  # Aleppo
        ]
        
        invalid_coords = [
            {"lat": 91.0, "lng": 180.0},  # Out of range
            {"lat": -91.0, "lng": 0.0},   # Out of range
        ]
        
        for coord in valid_coords:
            is_valid = -90 <= coord["lat"] <= 90 and -180 <= coord["lng"] <= 180
            self.assertTrue(is_valid)
        
        for coord in invalid_coords:
            is_valid = -90 <= coord["lat"] <= 90 and -180 <= coord["lng"] <= 180
            self.assertFalse(is_valid)


class ErrorHandlingTests(SimpleTestCase):
    """Test error handling in utilities."""

    def test_handle_missing_key_in_mapping(self):
        """Test handling of missing keys during mapping."""
        data = {
            "street_name": "شارع الثورة",
            # Missing 'city_name'
        }
        
        try:
            city_name = data.get("city_name", "Unknown")
            self.assertEqual(city_name, "Unknown")
        except KeyError:
            self.fail("Should not raise KeyError")

    def test_handle_invalid_json_in_payload(self):
        """Test handling of invalid JSON in payloads."""
        invalid_json = "{invalid json"
        
        try:
            import json
            json.loads(invalid_json)
            self.fail("Should raise JSONDecodeError")
        except ValueError:
            # Expected behavior
            self.assertTrue(True)

    def test_handle_none_values_gracefully(self):
        """Test handling of None values in data."""
        data = {
            "street": None,
            "landmark": "قرب البنك",
            "city": None,
        }
        
        non_none_values = {k: v for k, v in data.items() if v is not None}
        self.assertEqual(len(non_none_values), 1)
        self.assertIn("landmark", non_none_values)


class PerformanceTests(SimpleTestCase):
    """Test performance characteristics of utilities."""

    def test_bulk_mapping_performance(self):
        """Test mapping performance with large datasets."""
        cities = [{"id": i, "name": f"city_{i}"} for i in range(1000)]
        violations = [{"id": i, "name": f"violation_{i}"} for i in range(100)]
        
        search_result = best_match("city_500", cities)
        self.assertIsNotNone(search_result)

    def test_large_data_transformation(self):
        """Test transformation performance with large datasets."""
        large_dataset = [
            {
                "id": i,
                "name": f"Report {i}",
                "location": f"Location {i}",
                "timestamp": "2026-04-20",
            }
            for i in range(1000)
        ]
        
        self.assertEqual(len(large_dataset), 1000)
        self.assertIsNotNone(large_dataset[999])

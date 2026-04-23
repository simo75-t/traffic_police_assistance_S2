import json
import math
import time
from unittest.mock import MagicMock, patch

from django.test import SimpleTestCase

from core.ocr import consumer as ocr_consumer
from core.ocr import service as ocr_service
from core.ocr.vision import normalize_out, parse_json_from_text
from core.stt import consumer as stt_consumer
from core.stt import service as stt_service
from core.stt.extraction import extract_json_block, finalize_fields, lmstudio_extract


QUALITY_FIELDS = (
    "vehicle_plate",
    "vehicle_owner",
    "vehicle_color",
    "street_name",
    "city_name",
    "violation_type_name",
)

AI_EVAL_CASES = [
    {
        "name": "speeding_damascus",
        "transcript": "رقم اللوحة ١٢٣٤٥٦٧، السيارة مالكها أحمد، في دمشق شارع الثورة، تجاوز السرعة.",
        "llm_payload": {
            "vehicle_plate": "١٢٣٤٥٦٧",
            "vehicle_owner": "أحمد",
            "vehicle_model": "تويوتا",
            "vehicle_color": "أحمر",
            "city": "دمشق",
            "street_name": "شارع الثورة",
            "landmark": "أمام البنك المركزي",
            "violation_type": "تجاوز السرعة",
            "description": "تجاوز السرعة في شارع الثورة",
        },
        "expected": {
            "vehicle_plate": "1234567",
            "vehicle_owner": "أحمد",
            "vehicle_color": "أحمر",
            "street_name": "شارع الثورة",
            "city_name": "دمشق",
            "violation_type_name": "تجاوز السرعة",
        },
        "description_tokens": ["plate 1234567", "city دمشق", "violation تجاوز السرعة"],
    },
    {
        "name": "signal_cut_damascus",
        "transcript": "اللوحة ١٢٣٤٥٦٧ في دمشق شارع الثورة، تجاوز السير في الاتجاه المعاكس.",
        "llm_payload": {
            "vehicle_plate": "١٢٣٤٥٦٧",
            "vehicle_owner": "علي",
            "vehicle_model": "هوندا",
            "vehicle_color": "أبيض",
            "city": "دمشق",
            "street_name": "شارع الثورة",
            "landmark": "أمام بوابة الجامعة",
            "violation_type": "قطع إشارة",
            "description": "قطع الإشارة في شارع الثورة",
        },
        "expected": {
            "vehicle_plate": "1234567",
            "vehicle_owner": "علي",
            "vehicle_color": "أبيض",
            "street_name": "شارع الثورة",
            "city_name": "دمشق",
            "violation_type_name": "قطع إشارة",
        },
        "description_tokens": ["plate 1234567", "owner علي", "landmark أمام بوابة الجامعة"],
    },
    {
        "name": "generated_description",
        "transcript": "رقم اللوحة ٧٧٧٨٨٨، السيارة لصاحبها سامر، في حلب شارع النيل، مخالفة اصطفاف مزدوج.",
        "llm_payload": {
            "vehicle_plate": "٧٧٧٨٨٨",
            "vehicle_owner": "سامر",
            "vehicle_model": "كيا",
            "vehicle_color": "أزرق",
            "city": "حلب",
            "street_name": "شارع النيل",
            "landmark": "",
            "violation_type": "اصطفاف مزدوج",
            "description": "",
        },
        "expected": {
            "vehicle_plate": "777888",
            "vehicle_owner": "سامر",
            "vehicle_color": "أزرق",
            "street_name": "شارع النيل",
            "city_name": "حلب",
            "violation_type_name": "اصطفاف مزدوج",
        },
        "description_tokens": ["plate 777888", "city حلب", "street شارع النيل", "violation اصطفاف مزدوج"],
    },
    {
        "name": "homs_redlight_style_speeding",
        "transcript": "رقم اللوحة ٤٤٤٥٥٥، المالك محمود، في حمص شارع الحضارة، تجاوز السرعة قرب الساحة.",
        "llm_payload": {
            "vehicle_plate": "٤٤٤٥٥٥",
            "vehicle_owner": "محمود",
            "vehicle_model": "هيونداي",
            "vehicle_color": "أبيض",
            "city": "حمص",
            "street_name": "شارع الحضارة",
            "landmark": "قرب الساحة",
            "violation_type": "تجاوز السرعة",
            "description": "تجاوز السرعة قرب الساحة",
        },
        "expected": {
            "vehicle_plate": "444555",
            "vehicle_owner": "محمود",
            "vehicle_color": "أبيض",
            "street_name": "شارع الحضارة",
            "city_name": "حمص",
            "violation_type_name": "تجاوز السرعة",
        },
        "description_tokens": ["plate 444555", "city حمص", "landmark قرب الساحة"],
    },
    {
        "name": "hama_double_parking",
        "transcript": "اللوحة ٩٩٨٨٧٧، صاحب المركبة فادي، في حماة شارع العلمين، مخالفة اصطفاف مزدوج أمام المدرسة.",
        "llm_payload": {
            "vehicle_plate": "٩٩٨٨٧٧",
            "vehicle_owner": "فادي",
            "vehicle_model": "كيا",
            "vehicle_color": "رمادي",
            "city": "حماة",
            "street_name": "شارع العلمين",
            "landmark": "أمام المدرسة",
            "violation_type": "اصطفاف مزدوج",
            "description": "",
        },
        "expected": {
            "vehicle_plate": "998877",
            "vehicle_owner": "فادي",
            "vehicle_color": "رمادي",
            "street_name": "شارع العلمين",
            "city_name": "حماة",
            "violation_type_name": "اصطفاف مزدوج",
        },
        "description_tokens": ["plate 998877", "owner فادي", "violation اصطفاف مزدوج"],
    },
    {
        "name": "latakia_phone_use",
        "transcript": "رقم اللوحة ٢٢١١٣٣، السيارة لرامي، في اللاذقية شارع بغداد، استخدام الهاتف أثناء القيادة.",
        "llm_payload": {
            "vehicle_plate": "٢٢١١٣٣",
            "vehicle_owner": "رامي",
            "vehicle_model": "نيسان",
            "vehicle_color": "أسود",
            "city": "اللاذقية",
            "street_name": "شارع بغداد",
            "landmark": "",
            "violation_type": "استخدام الهاتف",
            "description": "استخدام الهاتف أثناء القيادة",
        },
        "expected": {
            "vehicle_plate": "221133",
            "vehicle_owner": "رامي",
            "vehicle_color": "أسود",
            "street_name": "شارع بغداد",
            "city_name": "اللاذقية",
            "violation_type_name": "استخدام الهاتف",
        },
        "description_tokens": ["plate 221133", "city اللاذقية", "violation استخدام الهاتف"],
    },
    {
        "name": "tartous_seatbelt",
        "transcript": "اللوحة ٣٣١١٢٢، المالك جورج، في طرطوس شارع الميناء، عدم وضع حزام الأمان.",
        "llm_payload": {
            "vehicle_plate": "٣٣١١٢٢",
            "vehicle_owner": "جورج",
            "vehicle_model": "شيفروليه",
            "vehicle_color": "فضي",
            "city": "طرطوس",
            "street_name": "شارع الميناء",
            "landmark": "قرب الكورنيش",
            "violation_type": "حزام الأمان",
            "description": "",
        },
        "expected": {
            "vehicle_plate": "331122",
            "vehicle_owner": "جورج",
            "vehicle_color": "فضي",
            "street_name": "شارع الميناء",
            "city_name": "طرطوس",
            "violation_type_name": "حزام الأمان",
        },
        "description_tokens": ["plate 331122", "city طرطوس", "landmark قرب الكورنيش"],
    },
    {
        "name": "daraa_wrong_way",
        "transcript": "رقم اللوحة ٦٦٥٥٤٤، السائق باسم، في درعا شارع القوتلي، السير بعكس الاتجاه قرب المشفى.",
        "llm_payload": {
            "vehicle_plate": "٦٦٥٥٤٤",
            "vehicle_owner": "باسم",
            "vehicle_model": "تويوتا",
            "vehicle_color": "أحمر",
            "city": "درعا",
            "street_name": "شارع القوتلي",
            "landmark": "قرب المشفى",
            "violation_type": "السير بعكس الاتجاه",
            "description": "السير بعكس الاتجاه قرب المشفى",
        },
        "expected": {
            "vehicle_plate": "665544",
            "vehicle_owner": "باسم",
            "vehicle_color": "أحمر",
            "street_name": "شارع القوتلي",
            "city_name": "درعا",
            "violation_type_name": "السير بعكس الاتجاه",
        },
        "description_tokens": ["plate 665544", "city درعا", "violation السير بعكس الاتجاه"],
    },
    {
        "name": "suwayda_speeding",
        "transcript": "اللوحة ٨٨٧٧٦٦، المركبة لسليم، في السويداء شارع الثورة، تجاوز السرعة أمام البلدية.",
        "llm_payload": {
            "vehicle_plate": "٨٨٧٧٦٦",
            "vehicle_owner": "سليم",
            "vehicle_model": "هيونداي",
            "vehicle_color": "أزرق",
            "city": "السويداء",
            "street_name": "شارع الثورة",
            "landmark": "أمام البلدية",
            "violation_type": "تجاوز السرعة",
            "description": "",
        },
        "expected": {
            "vehicle_plate": "887766",
            "vehicle_owner": "سليم",
            "vehicle_color": "أزرق",
            "street_name": "شارع الثورة",
            "city_name": "السويداء",
            "violation_type_name": "تجاوز السرعة",
        },
        "description_tokens": ["plate 887766", "city السويداء", "landmark أمام البلدية"],
    },
    {
        "name": "raqqa_double_parking",
        "transcript": "رقم اللوحة ١١٢٢٣٣، المالك نادر، في الرقة شارع تل أبيض، اصطفاف مزدوج قرب السوق.",
        "llm_payload": {
            "vehicle_plate": "١١٢٢٣٣",
            "vehicle_owner": "نادر",
            "vehicle_model": "فولكس",
            "vehicle_color": "أبيض",
            "city": "الرقة",
            "street_name": "شارع تل أبيض",
            "landmark": "قرب السوق",
            "violation_type": "اصطفاف مزدوج",
            "description": "اصطفاف مزدوج قرب السوق",
        },
        "expected": {
            "vehicle_plate": "112233",
            "vehicle_owner": "نادر",
            "vehicle_color": "أبيض",
            "street_name": "شارع تل أبيض",
            "city_name": "الرقة",
            "violation_type_name": "اصطفاف مزدوج",
        },
        "description_tokens": ["plate 112233", "owner نادر", "city الرقة"],
    },
    {
        "name": "deir_ezzor_phone_use",
        "transcript": "اللوحة ٧٧١١٢٢، السيارة لخالد، في دير الزور شارع النهر، استخدام الهاتف قرب الجسر.",
        "llm_payload": {
            "vehicle_plate": "٧٧١١٢٢",
            "vehicle_owner": "خالد",
            "vehicle_model": "مازدا",
            "vehicle_color": "بني",
            "city": "دير الزور",
            "street_name": "شارع النهر",
            "landmark": "قرب الجسر",
            "violation_type": "استخدام الهاتف",
            "description": "",
        },
        "expected": {
            "vehicle_plate": "771122",
            "vehicle_owner": "خالد",
            "vehicle_color": "بني",
            "street_name": "شارع النهر",
            "city_name": "دير الزور",
            "violation_type_name": "استخدام الهاتف",
        },
        "description_tokens": ["plate 771122", "city دير الزور", "violation استخدام الهاتف"],
    },
    {
        "name": "hasaka_seatbelt",
        "transcript": "رقم اللوحة ٥٥٣٣١١، المالك حسين، في الحسكة شارع القامشلي، مخالفة حزام الأمان.",
        "llm_payload": {
            "vehicle_plate": "٥٥٣٣١١",
            "vehicle_owner": "حسين",
            "vehicle_model": "سوزوكي",
            "vehicle_color": "ذهبي",
            "city": "الحسكة",
            "street_name": "شارع القامشلي",
            "landmark": "",
            "violation_type": "حزام الأمان",
            "description": "مخالفة حزام الأمان",
        },
        "expected": {
            "vehicle_plate": "553311",
            "vehicle_owner": "حسين",
            "vehicle_color": "ذهبي",
            "street_name": "شارع القامشلي",
            "city_name": "الحسكة",
            "violation_type_name": "حزام الأمان",
        },
        "description_tokens": ["plate 553311", "city الحسكة", "street شارع القامشلي"],
    },
    {
        "name": "idlib_speeding",
        "transcript": "اللوحة ١٢١٢١٢، المركبة لمروان، في إدلب طريق الجامعة، تجاوز السرعة قرب الدوار.",
        "llm_payload": {
            "vehicle_plate": "١٢١٢١٢",
            "vehicle_owner": "مروان",
            "vehicle_model": "بيجو",
            "vehicle_color": "أخضر",
            "city": "إدلب",
            "street_name": "طريق الجامعة",
            "landmark": "قرب الدوار",
            "violation_type": "تجاوز السرعة",
            "description": "",
        },
        "expected": {
            "vehicle_plate": "121212",
            "vehicle_owner": "مروان",
            "vehicle_color": "أخضر",
            "street_name": "طريق الجامعة",
            "city_name": "إدلب",
            "violation_type_name": "تجاوز السرعة",
        },
        "description_tokens": ["plate 121212", "city إدلب", "landmark قرب الدوار"],
    },
    {
        "name": "damascus_wrong_way_generated_desc",
        "transcript": "رقم اللوحة ٨٨٨١١١، المالك عماد، في دمشق شارع بغداد، السير بعكس الاتجاه أمام الحديقة.",
        "llm_payload": {
            "vehicle_plate": "٨٨٨١١١",
            "vehicle_owner": "عماد",
            "vehicle_model": "مرسيدس",
            "vehicle_color": "أسود",
            "city": "دمشق",
            "street_name": "شارع بغداد",
            "landmark": "أمام الحديقة",
            "violation_type": "السير بعكس الاتجاه",
            "description": "",
        },
        "expected": {
            "vehicle_plate": "888111",
            "vehicle_owner": "عماد",
            "vehicle_color": "أسود",
            "street_name": "شارع بغداد",
            "city_name": "دمشق",
            "violation_type_name": "السير بعكس الاتجاه",
        },
        "description_tokens": ["plate 888111", "owner عماد", "violation السير بعكس الاتجاه"],
    },
    {
        "name": "aleppo_phone_use",
        "transcript": "اللوحة ٦١٦١٦١، السيارة لسامر، في حلب شارع فيصل، استخدام الهاتف أثناء القيادة قرب القلعة.",
        "llm_payload": {
            "vehicle_plate": "٦١٦١٦١",
            "vehicle_owner": "سامر",
            "vehicle_model": "تويوتا",
            "vehicle_color": "أبيض",
            "city": "حلب",
            "street_name": "شارع فيصل",
            "landmark": "قرب القلعة",
            "violation_type": "استخدام الهاتف",
            "description": "استخدام الهاتف أثناء القيادة",
        },
        "expected": {
            "vehicle_plate": "616161",
            "vehicle_owner": "سامر",
            "vehicle_color": "أبيض",
            "street_name": "شارع فيصل",
            "city_name": "حلب",
            "violation_type_name": "استخدام الهاتف",
        },
        "description_tokens": ["plate 616161", "city حلب", "landmark قرب القلعة"],
    },
]


def _normalize_metric_value(value):
    if value is None:
        return ""
    return str(value).strip().lower()


def _p95(latencies):
    if not latencies:
        return 0.0
    ordered = sorted(latencies)
    index = max(0, math.ceil(len(ordered) * 0.95) - 1)
    return ordered[index]


def build_finalize_fields_quality_report():
    latencies_ms = []
    field_hits = {field: 0 for field in QUALITY_FIELDS}
    exact_matches = 0
    description_hits = 0
    cases = []

    for case in AI_EVAL_CASES:
        started = time.perf_counter()
        result = finalize_fields(case["transcript"], case["llm_payload"])
        elapsed_ms = (time.perf_counter() - started) * 1000
        latencies_ms.append(elapsed_ms)

        matched_all = True
        for field in QUALITY_FIELDS:
            actual = _normalize_metric_value(result.get(field))
            expected = _normalize_metric_value(case["expected"].get(field))
            if actual == expected:
                field_hits[field] += 1
            else:
                matched_all = False

        description = _normalize_metric_value(result.get("description"))
        description_ok = all(token.lower() in description for token in case["description_tokens"])
        if description_ok:
            description_hits += 1
        else:
            matched_all = False

        if matched_all:
            exact_matches += 1

        cases.append(
            {
                "name": case["name"],
                "latency_ms": round(elapsed_ms, 3),
                "description_ok": description_ok,
            }
        )

    total_cases = len(AI_EVAL_CASES)
    total_field_checks = total_cases * len(QUALITY_FIELDS)
    matched_fields = sum(field_hits.values())

    return {
        "cases": cases,
        "summary": {
            "total_cases": total_cases,
            "field_accuracy": round(matched_fields / total_field_checks, 4),
            "exact_match_rate": round(exact_matches / total_cases, 4),
            "description_token_rate": round(description_hits / total_cases, 4),
            "avg_latency_ms": round(sum(latencies_ms) / total_cases, 3),
            "p95_latency_ms": round(_p95(latencies_ms), 3),
            "per_field_accuracy": {
                field: round(field_hits[field] / total_cases, 4) for field in QUALITY_FIELDS
            },
        },
    }


def build_lmstudio_extract_benchmark(iterations=50):
    mock_response = MagicMock()
    mock_response.ok = True
    mock_response.json.return_value = {
        "choices": [
            {"message": {"content": "{\"vehicle_plate\": \"1234567\", \"city\": \"دمشق\"}"}}
        ]
    }

    latencies_ms = []
    with patch("core.stt.extraction.requests.post", return_value=mock_response):
        for _ in range(iterations):
            started = time.perf_counter()
            extracted = lmstudio_extract("نص اختبار")
            latencies_ms.append((time.perf_counter() - started) * 1000)
            assert extracted["vehicle_plate"] == "1234567"

    return {
        "iterations": iterations,
        "avg_latency_ms": round(sum(latencies_ms) / iterations, 3),
        "p95_latency_ms": round(_p95(latencies_ms), 3),
    }


def build_ocr_parse_benchmark(iterations=200):
    raw = '{"plate_number": "١٢٣٤٥٦٧", "model": "Hyundai", "color": "أبيض"}'
    latencies_ms = []
    for _ in range(iterations):
        started = time.perf_counter()
        parsed = parse_json_from_text(raw)
        normalize_out(parsed)
        latencies_ms.append((time.perf_counter() - started) * 1000)

    return {
        "iterations": iterations,
        "avg_latency_ms": round(sum(latencies_ms) / iterations, 3),
        "p95_latency_ms": round(_p95(latencies_ms), 3),
    }


def build_ai_metrics_report():
    return {
        "finalize_fields": build_finalize_fields_quality_report(),
        "lmstudio_extract_mocked": build_lmstudio_extract_benchmark(),
        "ocr_parse_normalize": build_ocr_parse_benchmark(),
    }


class AiResponseAccuracyTests(SimpleTestCase):
    def test_extract_json_block_from_code_fence(self):
        raw = "```json\n{\n  \"vehicle_plate\": \"1234567\",\n  \"city\": \"دمشق\"\n}\n```"
        extracted = extract_json_block(raw)
        self.assertEqual(json.loads(extracted), {"vehicle_plate": "1234567", "city": "دمشق"})

    def test_parse_json_from_text_accepts_raw_json(self):
        raw = '{"plate_number": "1234", "model": "Toyota", "color": "أحمر"}'
        parsed = parse_json_from_text(raw)
        self.assertEqual(parsed["plate_number"], "1234")
        self.assertEqual(parsed["model"], "Toyota")
        self.assertEqual(parsed["color"], "أحمر")

    def test_parse_json_from_text_accepts_markdown_json(self):
        raw = "```json\n{\"plate_number\": \"12-3456\", \"model\": \"Hyundai\", \"color\": \"أبيض\"}\n```"
        parsed = parse_json_from_text(raw)
        self.assertEqual(parsed["plate_number"], "12-3456")
        self.assertEqual(parsed["color"], "أبيض")

    def test_parse_json_from_text_rejects_missing_json(self):
        with self.assertRaises(ValueError):
            parse_json_from_text("No JSON here")

    def test_normalize_out_strips_extra_whitespace_and_symbols(self):
        raw = {
            "plate_number": "  1234-٥٦٧  ",
            "model": "  Toyota Corolla  ",
            "color": "  أحمر   \n"
        }
        normalized = normalize_out(raw)
        self.assertEqual(normalized["plate_number"], "1234-٥٦٧")
        self.assertEqual(normalized["model"], "Toyota Corolla")
        self.assertEqual(normalized["color"], "أحمر")

    def test_finalize_fields_merges_llm_output_and_transcript(self):
        transcript = "رقم اللوحة ١٢٣٤٥٦٧، السيارة مالكها أحمد، في دمشق شارع الثورة، تجاوز السرعة."
        llm_payload = {
            "vehicle_plate": "١٢٣٤٥٦٧",
            "vehicle_owner": "أحمد",
            "vehicle_model": "تويوتا",
            "vehicle_color": "أحمر",
            "city": "دمشق",
            "street_name": "شارع الثورة",
            "landmark": "أمام البنك المركزي",
            "violation_type": "تجاوز السرعة",
            "description": "تجاوز السرعة في شارع الثورة"
        }

        result = finalize_fields(transcript, llm_payload)
        self.assertEqual(result["vehicle_plate"], "1234567")
        self.assertEqual(result["vehicle_owner"], "أحمد")
        self.assertEqual(result["vehicle_color"], "أحمر")
        self.assertEqual(result["street_name"], "شارع الثورة")
        self.assertEqual(result["city_name"], "دمشق")
        self.assertEqual(result["violation_type_name"], "تجاوز السرعة")
        self.assertIn("plate 1234567", result["description"])

    @patch("core.stt.extraction.requests.post")
    def test_lmstudio_extract_parses_valid_response(self, mock_post):
        mock_response = MagicMock()
        mock_response.ok = True
        mock_response.json.return_value = {
            "choices": [
                {"message": {"content": "{\"vehicle_plate\": \"1234567\", \"city\": \"دمشق\"}"}}
            ]
        }
        mock_post.return_value = mock_response

        extracted = lmstudio_extract("نص الاختبار")
        self.assertEqual(extracted["vehicle_plate"], "1234567")
        self.assertEqual(extracted["city"], "دمشق")


class AiPerformanceTests(SimpleTestCase):
    def test_finalize_fields_performance_under_threshold(self):
        transcript = "اللوحة ١٢٣٤٥٦٧ في دمشق شارع الثورة، تجاوز السير في الاتجاه المعاكس."
        llm_payload = {
            "vehicle_plate": "١٢٣٤٥٦٧",
            "vehicle_owner": "علي",
            "vehicle_model": "هوندا",
            "vehicle_color": "أبيض",
            "city": "دمشق",
            "street_name": "شارع الثورة",
            "landmark": "أمام بوابة الجامعة",
            "violation_type": "قطع إشارة",
            "description": "قطع الإشارة في شارع الثورة"
        }
        start = time.perf_counter()
        for _ in range(10):
            finalize_fields(transcript, llm_payload)
        elapsed = time.perf_counter() - start
        self.assertLess(elapsed, 0.5, f"finalize_fields is too slow: {elapsed:.3f}s")

    def test_parse_and_normalize_large_payloads_quickly(self):
        raw = '{"plate_number": "١٢٣٤٥٦٧", "model": "Hyundai", "color": "أبيض"}'
        start = time.perf_counter()
        for _ in range(100):
            parsed = parse_json_from_text(raw)
            normalize_out(parsed)
        elapsed = time.perf_counter() - start
        self.assertLess(elapsed, 1.0, f"AI parsing loop is too slow: {elapsed:.3f}s")


class SttServiceWorkflowTests(SimpleTestCase):
    @patch("core.stt.service.finalize_fields")
    @patch("core.stt.service.lmstudio_extract")
    @patch("core.stt.service.transcribe")
    @patch("core.stt.service.fetch_file")
    def test_handle_job_runs_full_stt_pipeline(self, mock_fetch_file, mock_transcribe, mock_lmstudio_extract, mock_finalize_fields):
        mock_fetch_file.return_value = "C:/audio/test.wav"
        mock_transcribe.return_value = "نص المخالفة"
        mock_lmstudio_extract.return_value = {"vehicle_plate": "1234567"}
        mock_finalize_fields.return_value = {"vehicle_plate": "1234567", "description": "plate 1234567"}

        result = stt_service.handle_job(
            {
                "job_id": "stt-001",
                "payload": {"audio_url": "https://example.com/test.wav"},
            }
        )

        mock_fetch_file.assert_called_once_with("https://example.com/test.wav")
        mock_transcribe.assert_called_once_with("C:/audio/test.wav")
        mock_lmstudio_extract.assert_called_once_with("نص المخالفة")
        mock_finalize_fields.assert_called_once_with("نص المخالفة", {"vehicle_plate": "1234567"})
        self.assertEqual(result["text"], "نص المخالفة")
        self.assertEqual(result["llm"]["vehicle_plate"], "1234567")
        self.assertEqual(result["fields"]["vehicle_plate"], "1234567")

    def test_handle_job_rejects_missing_job_id_or_audio_source(self):
        with self.assertRaises(ValueError):
            stt_service.handle_job({"payload": {"audio_url": "https://example.com/test.wav"}})
        with self.assertRaises(ValueError):
            stt_service.handle_job({"job_id": "stt-002", "payload": {}})


class OcrServiceWorkflowTests(SimpleTestCase):
    @patch("core.ocr.service.normalize_out")
    @patch("core.ocr.service.call_ollama_vision_json")
    @patch("core.ocr.service.encode_jpeg_b64")
    @patch("core.ocr.service.read_image_bgr")
    def test_ocr_vehicle_runs_full_ocr_pipeline(self, mock_read_image_bgr, mock_encode_jpeg_b64, mock_call_ollama, mock_normalize_out):
        mock_read_image_bgr.return_value = "image-array"
        mock_encode_jpeg_b64.return_value = "base64-image"
        mock_call_ollama.return_value = {"plate_number": "1234567", "model": "Toyota", "color": "white"}
        mock_normalize_out.return_value = {"plate_number": "1234567", "model": "Toyota", "color": "white"}

        result = ocr_service.ocr_vehicle("C:/images/car.jpg")

        mock_read_image_bgr.assert_called_once_with("C:/images/car.jpg")
        mock_encode_jpeg_b64.assert_called_once_with("image-array", quality=95)
        mock_call_ollama.assert_called_once()
        mock_normalize_out.assert_called_once_with({"plate_number": "1234567", "model": "Toyota", "color": "white"})
        self.assertEqual(result["plate_number"], "1234567")

    @patch("core.ocr.service.ocr_vehicle")
    @patch("core.ocr.service.resolve_image_path")
    def test_handle_job_returns_normalized_ocr_result(self, mock_resolve_image_path, mock_ocr_vehicle):
        mock_resolve_image_path.return_value = "C:/images/car.jpg"
        mock_ocr_vehicle.return_value = {"plate_number": "1234567", "model": "Toyota", "color": "white"}

        result = ocr_service.handle_job(
            {
                "job_id": "ocr-001",
                "payload": {"local_image_path": "C:/images/car.jpg"},
            }
        )

        mock_resolve_image_path.assert_called_once_with(local_image_path="C:/images/car.jpg", image_url=None)
        mock_ocr_vehicle.assert_called_once_with("C:/images/car.jpg")
        self.assertEqual(result["job_id"], "ocr-001")
        self.assertEqual(result["plate_number"], "1234567")


class SttConsumerWorkflowTests(SimpleTestCase):
    @patch("core.stt.consumer.publish_result")
    @patch("core.stt.consumer.handle_job")
    def test_on_message_acknowledges_success(self, mock_handle_job, mock_publish_result):
        channel = MagicMock()
        method = MagicMock(delivery_tag="tag-1")
        properties = MagicMock(correlation_id="corr-1")
        mock_handle_job.return_value = {"text": "نص", "llm": {}, "fields": {"vehicle_plate": "1234567"}}

        stt_consumer.on_message(
            channel,
            method,
            properties,
            json.dumps({"job_id": "stt-101", "payload": {"audio_url": "https://example.com/a.wav"}}),
        )

        mock_handle_job.assert_called_once()
        mock_publish_result.assert_called_once()
        channel.basic_ack.assert_called_once_with("tag-1")

    @patch("core.stt.consumer.publish_result")
    @patch("core.stt.consumer.handle_job", side_effect=RuntimeError("stt failed"))
    def test_on_message_publishes_failure_and_acknowledges(self, mock_handle_job, mock_publish_result):
        channel = MagicMock()
        method = MagicMock(delivery_tag="tag-2")
        properties = MagicMock(correlation_id="corr-2")

        stt_consumer.on_message(
            channel,
            method,
            properties,
            json.dumps({"job_id": "stt-102", "payload": {"audio_url": "https://example.com/a.wav"}}),
        )

        mock_handle_job.assert_called_once()
        mock_publish_result.assert_called_once()
        channel.basic_ack.assert_called_once_with("tag-2")

    @patch("core.stt.consumer.publish_result", side_effect=RuntimeError("publish failed"))
    @patch("core.stt.consumer.handle_job", side_effect=RuntimeError("stt failed"))
    def test_on_message_nacks_when_failure_result_cannot_be_published(self, mock_handle_job, mock_publish_result):
        channel = MagicMock()
        method = MagicMock(delivery_tag="tag-2b")
        properties = MagicMock(correlation_id="corr-2b")

        stt_consumer.on_message(
            channel,
            method,
            properties,
            json.dumps({"job_id": "stt-102b", "payload": {"audio_url": "https://example.com/a.wav"}}),
        )

        mock_handle_job.assert_called_once()
        mock_publish_result.assert_called_once()
        channel.basic_nack.assert_called_once_with("tag-2b", requeue=True)


class OcrConsumerWorkflowTests(SimpleTestCase):
    def test_publish_result_serializes_payload(self):
        channel = MagicMock()
        channel.is_open = True

        ocr_consumer.publish_result(channel, {"job_id": "ocr-201", "status": "success"}, correlation_id="corr-201")

        channel.basic_publish.assert_called_once()
        kwargs = channel.basic_publish.call_args.kwargs
        self.assertEqual(kwargs["routing_key"], ocr_consumer.RESULTS_ROUTING_KEY)
        self.assertIn(b"ocr-201", kwargs["body"])

    @patch("core.ocr.consumer.publish_result")
    @patch("core.ocr.consumer.handle_job")
    def test_consumer_callback_acknowledges_success(self, mock_handle_job, mock_publish_result):
        connection = MagicMock()
        channel = MagicMock()
        connection.channel.return_value = channel
        mock_handle_job.return_value = {
            "job_id": "ocr-202",
            "plate_number": "1234567",
            "model": "Toyota",
            "color": "white",
            "image_path": "C:/images/car.jpg",
            "created_at": 1.0,
        }

        captured = {}

        def capture_callback(*args, **kwargs):
            captured["callback"] = kwargs["on_message_callback"]

        channel.basic_consume.side_effect = capture_callback
        channel.start_consuming.side_effect = lambda: captured["callback"](
            channel,
            MagicMock(delivery_tag="tag-3"),
            MagicMock(correlation_id="corr-3"),
            json.dumps({"job_id": "ocr-202", "payload": {"local_image_path": "C:/images/car.jpg"}}).encode("utf-8"),
        )

        with patch("core.ocr.consumer.rabbit_connection", return_value=connection):
            ocr_consumer.consume_forever()

        mock_handle_job.assert_called_once()
        mock_publish_result.assert_called_once()
        channel.basic_ack.assert_called_once_with("tag-3")

    @patch("core.ocr.consumer.publish_result")
    @patch("core.ocr.consumer.handle_job", side_effect=RuntimeError("ocr failed"))
    def test_consumer_callback_acknowledges_failure_after_publishing_result(self, mock_handle_job, mock_publish_result):
        connection = MagicMock()
        channel = MagicMock()
        connection.channel.return_value = channel

        captured = {}

        def capture_callback(*args, **kwargs):
            captured["callback"] = kwargs["on_message_callback"]

        channel.basic_consume.side_effect = capture_callback
        channel.start_consuming.side_effect = lambda: captured["callback"](
            channel,
            MagicMock(delivery_tag="tag-4"),
            MagicMock(correlation_id="corr-4"),
            json.dumps({"job_id": "ocr-204", "payload": {"local_image_path": "C:/images/car.jpg"}}).encode("utf-8"),
        )

        with patch("core.ocr.consumer.rabbit_connection", return_value=connection):
            ocr_consumer.consume_forever()

        mock_handle_job.assert_called_once()
        mock_publish_result.assert_called_once()
        channel.basic_ack.assert_called_once_with("tag-4")


class AiQualityMetricsTests(SimpleTestCase):
    def test_finalize_fields_quality_metrics(self):
        report = build_finalize_fields_quality_report()
        summary = report["summary"]
        self.assertGreaterEqual(summary["field_accuracy"], 0.94)
        self.assertGreaterEqual(summary["exact_match_rate"], 0.66)
        self.assertEqual(summary["description_token_rate"], 1.0)
        self.assertLess(summary["avg_latency_ms"], 50.0)
        self.assertLess(summary["p95_latency_ms"], 50.0)

    def test_mocked_lmstudio_response_time(self):
        report = build_lmstudio_extract_benchmark()
        self.assertLess(report["avg_latency_ms"], 20.0)
        self.assertLess(report["p95_latency_ms"], 20.0)

    def test_ocr_parse_response_time(self):
        report = build_ocr_parse_benchmark()
        self.assertLess(report["avg_latency_ms"], 5.0)
        self.assertLess(report["p95_latency_ms"], 5.0)

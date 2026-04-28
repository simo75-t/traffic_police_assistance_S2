import json
from unittest.mock import MagicMock, patch

from django.test import SimpleTestCase

from core.heatmap_prediction import consumer as prediction_consumer
from core.heatmap_prediction import llm_service
from core.heatmap_prediction.llm_service import (
    PredictionLlmConnectionError,
    PredictionLlmInvalidJsonError,
    PredictionLlmSchemaError,
    generate_qwen_recommendations,
    generate_recommendations,
)
from core.heatmap_prediction.orchestrator import HeatmapPredictionOrchestrator
from core.heatmap_prediction.payloads import PayloadValidationError, validate_payload
from core.heatmap_prediction.providers import resolve_prediction_source
from core.heatmap_prediction.schema import PredictionSchemaError, validate_prediction_output
from core.heatmap_prediction.scoring import build_prediction_signals


def sample_prediction_payload():
    return {
        "job_type": "generate_heatmap_prediction",
        "request_id": "pred-001",
        "correlation_id": "corr-001",
        "heatmap_summary": {
            "city": "ط¯ظ…ط´ظ‚",
            "from_date": "2026-03-21",
            "to_date": "2026-04-27",
            "violation_type": "all",
            "time_bucket": "all_day",
            "hotspots": [
                {
                    "area_name": "Barzeh",
                    "density_score": 0.87,
                    "rank": 1,
                    "trend": "increasing",
                    "percentage_change": 32.5,
                    "dominant_violation_type": "Speeding",
                    "dominant_time_bucket": "evening",
                    "recent_count": 53,
                    "previous_count": 40,
                },
                {
                    "area_name": "Mazzeh",
                    "density_score": 0.52,
                    "rank": 2,
                    "trend": "stable",
                    "percentage_change": 6.0,
                    "dominant_violation_type": "Illegal Parking",
                    "dominant_time_bucket": "afternoon",
                },
            ],
        },
    }


def sample_valid_prediction_result():
    return {
        "prediction_summary": "ملخص قصير",
        "overall_risk_level": "high",
        "predicted_hotspots": [
            {
                "area_name": "Barzeh",
                "risk_level": "high",
                "predicted_time_bucket": "evening",
                "predicted_violation_type": "Speeding",
                "confidence": 0.88,
                "reason": "ارتفاع واضح في الإشارة.",
            }
        ],
        "recommendations": [
            {
                "priority": "high",
                "action": "تكثيف الدوريات في برزة",
                "target_area": "Barzeh",
                "target_time_bucket": "evening",
                "reason": "المنطقة الأعلى خطرًا في الملخص.",
            }
        ],
        "limitations": ["يعتمد على الملخص الحسابي الحالي."],
    }


class HeatmapPredictionValidationTests(SimpleTestCase):
    def test_validate_payload_accepts_valid_summary(self):
        payload = validate_payload(sample_prediction_payload())
        self.assertEqual(payload.heatmap_summary.city, "ط¯ظ…ط´ظ‚")
        self.assertEqual(len(payload.heatmap_summary.hotspots), 2)

    def test_validate_payload_rejects_empty_hotspots(self):
        payload = sample_prediction_payload()
        payload["heatmap_summary"]["hotspots"] = []
        with self.assertRaises(PayloadValidationError):
            validate_payload(payload)

    def test_schema_validation_rejects_invalid_confidence(self):
        with self.assertRaises(PredictionSchemaError):
            validate_prediction_output(
                {
                    "prediction_summary": "x",
                    "overall_risk_level": "high",
                    "predicted_hotspots": [
                        {
                            "area_name": "Barzeh",
                            "risk_level": "high",
                            "predicted_time_bucket": "evening",
                            "predicted_violation_type": "Speeding",
                            "confidence": 1.5,
                            "reason": "test",
                        }
                    ],
                    "recommendations": [
                        {
                            "priority": "high",
                            "action": "test",
                            "target_area": "Barzeh",
                            "target_time_bucket": "evening",
                            "reason": "test",
                        }
                    ],
                    "limitations": ["test"],
                }
            )


class HeatmapPredictionScoringTests(SimpleTestCase):
    def test_build_prediction_signals_assigns_high_risk_for_strong_hotspot(self):
        payload = validate_payload(sample_prediction_payload())
        signals = build_prediction_signals(payload.heatmap_summary)
        top_hotspot = signals["predicted_hotspots"][0]

        self.assertEqual(signals["overall_risk_level"], "high")
        self.assertEqual(top_hotspot["area_name"], "Barzeh")
        self.assertIn(top_hotspot["risk_level"], {"high", "critical"})
        self.assertGreaterEqual(top_hotspot["confidence"], 0.7)

    def test_build_prediction_signals_collects_violation_and_time_bucket_trends(self):
        payload = validate_payload(sample_prediction_payload())
        signals = build_prediction_signals(payload.heatmap_summary)

        self.assertEqual(signals["predicted_increasing_violation_types"][0]["violation_type"], "Speeding")
        self.assertEqual(signals["predicted_high_risk_time_buckets"][0]["time_bucket"], "evening")


class HeatmapPredictionConsumerTests(SimpleTestCase):
    @patch("core.heatmap_prediction.consumer.publish_result")
    @patch("core.heatmap_prediction.consumer.orchestrator.generate_prediction")
    def test_on_message_acknowledges_success(self, mock_generate_prediction, mock_publish_result):
        channel = MagicMock()
        method = MagicMock(delivery_tag="tag-1")
        properties = MagicMock(correlation_id="corr-1")
        mock_generate_prediction.return_value = {
            "request_id": "pred-001",
            "city": "ط¯ظ…ط´ظ‚",
            "source": "fallback",
            "signal_summary": {},
            "prediction_summary": "summary",
            "overall_risk_level": "high",
            "predicted_hotspots": [],
            "recommendations": [],
            "limitations": [],
        }

        prediction_consumer.on_message(
            channel,
            method,
            properties,
            json.dumps(sample_prediction_payload()).encode("utf-8"),
        )

        mock_generate_prediction.assert_called_once()
        mock_publish_result.assert_called_once()
        channel.basic_ack.assert_called_once_with("tag-1")

    @patch("core.heatmap_prediction.consumer.publish_result", side_effect=RuntimeError("publish failed"))
    @patch("core.heatmap_prediction.consumer.orchestrator.generate_prediction", side_effect=RuntimeError("boom"))
    def test_on_message_nacks_when_failure_publish_fails(self, mock_generate_prediction, mock_publish_result):
        channel = MagicMock()
        method = MagicMock(delivery_tag="tag-2")
        properties = MagicMock(correlation_id="corr-2")

        prediction_consumer.on_message(
            channel,
            method,
            properties,
            json.dumps(sample_prediction_payload()).encode("utf-8"),
        )

        mock_generate_prediction.assert_called_once()
        mock_publish_result.assert_called_once()
        channel.basic_nack.assert_called_once_with("tag-2", requeue=True)

    @patch("core.heatmap_prediction.consumer.publish_result")
    @patch("core.heatmap_prediction.consumer.orchestrator.generate_prediction")
    def test_consume_forever_registers_worker_callback(self, mock_generate_prediction, mock_publish_result):
        connection = MagicMock()
        channel = MagicMock()
        connection.channel.return_value = channel
        captured = {}

        mock_generate_prediction.return_value = {
            "request_id": "pred-001",
            "city": "ط¯ظ…ط´ظ‚",
            "source": "fallback",
            "signal_summary": {},
            "prediction_summary": "summary",
            "overall_risk_level": "medium",
            "predicted_hotspots": [],
            "recommendations": [],
            "limitations": [],
        }

        def capture_callback(*args, **kwargs):
            captured["callback"] = kwargs["on_message_callback"]

        channel.basic_consume.side_effect = capture_callback
        channel.start_consuming.side_effect = lambda: captured["callback"](
            channel,
            MagicMock(delivery_tag="tag-3"),
            MagicMock(correlation_id="corr-3"),
            json.dumps(sample_prediction_payload()).encode("utf-8"),
        )

        with patch("core.heatmap_prediction.consumer.rabbit_connection", return_value=connection):
            prediction_consumer.consume_forever()

        mock_generate_prediction.assert_called_once()
        mock_publish_result.assert_called_once()
        channel.basic_ack.assert_called_once_with("tag-3")


class HeatmapPredictionLlmTests(SimpleTestCase):
    def setUp(self):
        payload = validate_payload(sample_prediction_payload())
        self.signals = build_prediction_signals(payload.heatmap_summary)

    @patch("core.heatmap_prediction.llm_service._qwen_api_key", return_value="secret")
    @patch("core.heatmap_prediction.llm_service.SESSION.post")
    def test_generate_qwen_recommendations_success_returns_valid_schema(self, mock_post, _mock_key):
        mock_response = MagicMock()
        mock_response.raise_for_status.return_value = None
        mock_response.json.return_value = {
            "choices": [
                {
                    "message": {
                        "content": json.dumps(sample_valid_prediction_result(), ensure_ascii=False),
                    }
                }
            ]
        }
        mock_post.return_value = mock_response

        result = generate_qwen_recommendations(self.signals)

        self.assertEqual(result["overall_risk_level"], "high")
        self.assertEqual(result["predicted_hotspots"][0]["area_name"], "Barzeh")

    @patch("core.heatmap_prediction.llm_service._provider", return_value="qwen_api")
    @patch("core.heatmap_prediction.llm_service._qwen_api_key", return_value="secret")
    @patch("core.heatmap_prediction.llm_service.SESSION.post")
    def test_generate_recommendations_uses_qwen_provider(self, mock_post, _mock_key, _mock_provider):
        mock_response = MagicMock()
        mock_response.raise_for_status.return_value = None
        mock_response.json.return_value = {
            "choices": [
                {
                    "message": {
                        "content": json.dumps(sample_valid_prediction_result(), ensure_ascii=False),
                    }
                }
            ]
        }
        mock_post.return_value = mock_response

        result = generate_recommendations(self.signals)

        self.assertEqual(result["overall_risk_level"], "high")
        self.assertEqual(mock_post.call_count, 1)

    @patch("core.heatmap_prediction.llm_service._provider", return_value="fallback")
    @patch("core.heatmap_prediction.llm_service.SESSION.post")
    def test_generate_recommendations_uses_rule_based_provider_without_http_call(self, mock_post, _mock_provider):
        result = generate_recommendations(self.signals)

        self.assertTrue(result["recommendations"])
        mock_post.assert_not_called()

    @patch("core.heatmap_prediction.llm_service._provider", return_value="ollama")
    @patch("core.heatmap_prediction.llm_service.SESSION.post")
    def test_generate_recommendations_accepts_valid_ollama_json(self, mock_post, _mock_provider):
        mock_response = MagicMock()
        mock_response.raise_for_status.return_value = None
        mock_response.json.return_value = {
            "message": {
                "content": "```json\n" + json.dumps(sample_valid_prediction_result(), ensure_ascii=False) + "\n```"
            }
        }
        mock_post.return_value = mock_response

        result = generate_recommendations(self.signals)
        self.assertEqual(result["overall_risk_level"], "high")
        self.assertEqual(result["predicted_hotspots"][0]["area_name"], "Barzeh")

    @patch("core.heatmap_prediction.llm_service._provider", return_value="ollama")
    @patch("core.heatmap_prediction.llm_service._prediction_ollama_retries", return_value=2)
    @patch("core.heatmap_prediction.llm_service.SESSION.post")
    def test_generate_recommendations_retries_when_ollama_truncates_json(self, mock_post, _mock_retries, _mock_provider):
        truncated_response = MagicMock()
        truncated_response.raise_for_status.return_value = None
        truncated_response.json.return_value = {
            "done": True,
            "done_reason": "length",
            "message": {
                "content": '{"prediction_summary":"ملخص","overall_risk_level":"high"',
            },
        }
        valid_response = MagicMock()
        valid_response.raise_for_status.return_value = None
        valid_response.json.return_value = {
            "done": True,
            "done_reason": "stop",
            "message": {
                "content": json.dumps(sample_valid_prediction_result(), ensure_ascii=False),
            },
        }
        mock_post.side_effect = [truncated_response, valid_response]

        result = generate_recommendations(self.signals)

        self.assertEqual(result["overall_risk_level"], "high")
        self.assertEqual(mock_post.call_count, 2)
        self.assertEqual(mock_post.call_args_list[0].kwargs["json"]["options"]["num_predict"], 480)
        self.assertGreater(mock_post.call_args_list[1].kwargs["json"]["options"]["num_predict"], 480)

    @patch("core.heatmap_prediction.llm_service._provider", return_value="qwen_api")
    @patch("core.heatmap_prediction.llm_service._qwen_api_key", return_value="secret")
    @patch("core.heatmap_prediction.llm_service.SESSION.post", side_effect=llm_service.requests_exceptions.Timeout("timeout"))
    def test_generate_recommendations_qwen_timeout_raises(self, _mock_post, _mock_key, _mock_provider):
        with self.assertRaises(llm_service.PredictionLlmTimeoutError):
            generate_recommendations(self.signals)

    @patch("core.heatmap_prediction.llm_service._provider", return_value="qwen_api")
    @patch("core.heatmap_prediction.llm_service._qwen_api_key", return_value="secret")
    @patch(
        "core.heatmap_prediction.llm_service.SESSION.post",
        side_effect=llm_service.requests_exceptions.ConnectionError("conn"),
    )
    def test_generate_recommendations_qwen_connection_error_raises(self, _mock_post, _mock_key, _mock_provider):
        with self.assertRaises(PredictionLlmConnectionError):
            generate_recommendations(self.signals)

    @patch("core.heatmap_prediction.llm_service._provider", return_value="qwen_api")
    @patch("core.heatmap_prediction.llm_service._qwen_api_key", return_value="secret")
    @patch("core.heatmap_prediction.llm_service.SESSION.post")
    def test_generate_recommendations_qwen_invalid_json_raises(self, mock_post, _mock_key, _mock_provider):
        mock_response = MagicMock()
        mock_response.raise_for_status.return_value = None
        mock_response.json.return_value = {"choices": [{"message": {"content": "not valid"}}]}
        mock_post.return_value = mock_response

        with self.assertRaises(PredictionLlmInvalidJsonError):
            generate_recommendations(self.signals)

    @patch("core.heatmap_prediction.llm_service._provider", return_value="qwen_api")
    @patch("core.heatmap_prediction.llm_service._qwen_api_key", return_value="secret")
    @patch("core.heatmap_prediction.llm_service.SESSION.post")
    def test_generate_recommendations_qwen_invalid_schema_raises(self, mock_post, _mock_key, _mock_provider):
        mock_response = MagicMock()
        mock_response.raise_for_status.return_value = None
        mock_response.json.return_value = {
            "choices": [
                {
                    "message": {
                        "content": json.dumps(
                            {
                                "prediction_summary": "ملخص",
                                "overall_risk_level": "high",
                                "predicted_hotspots": [],
                                "recommendations": [],
                                "limitations": ["ok"],
                                "extra_field": "should fail",
                            },
                            ensure_ascii=False,
                        )
                    }
                }
            ]
        }
        mock_post.return_value = mock_response

        with self.assertRaises(PredictionLlmSchemaError):
            generate_recommendations(self.signals)


class HeatmapPredictionOrchestratorTests(SimpleTestCase):
    @patch("core.heatmap_prediction.orchestrator.current_provider", return_value="qwen_api")
    @patch("core.heatmap_prediction.orchestrator.generate_recommendations")
    def test_orchestrator_qwen_success_returns_qwen_source(self, mock_generate_recommendations, _mock_provider):
        mock_generate_recommendations.return_value = sample_valid_prediction_result()
        orchestrator = HeatmapPredictionOrchestrator()

        result = orchestrator.generate_prediction(sample_prediction_payload())

        self.assertEqual(result["source"], "qwen_api")
        self.assertEqual(result["overall_risk_level"], "high")

    @patch("core.heatmap_prediction.orchestrator.current_provider", return_value="qwen_api")
    @patch("core.heatmap_prediction.orchestrator.generate_recommendations", side_effect=llm_service.PredictionLlmTimeoutError("timeout"))
    def test_orchestrator_qwen_timeout_uses_fallback(self, _mock_generate_recommendations, _mock_provider):
        orchestrator = HeatmapPredictionOrchestrator()

        result = orchestrator.generate_prediction(sample_prediction_payload())

        self.assertEqual(result["source"], "fallback_after_qwen_failure")
        self.assertTrue(result["recommendations"])

    @patch(
        "core.heatmap_prediction.orchestrator.current_provider",
        return_value="qwen_api",
    )
    @patch(
        "core.heatmap_prediction.orchestrator.generate_recommendations",
        side_effect=llm_service.PredictionLlmConnectionError("connection"),
    )
    def test_orchestrator_qwen_connection_error_uses_fallback(self, _mock_generate_recommendations, _mock_provider):
        orchestrator = HeatmapPredictionOrchestrator()

        result = orchestrator.generate_prediction(sample_prediction_payload())

        self.assertEqual(result["source"], "fallback_after_qwen_failure")

    @patch("core.heatmap_prediction.orchestrator.current_provider", return_value="qwen_api")
    @patch(
        "core.heatmap_prediction.orchestrator.generate_recommendations",
        side_effect=llm_service.PredictionLlmInvalidJsonError("bad json"),
    )
    def test_orchestrator_qwen_invalid_json_uses_fallback(self, _mock_generate_recommendations, _mock_provider):
        orchestrator = HeatmapPredictionOrchestrator()

        result = orchestrator.generate_prediction(sample_prediction_payload())

        self.assertEqual(result["source"], "fallback_after_qwen_failure")

    @patch("core.heatmap_prediction.orchestrator.current_provider", return_value="qwen_api")
    @patch(
        "core.heatmap_prediction.orchestrator.generate_recommendations",
        side_effect=llm_service.PredictionLlmSchemaError("bad schema"),
    )
    def test_orchestrator_qwen_invalid_schema_uses_fallback(self, _mock_generate_recommendations, _mock_provider):
        orchestrator = HeatmapPredictionOrchestrator()

        result = orchestrator.generate_prediction(sample_prediction_payload())

        self.assertEqual(result["source"], "fallback_after_qwen_failure")

    @patch("core.heatmap_prediction.orchestrator.current_provider", return_value="fallback")
    @patch("core.heatmap_prediction.orchestrator.generate_recommendations")
    def test_orchestrator_provider_fallback_uses_fallback_directly(self, mock_generate_recommendations, _mock_provider):
        mock_generate_recommendations.return_value = llm_service.build_fallback_prediction(
            build_prediction_signals(validate_payload(sample_prediction_payload()).heatmap_summary)
        )
        orchestrator = HeatmapPredictionOrchestrator()

        result = orchestrator.generate_prediction(sample_prediction_payload())

        self.assertEqual(result["source"], "fallback")

    @patch("core.heatmap_prediction.orchestrator.current_provider", return_value="ollama")
    @patch("core.heatmap_prediction.orchestrator.generate_recommendations", return_value=sample_valid_prediction_result())
    def test_orchestrator_provider_ollama_success_keeps_ollama_source(self, _mock_generate_recommendations, _mock_provider):
        orchestrator = HeatmapPredictionOrchestrator()

        result = orchestrator.generate_prediction(sample_prediction_payload())

        self.assertEqual(result["source"], "ollama")

    @patch("core.heatmap_prediction.orchestrator.current_provider", return_value="ollama")
    @patch(
        "core.heatmap_prediction.orchestrator.generate_recommendations",
        side_effect=llm_service.PredictionLlmConnectionError("ollama down"),
    )
    def test_orchestrator_provider_ollama_failure_uses_fallback(self, _mock_generate_recommendations, _mock_provider):
        orchestrator = HeatmapPredictionOrchestrator()

        result = orchestrator.generate_prediction(sample_prediction_payload())

        self.assertEqual(result["source"], "fallback_after_provider_failure")


class HeatmapPredictionProviderTests(SimpleTestCase):
    def test_resolve_prediction_source_maps_success_sources(self):
        self.assertEqual(resolve_prediction_source("qwen_api", failed=False), "qwen_api")
        self.assertEqual(resolve_prediction_source("fallback", failed=False), "fallback")
        self.assertEqual(resolve_prediction_source("ollama", failed=False), "ollama")

    def test_resolve_prediction_source_maps_failure_sources(self):
        self.assertEqual(resolve_prediction_source("qwen_api", failed=True), "fallback_after_qwen_failure")
        self.assertEqual(resolve_prediction_source("fallback", failed=True), "fallback")
        self.assertEqual(resolve_prediction_source("ollama", failed=True), "fallback_after_provider_failure")

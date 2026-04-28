from __future__ import annotations

import logging
from typing import Any

from core.heatmap_prediction.fallbacks import build_fallback_prediction
from core.heatmap_prediction.llm_service import (
    PredictionLlmConnectionError,
    PredictionLlmEmptyResponseError,
    PredictionLlmInvalidJsonError,
    PredictionLlmSchemaError,
    PredictionLlmTimeoutError,
    current_provider,
    generate_recommendations,
)
from core.heatmap_prediction.payloads import HeatmapPredictionPayload, validate_payload
from core.heatmap_prediction.providers import resolve_prediction_source
from core.heatmap_prediction.schema import validate_prediction_output
from core.heatmap_prediction.scoring import build_prediction_signals


log = logging.getLogger("HEATMAP_PREDICTION_SERVICE")


class HeatmapPredictionOrchestrator:
    def generate_prediction(self, payload: dict[str, Any]) -> dict[str, Any]:
        parsed = validate_payload(payload)
        signal_summary = build_prediction_signals(parsed.heatmap_summary)
        provider = current_provider()
        log.info(
            "Heatmap prediction provider selected request_id=%s provider=%s",
            parsed.request_id,
            provider,
        )

        try:
            llm_output = generate_recommendations(signal_summary)
            validated = validate_prediction_output(llm_output)
            source = resolve_prediction_source(provider, failed=False)
        except (
            PredictionLlmConnectionError,
            PredictionLlmEmptyResponseError,
            PredictionLlmTimeoutError,
            PredictionLlmInvalidJsonError,
            PredictionLlmSchemaError,
        ) as exc:
            source = resolve_prediction_source(provider, failed=True)
            log.warning(
                "Heatmap prediction fallback used request_id=%s provider=%s source=%s err=%s",
                parsed.request_id,
                provider,
                source,
                exc,
            )
            validated = build_fallback_prediction(signal_summary)
            validated = validate_prediction_output(validated)
        except Exception as exc:
            source = resolve_prediction_source(provider, failed=True)
            log.exception(
                "Unexpected heatmap prediction failure request_id=%s provider=%s source=%s",
                parsed.request_id,
                provider,
                source,
            )
            validated = build_fallback_prediction(signal_summary)
            validated = validate_prediction_output(validated)

        return {
            "request_id": parsed.request_id,
            "city": parsed.heatmap_summary.city,
            "source": source,
            "signal_summary": signal_summary,
            **validated,
        }

    def fail_job(self, payload: dict[str, Any], exc: Exception) -> None:
        _ = payload
        _ = exc

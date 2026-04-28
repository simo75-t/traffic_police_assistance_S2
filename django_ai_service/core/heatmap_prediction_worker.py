"""Heatmap prediction RabbitMQ worker entrypoint."""

from core.heatmap_prediction.consumer import main

__all__ = ["main"]

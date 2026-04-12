"""Management command that starts the OCR RabbitMQ worker."""

from django.core.management.base import BaseCommand


class Command(BaseCommand):
    help = "Run the OCR RabbitMQ worker."

    def handle(self, *args, **options):
        # Import lazily so help or checks do not initialize OCR dependencies.
        from core import ocr_worker

        self.stdout.write(self.style.SUCCESS("Starting OCR worker..."))
        ocr_worker.main()

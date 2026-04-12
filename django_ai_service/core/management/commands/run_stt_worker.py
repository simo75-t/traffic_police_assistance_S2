"""Management command that starts the STT RabbitMQ worker."""

from django.core.management.base import BaseCommand


class Command(BaseCommand):
    help = "Run the STT RabbitMQ worker."

    def handle(self, *args, **options):
        # Import lazily so help or checks do not load Whisper unless needed.
        from core import stt_worker

        self.stdout.write(self.style.SUCCESS("Starting STT worker..."))
        stt_worker.main()

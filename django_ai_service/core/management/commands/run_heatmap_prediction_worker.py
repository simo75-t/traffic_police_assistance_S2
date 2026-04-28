from django.core.management.base import BaseCommand


class Command(BaseCommand):
    help = "Run the heatmap prediction RabbitMQ worker."

    def handle(self, *args, **options):
        from core import heatmap_prediction_worker

        self.stdout.write(self.style.SUCCESS("Starting heatmap prediction worker..."))
        heatmap_prediction_worker.main()

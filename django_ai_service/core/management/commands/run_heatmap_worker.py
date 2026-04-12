from django.core.management.base import BaseCommand


class Command(BaseCommand):
    help = "Run the heatmap RabbitMQ worker."

    def handle(self, *args, **options):
        from core import heatmap_worker

        self.stdout.write(self.style.SUCCESS("Starting heatmap worker..."))
        heatmap_worker.main()

from django.test.runner import DiscoverRunner


class MongoDBTestRunner(DiscoverRunner):
    """Use the existing MongoDB database for Django tests.

    This runner skips test database creation and teardown so tests run
    against the MongoDB instance configured in settings.
    """

    def setup_databases(self, **kwargs):
        # Skip creating a separate test database and use the configured MongoDB.
        return None

    def teardown_databases(self, old_config, **kwargs):
        # No teardown required for the live MongoDB connection.
        return None

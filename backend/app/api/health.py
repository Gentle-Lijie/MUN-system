"""Health check resources."""

from datetime import datetime, timezone

from flask_restful import Resource


class HealthResource(Resource):
    """Simple ping endpoint to verify service health."""

    def get(self) -> tuple[dict[str, str], int]:
        return (
            {
                'status': 'ok',
                'timestamp': datetime.now(timezone.utc).isoformat(),
            },
            200,
        )

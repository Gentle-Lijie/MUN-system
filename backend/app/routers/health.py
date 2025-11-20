from datetime import datetime, timezone

from fastapi import APIRouter

router = APIRouter(prefix='/health', tags=['health'])


@router.get('/ping')
async def ping() -> dict[str, str]:
    return {'status': 'ok', 'timestamp': datetime.now(timezone.utc).isoformat()}

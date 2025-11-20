from __future__ import annotations

from typing import Literal

from fastapi import APIRouter, HTTPException, status
from pydantic import BaseModel, Field

router = APIRouter(prefix='/mcp', tags=['mcp', 'daisyUI'])


class DaisyUIComponent(BaseModel):
    id: str
    name: str
    category: str
    description: str
    classes: list[str]
    tokens: dict[str, str] = Field(default_factory=dict)


class ThemePreviewRequest(BaseModel):
    theme: Literal['munlight', 'business'] = 'munlight'


class ActionPayload(BaseModel):
    component_id: str = Field(..., alias='componentId')
    action: Literal['generate', 'sync'] = 'generate'


FAKE_COMPONENT_REGISTRY: list[DaisyUIComponent] = [
    DaisyUIComponent(
        id='hero-card',
        name='Hero 卡片',
        category='layout',
        description='用于会议主页欢迎语与行动按钮的 hero 组件',
        classes=['hero', 'bg-base-200', 'rounded-2xl', 'shadow-xl'],
        tokens={'primary': '#2563eb', 'accent': '#f97316'},
    ),
    DaisyUIComponent(
        id='timeline',
        name='时间线',
        category='feedback',
        description='展示会议议程或委员会里程碑',
        classes=['timeline', 'timeline-vertical'],
        tokens={'neutral': '#1f2937'},
    ),
    DaisyUIComponent(
        id='stat-card',
        name='统计卡片',
        category='data',
        description='显示注册代表数、议题与 KPI 的信息卡',
        classes=['card', 'bg-base-100', 'shadow-md'],
        tokens={'success': '#22c55e'},
    ),
]


@router.get('/components', response_model=list[DaisyUIComponent])
async def list_components() -> list[DaisyUIComponent]:
    return FAKE_COMPONENT_REGISTRY


@router.post('/theme/preview')
async def preview_theme(payload: ThemePreviewRequest) -> dict[str, str]:
    if payload.theme not in {'munlight', 'business'}:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail='Theme not supported')

    return {
        'message': 'Theme ready for live preview',
        'theme': payload.theme,
    }


@router.post('/components/action')
async def trigger_component_action(payload: ActionPayload) -> dict[str, str]:
    component = next((item for item in FAKE_COMPONENT_REGISTRY if item.id == payload.component_id), None)
    if not component:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail='Component not found')

    return {
        'status': 'queued',
        'action': payload.action,
        'component': component.id,
        'description': f'{component.name} action {payload.action} queued via MCP server',
    }

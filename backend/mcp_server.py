"""Standalone entry point for the daisyUI MCP server.

Run with:
    uvicorn mcp_server:create_app --reload --port 9000
"""

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app.config import settings
from app.routers import mcp_daisyui


def create_app() -> FastAPI:
    app = FastAPI(title='daisyUI MCP Server', version='0.1.0')
    app.add_middleware(
        CORSMiddleware,
        allow_origins=settings.cors_origins,
        allow_credentials=True,
        allow_methods=['*'],
        allow_headers=['*'],
    )
    app.include_router(mcp_daisyui.router)
    return app


app = create_app()

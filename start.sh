#!/bin/bash

# 一键启动 MUN 系统所有服务
# 前端: Vue + Vite (热重载)
# 后端: FastAPI 主 API
# MCP: daisyUI MCP 服务器

set -e

echo "🚀 启动 MUN 系统..."

# 检查依赖
if ! command -v pnpm &> /dev/null; then
    echo "❌ pnpm 未安装，请先安装 pnpm"
    exit 1
fi

if ! command -v uvicorn &> /dev/null; then
    echo "❌ uvicorn 未安装，请在 backend 目录运行: pip install -r requirements.txt"
    exit 1
fi

# 启动后端 API (端口 8000)
echo "📡 启动后端 API..."
cd backend
uvicorn app.main:app --reload --port 8000 &
BACKEND_PID=$!
cd ..

# 启动 MCP 服务器 (端口 9000)
echo "🔧 启动 daisyUI MCP 服务器..."
cd backend
uvicorn mcp_server:app --reload --port 9000 &
MCP_PID=$!
cd ..

# 启动前端 (端口 5173)
echo "🎨 启动前端 (Vue + Vite)..."
cd frontend
pnpm start &
FRONTEND_PID=$!
cd ..

echo "✅ 所有服务已启动!"
echo "📍 前端: http://localhost:5173"
echo "📍 后端 API: http://localhost:8000"
echo "📍 MCP 服务器: http://localhost:9000"
echo ""
echo "按 Ctrl+C 停止所有服务"

# 等待中断信号
trap "echo '🛑 停止服务...'; kill $BACKEND_PID $MCP_PID $FRONTEND_PID; exit" INT

wait
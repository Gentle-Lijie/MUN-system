"""WSGI entrypoint for running the Flask app via `flask run` or gunicorn."""

import sys
from pathlib import Path

# Add the parent directory to sys.path to allow imports
sys.path.insert(0, str(Path(__file__).parent.parent))

from app import create_app

app = create_app()


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8000, debug=True)

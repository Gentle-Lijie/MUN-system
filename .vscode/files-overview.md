# Files Management API Overview

## Endpoints

### File Submissions

- `GET /api/files/submissions`: Presidium views submissions, filterable by status, committee, search.
- `POST /api/files/submissions`: Delegates submit files (title, type, description, content_path).
- `POST /api/files/submissions/{id}/decision`: Presidium approves/rejects with feedback.

### File Publications

- `GET /api/files/published`: All published files, filterable by committee.
- `POST /api/files/published`: Presidium publishes reference files.
- `PATCH /api/files/published/{id}`: Update published file properties.

### Reference and Upload

- `GET /api/files/reference`: Dropdown list for motions/speeches.
- `POST /api/files/upload`: Upload files to /attachments, renamed as userId-timestamp.ext.

## Database Schema

Files table with fields: id, committee_id, type (enum), title, description, content_path, submitted_by, status (enum), visibility (enum), dias_fb, timestamps.

## Frontend Integration

- File approval page: List submissions, click tile for details, approve/reject with feedback.
- File management: Table with title, type, status (as visibility), updated_at, submitter; filter by committee.
- Delegate file center: Show only user's submissions.
- Use daisyUI components for UI.

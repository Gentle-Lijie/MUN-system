# Display Board & Motion Management API Overview

Backend `/api/display/*` and `/api/motions/*` endpoints (PHP) provide display board state management and motion handling for the Vue Display Board view.

## Display Board Endpoints

### GET /api/display/board
- Query parameter: `committeeId` (required)
- Returns complete display board state including:
  - Committee info (id, code, name, venue, status, timeConfig)
  - Statistics (total delegates, present count, majority thresholds)
  - Speaker queue (current speakers with waiting/speaking status)
  - Current session details
  - History events (recent motions with passed/rejected state)
- Time config includes `flowSpeed`, `realTimeAnchor`, and auto-generated `updateTime`
- Formula: `displayTime = realTimeAnchor + (currentTime - updateTime) * flowSpeed`

### POST /api/display/speakers
- Requires authentication
- Body: `{ speakerListId, delegateId }`
- Adds delegate to speaker list queue with auto-incremented position
- Returns created entry with country, delegate name, status

### POST /api/display/roll-call
- Requires authentication
- Body: `{ committeeId, attendance: { delegateId: 'present'|'absent' } }`
- Updates `Delegates.status` field for all provided delegates
- Returns updated statistics (total, present, majority thresholds)
- Used after motion passes or when session starts

### POST /api/display/start-session
- Requires authentication
- Body: `{ committeeId }`
- Changes committee status from `preparation`/`paused` to `in_session`
- Used by frontend overlay button when committee not active

## Motion Endpoints

### POST /api/motions
- Requires authentication
- Creates motion record with vote results
- Body includes: `motionType` (required), `sessionId` or `committeeId`, `proposerId`, `fileId`, time limits, vote data
- For `open_main_list` and `moderated_caucus` motion types:
  - Auto-creates `SpeakerList` record
  - Links to session via `speaker_list_id`
  - Stores `speakerListId` in motion record
- Returns created motion with all fields

### POST /api/motions/{motionId}/{listId}
- Requires authentication
- Updates speaker list entries (position and status)
- Body: `{ entries: [{ id, position?, status? }] }`
- Validates motion owns the speaker list
- Status values: `waiting`, `speaking`, `removed`
- Returns updated speaker list with all entries

## Database Models

### Delegates Table
- Added `status` field: enum('present', 'absent') nullable
- Updated via roll-call endpoint
- Used for calculating present count and majorities

### Sessions Table
- Links committee to speaker list and proposals
- Fields: `type` (main_list/moderated/etc.), time limits, approval status, vote results
- Foreign keys to `Committees`, `Delegates` (proposer), `SpeakerLists`

### SpeakerLists Table
- Container for speaker entries
- Belongs to committee
- Has many entries ordered by position

### SpeakerListEntries Table
- Individual speaker in queue
- Fields: `speaker_list_id`, `delegate_id`, `position`, `status`
- Unique constraint on (speaker_list_id, position)
- Status: waiting → speaking → removed

### Motions Table
- Records motion proposals and results
- Links to session, proposer delegate, file, and speaker list
- State: pending/passed/rejected
- Vote result stored as JSON

## Frontend Integration

Display Board component (`DisplayBoard.vue`) route changed to `/display/:committeeId` to support multiple committees.

Key UI behaviors:
1. Page loads with committeeId from route parameter
2. Fetches board state via GET /api/display/board
3. Shows overlay "开始会议" button if status is preparation/paused
4. Button click calls POST /api/display/start-session then triggers roll-call
5. Motion config panel has checkbox for "发起点名" (default checked for file votes)
6. After motion passes and roll-call checkbox enabled, triggers POST /api/display/roll-call
7. Time display uses formula: `realTimeAnchor + (now - updateTime) * flowSpeed`
8. Statistics (top-right corner) refresh after each roll-call

JSON schema: `backend/json/display.openapi.json`

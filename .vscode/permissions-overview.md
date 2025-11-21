# User Permissions Management

## API Endpoints

- `GET /api/users/{user_id}/permissions`: Retrieve the list of permissions for a specific user.
- `POST /api/users/{user_id}/permissions`: Update the permissions for a specific user by sending a JSON payload with `permissions` as an array of strings.

## Database Changes

- Added `permissions` column to the `users` table as JSON type, defaulting to an empty list.
- Permissions are stored as an array of strings per user, overriding role-based defaults.

## Default Permissions on User Creation

- Admin and Dais: All available permissions.
- Delegate: Delegate-specific permissions (e.g., 'delegate:self', 'documents:submit', 'messages:send').
- Observer: Only 'observer:read'.

## Frontend Integration

- Added "Configure Permissions" button in UserManagementView.vue next to "Reset Password".
- Opens a modal with checkboxes for all possible permissions, grouped by role for bulk selection.
- Supports saving updated permissions via the API.

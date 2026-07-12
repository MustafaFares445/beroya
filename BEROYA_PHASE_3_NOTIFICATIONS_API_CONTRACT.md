# Beroya Phase 3 API Contract: Notifications

This contract covers the notification inbox used by the phase-3 sales workflow.

## Access

- Authentication: `Authorization: Bearer {{auth_token}}`
- The list, mark-read, and delete endpoints act on the authenticated user only

## Endpoints

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/api/notifications` | List the current user notifications |
| `PUT` | `/api/notifications/{notification}/read` | Mark one notification as read |
| `DELETE` | `/api/notifications/{notification}` | Delete one notification |

## List Response

The list endpoint returns the standard success envelope with an extra `unread_count` field inside `data`.

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "notifications": [],
    "unread_count": 0
  }
}
```

Each notification item contains:

| Field | Type | Notes |
|---|---|---|
| `id` | string | Database notification key |
| `type` | string | Laravel notification type |
| `category` | string or null | Example: `sale`, `order` |
| `event` | string or null | Example: `created`, `approved` |
| `title` | string or null | Short title |
| `body` | string or null | Human-readable message |
| `entity_type` | string or null | Example: `Sale` |
| `entity_id` | integer or string or null | Related record ID |
| `meta` | array | Extra payload data; serializes as a JSON object when keyed |
| `read_at` | string or null | Format: `Y-m-d H:i:s` |
| `created_at` | string or null | Format: `Y-m-d H:i:s` |

## Mark Read Response

`PUT /api/notifications/{notification}/read` returns the updated notification payload as `data`.

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": "2f6f6f9c-6f2d-4e12-9d4a-0f04d7f9b8a1",
    "type": "App\\Notifications\\WorkflowNotification",
    "category": "sale",
    "event": "created",
    "title": "Pending sale created",
    "body": "Sale #101 is waiting for approval.",
    "entity_type": "Sale",
    "entity_id": 101,
    "meta": {
      "status": "hold",
      "contract_type": "cash"
    },
    "read_at": "2026-06-14 09:20:00",
    "created_at": "2026-06-14 09:15:00"
  }
}
```

## Delete Response

`DELETE /api/notifications/{notification}` returns success with a `null` data payload.

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": null
}
```

## Frontend Notes

- The list is ordered from newest to oldest.
- `unread_count` is computed from notifications where `read_at` is still `null`.
- If the notification ID does not belong to the authenticated user, the backend rejects it instead of returning another user's record.

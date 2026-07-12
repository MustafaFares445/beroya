# Beroya Phase 3 API Contract: Activity Logs

This contract covers the manager-facing audit log slice from the phase-3 cars workflow update.

## Access

- Authentication: `Authorization: Bearer {{auth_token}}`
- Allowed permission levels: `1` and `2`
- Forbidden response for other users: `403` with `status: failure`

## Endpoint

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/api/activity-logs` | List audit events with optional filters |

## Query Parameters

All filters are optional.

| Name | Type | Notes |
|---|---|---|
| `user_id` | integer | Filters by the actor user ID |
| `gallery_id` | integer | Filters by gallery; gallery managers with a non-zero gallery ID are automatically scoped to their own gallery |
| `action_type` | string | Example: `sale.created`, `sale.completed` |
| `target_type` | string | Example: `Sale`, `Car` |
| `target_id` | integer | Filters by the target record ID |
| `date` | string | Format: `Y-m-d` |

## Response Shape

The success envelope is the standard API response:

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": []
}
```

Each activity log item contains:

| Field | Type | Notes |
|---|---|---|
| `id` | integer | Activity log ID |
| `actor_user_id` | integer | User who triggered the action |
| `actor_user_name` | string or null | Loaded from the actor relation |
| `gallery_id` | integer or null | Gallery that owns the action context |
| `gallery_name` | string or null | Loaded from the gallery relation |
| `action_type` | string | Example: `sale.created` |
| `target_type` | string | Example: `Sale` |
| `target_id` | integer | Target record ID |
| `old_values` | array | Serializes as a JSON object when keyed |
| `new_values` | array | Serializes as a JSON object when keyed |
| `ip_address` | string or null | Request IP address |
| `created_at` | string or null | Format: `Y-m-d H:i:s` |

## Example

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": [
    {
      "id": 12,
      "actor_user_id": 4,
      "actor_user_name": "Yousef Ali",
      "gallery_id": 2,
      "gallery_name": "Aleppo",
      "action_type": "sale.created",
      "target_type": "Sale",
      "target_id": 101,
      "old_values": [],
      "new_values": {
        "status": "hold"
      },
      "ip_address": "127.0.0.1",
      "created_at": "2026-06-14 09:15:00"
    }
  ]
}
```

## Forbidden Example

```json
{
  "status": "failure",
  "message": "لا تملك صلاحية تنفيذ هذا الإجراء",
  "data": "your computer harmly damaged"
}
```

## Frontend Notes

- Gallery managers with a non-zero gallery ID are automatically scoped to their own gallery, even if they send a different `gallery_id` value.
- `old_values` and `new_values` are always present, even when empty.
- The endpoint is read-only and does not accept a request body.

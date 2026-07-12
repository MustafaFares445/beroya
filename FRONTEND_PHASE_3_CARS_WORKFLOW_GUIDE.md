# Frontend Phase 3 API Guide: Cars Workflow Updates

Base URL: `https://beroya.mustafafares.com/api`

This phase covers audit logging, the notification inbox, and the sales workflow screens that drive approvals, completions, and installment contracts.

## User Flow

1. Sales users create or update sale requests from the car detail screen.
2. Managers review the notification inbox and the sales queue.
3. Managers approve sales, complete them, or switch a sale into installment mode.
4. The audit log screen shows who changed what and when.
5. The frontend should refresh the sale detail view from the sale resource after every workflow action.

## Shared Contract

- Use `Authorization: Bearer {{auth_token}}`.
- These endpoints are workflow-sensitive and are usually restricted to manager or sales roles.
- Most of the UI state comes back from the sale or notification resource itself, not from extra follow-up calls.

## Data Types And Event Sets

### Activity Log Action Types

- `car.created`
- `car.updated`
- `car.deleted`
- `car.sale_state_updated`
- `sale.created`
- `sale.updated`
- `sale.approved`
- `sale.completed`
- `sale.deleted`
- `sale.installment_contract.updated`

### Activity Log Targets

- `Car`
- `Sale`

### Notification Categories

- `order`
- `sale`

### Notification Events

- `order.created`
- `order.approved`
- `order.rejected`
- `sale.created`
- `sale.pending`
- `sale.completed`
- `sale.approved`
- `sale.deleted`
- `sale.installment_contract.updated`

### Sale Values

- `status`: `hold` or `done`
- `contract_type`: `cash` or `installment`
- `approved`: string-like flag, usually `0` or `1`
- `date`: `Y-m-d`
- `requested_at`, `approved_at`, `completed_at`, `created_at`, `updated_at`: `Y-m-d H:i:s`

## 1) Activity Logs

### Route Map

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `GET` | `/api/activity-logs` | query | `ActivityLogResource[]` |

### Query Filters

- `user_id` integer, optional.
- `gallery_id` integer, optional.
- `action_type` string, optional.
- `target_type` string, optional.
- `target_id` integer, optional.
- `date` `Y-m-d`, optional.

Example:

`GET /api/activity-logs?user_id=2&gallery_id=1&action_type=sale.created&date=2026-06-14`

### `ActivityLogResource`

- `id` integer.
- `actor_user_id` integer.
- `actor_user_name` string or `null`.
- `gallery_id` integer or `null`.
- `gallery_name` string or `null`.
- `action_type` string.
- `target_type` string.
- `target_id` integer.
- `old_values` object or array.
- `new_values` object or array.
- `ip_address` string or `null`.
- `created_at` datetime string or `null`.

### Response Example

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": [
    {
      "id": 501,
      "actor_user_id": 2,
      "actor_user_name": "manager",
      "gallery_id": 1,
      "gallery_name": "Aleppo",
      "action_type": "sale.created",
      "target_type": "Sale",
      "target_id": 101,
      "old_values": [],
      "new_values": {
        "status": "hold",
        "contract_type": "cash"
      },
      "ip_address": "127.0.0.1",
      "created_at": "2026-06-14 09:30:00"
    }
  ]
}
```

Frontend use:

- Build manager audit screens and filters from this endpoint.
- Treat `old_values` and `new_values` as JSON blobs.

## 2) Notifications

### Route Map

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `GET` | `/api/notifications` | none | inbox payload |
| `PUT` | `/api/notifications/{notification}/read` | none | `NotificationResource` |
| `DELETE` | `/api/notifications/{notification}` | none | empty `data` |

### Inbox Payload

`GET /api/notifications` returns:

- `data.notifications`
- `data.unread_count`

### `NotificationResource`

- `id` integer.
- `type` string. This is the database notification class name.
- `category` string or `null`.
- `event` string or `null`.
- `title` string or `null`.
- `body` string or `null`.
- `entity_type` string or `null`.
- `entity_id` integer or string or `null`.
- `meta` object or array.
- `read_at` datetime string or `null`.
- `created_at` datetime string or `null`.

### Inbox Response Example

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "notifications": [
      {
        "id": "00000000-0000-0000-0000-000000000001",
        "type": "App\\Notifications\\WorkflowNotification",
        "category": "sale",
        "event": "created",
        "title": "Pending sale created",
        "body": "Sale #101 is waiting for approval.",
        "entity_type": "Sale",
        "entity_id": 101,
        "meta": {
          "status": "hold",
          "contract_type": "cash",
          "car_name": "BMW X5",
          "gallery_id": 1,
          "ip_address": "127.0.0.1"
        },
        "read_at": null,
        "created_at": "2026-06-14 09:30:00"
      }
    ],
    "unread_count": 1
  }
}
```

### Mark Read Response Example

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": "00000000-0000-0000-0000-000000000001",
    "type": "App\\Notifications\\WorkflowNotification",
    "category": "sale",
    "event": "created",
    "title": "Pending sale created",
    "body": "Sale #101 is waiting for approval.",
    "entity_type": "Sale",
    "entity_id": 101,
    "meta": {
      "status": "hold",
      "contract_type": "cash",
      "car_name": "BMW X5",
      "gallery_id": 1,
      "ip_address": "127.0.0.1"
    },
    "read_at": "2026-06-14 09:40:00",
    "created_at": "2026-06-14 09:30:00"
  }
}
```

Frontend use:

- Render the inbox from `data.notifications`.
- Use `data.unread_count` for the badge counter.
- Mark-read and delete act on the authenticated user only.

## 3) Sales Workflow

### Route Map

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `GET` | `/api/sales` | query | `SaleResource[]` |
| `POST` | `/api/sales` | multipart | `SaleResource` |
| `GET` | `/api/sales/{sale}` | none | `SaleResource` |
| `PUT` | `/api/sales/{sale}` | multipart | `SaleResource` |
| `PUT` | `/api/sales/{sale}/installment-contract` | JSON | `SaleResource` |
| `PUT` | `/api/sales/{sale}/complete` | JSON | `SaleResource` |
| `PUT` | `/api/sales/{sale}/approve-order` | JSON | `SaleResource` |
| `DELETE` | `/api/sale-orders/{sale}` | none | deleted sale snapshot |

### List Filters

- `status` required, use `hold` or `done`.
- `gallery_id` optional.

Example:

`GET /api/sales?status=hold&gallery_id=1`

### `SaleResource`

- `id` integer.
- `user_comiss` integer.
- `user_note` string.
- `buyer_name` string.
- `buyer_phone` string or integer-like value.
- `owner_comiss` integer.
- `owner_comiss_payed` integer.
- `buyer_comiss` integer.
- `buyer_comiss_payed` integer.
- `owner_id_image` string.
- `buyer_id_image` string.
- `contract_image` string.
- `date` date string.
- `week_id` integer.
- `car_brand` string.
- `car_model` string.
- `car_name` string.
- `user_id` integer.
- `car_id` integer.
- `car_number` string.
- `price` integer.
- `employee_name` string.
- `owner_name` string.
- `owner_phone` string.
- `status` string.
- `requested_at` datetime string or `null`.
- `approved_at` datetime string or `null`.
- `completed_at` datetime string or `null`.
- `contract_type` string.
- `installment_count` integer or `null`.
- `installment_amount` integer or `null`.
- `installment_start_date` date string or `null`.
- `installment_end_date` date string or `null`.
- `installment_note` string or `null`.
- `created_at` datetime string or `null`.
- `updated_at` datetime string or `null`.
- `approved` string-like flag.

### Create Or Update Request Body

```json
{
  "car_id": 1,
  "car_brand": "BMW",
  "car_model": "X5",
  "car_number": "123456",
  "car_name": "BMW X5",
  "price": 25000,
  "employee_name": "Employee",
  "user_comiss": 200,
  "user_note": "Note",
  "owner_name": "Owner",
  "owner_phone": "0999999999",
  "owner_comiss": 0,
  "owner_comiss_payed": 0,
  "buyer_name": "Buyer",
  "buyer_phone": 912345678,
  "buyer_comiss": 0,
  "buyer_comiss_payed": 0,
  "contract_type": "cash",
  "installment_count": null,
  "installment_amount": null,
  "installment_start_date": null,
  "installment_end_date": null,
  "installment_note": null,
  "date": "2026-01-05",
  "user_id": 4,
  "owner_id_image": null,
  "buyer_id_image": null,
  "contract_image": null
}
```

Files:

- `owner_id_image`
- `buyer_id_image`
- `contract_image`

These are multipart uploads and each one accepts a file up to 50 MB.

### Workflow Action Bodies

Approve order:

```json
{
  "ownerName": "Approved Owner",
  "ownerPhone": "0999111111"
}
```

Complete sale:

```json
{
  "user_comiss": 200,
  "owner_comiss": 0,
  "owner_comiss_payed": 0,
  "buyer_comiss": 0,
  "buyer_comiss_payed": 0,
  "employee_name": "Manager",
  "user_note": "Completed"
}
```

Installment contract:

```json
{
  "installment_count": 24,
  "installment_amount": 1000,
  "installment_start_date": "2026-02-01",
  "installment_end_date": "2028-01-01",
  "installment_note": "Monthly installments"
}
```

### Sale Response Example

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": 101,
    "user_comiss": 200,
    "user_note": "Completed",
    "buyer_name": "Buyer",
    "buyer_phone": 912345678,
    "owner_comiss": 0,
    "owner_comiss_payed": 0,
    "buyer_comiss": 0,
    "buyer_comiss_payed": 0,
    "owner_id_image": "",
    "buyer_id_image": "",
    "contract_image": "",
    "date": "2026-01-05",
    "week_id": 12,
    "car_brand": "BMW",
    "car_model": "X5",
    "car_name": "BMW X5",
    "user_id": 4,
    "car_id": 1,
    "car_number": "123456",
    "price": 25000,
    "employee_name": "Manager",
    "owner_name": "Approved Owner",
    "owner_phone": "0999111111",
    "status": "done",
    "requested_at": "2026-06-14 09:30:00",
    "approved_at": "2026-06-14 09:35:00",
    "completed_at": "2026-06-14 09:40:00",
    "contract_type": "cash",
    "installment_count": 24,
    "installment_amount": 1000,
    "installment_start_date": "2026-02-01",
    "installment_end_date": "2028-01-01",
    "installment_note": "Monthly installments",
    "created_at": "2026-06-14 09:30:00",
    "updated_at": "2026-06-14 09:40:00",
    "approved": "1"
  }
}
```

### Workflow Notes

- `requested_at` is set automatically when a sale is created.
- `approve-order` updates the owner contact data and stamps `approved_at`.
- `complete` stamps `completed_at`, updates the car sale state, and recalculates the related account totals.
- `installment-contract` switches the sale to installment mode and stores the installment schedule fields.
- `DELETE /api/sale-orders/{sale}` removes the sale order and may also update the related car and account state.

### Delete Response Example

`DELETE /api/sale-orders/{sale}` returns the deleted sale snapshot as a `SaleResource`, not a null payload.

## Frontend Notes

- Use the activity log screen for manager audits and troubleshooting.
- Use notifications for new orders and sale workflow events.
- Use the sales screens for hold/done lists, approval, completion, and installment management.
- Keep the sale detail view in sync with the resource fields above instead of rebuilding state from separate calls.

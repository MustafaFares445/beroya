# Beroya Phase 3 API Contract: Sales Workflow

This contract covers the phase-3 sales workflow endpoints that the frontend needs for hold/done sales, approval, completion, and installment contracts.

## Access

- Sales list/create/show/update access: permission levels `1`, `2`, `3`, and `4`
- Workflow actions (`approve-order`, `complete`, `installment-contract`, delete sale order): permission levels `1` and `2`
- Authentication header: `Authorization: Bearer {{auth_token}}`

## Endpoints

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/api/sales` | List sales by status |
| `POST` | `/api/sales` | Create a sale |
| `GET` | `/api/sales/{sale}` | Show a sale |
| `PUT` | `/api/sales/{sale}` | Update a sale |
| `PUT` | `/api/sales/{sale}/approve-order` | Approve owner data for a sale request |
| `PUT` | `/api/sales/{sale}/complete` | Finalize a sale and store completion timestamps |
| `PUT` | `/api/sales/{sale}/installment-contract` | Save installment contract data |
| `DELETE` | `/api/sale-orders/{sale}` | Delete a sale order |

## List Contract

`GET /api/sales` requires these query parameters:

| Name | Type | Notes |
|---|---|---|
| `status` | string | Required. Use `hold` or `done` |
| `gallery_id` | integer | Optional for admin, recommended for gallery users |

Example:

```text
/api/sales?status=hold&gallery_id=2
```

## Sale Resource Response

The sale endpoints return the `SaleResource` payload.

Key fields returned to the frontend:

| Field | Notes |
|---|---|
| `id` | Sale ID |
| `user_comiss` | User commission |
| `user_note` | Sale note |
| `buyer_name` | Buyer name |
| `buyer_phone` | Buyer phone |
| `owner_comiss` | Owner commission |
| `owner_comiss_payed` | Owner commission paid flag/value |
| `buyer_comiss` | Buyer commission |
| `buyer_comiss_payed` | Buyer commission paid flag/value |
| `owner_id_image` | Owner ID upload name |
| `buyer_id_image` | Buyer ID upload name |
| `contract_image` | Contract upload name |
| `date` | `Y-m-d` |
| `week_id` | Related week ID |
| `car_brand` | Car brand |
| `car_model` | Car model |
| `car_name` | Car name |
| `user_id` | Seller user ID |
| `car_id` | Car ID |
| `car_number` | Car number |
| `price` | Sale price |
| `employee_name` | Employee name |
| `owner_name` | Owner name |
| `owner_phone` | Owner phone |
| `status` | `hold` or `done` |
| `requested_at` | Auto-set when the sale is created |
| `approved_at` | Set when the sale is approved or completed |
| `completed_at` | Set when the sale is completed |
| `contract_type` | `cash` or `installment` |
| `installment_count` | Installment count |
| `installment_amount` | Installment amount |
| `installment_start_date` | `Y-m-d` |
| `installment_end_date` | `Y-m-d` |
| `installment_note` | Installment note |
| `created_at` | `Y-m-d H:i:s` |
| `updated_at` | `Y-m-d H:i:s` |
| `approved` | String flag `0` or `1` |

## Create Or Update Sale Payload

`POST /api/sales` and `PUT /api/sales/{sale}` use the same field contract.

| Name | Type | Notes |
|---|---|---|
| `car_id` | integer | Required |
| `car_brand` | string | Optional |
| `car_model` | string | Optional |
| `car_number` | string | Optional |
| `car_name` | string | Required |
| `price` | integer | Required |
| `employee_name` | string | Optional |
| `user_comiss` | integer | Optional |
| `user_note` | string | Optional |
| `owner_name` | string | Optional |
| `owner_phone` | string | Optional |
| `owner_comiss` | integer | Optional |
| `owner_comiss_payed` | integer | Optional |
| `buyer_name` | string | Optional |
| `buyer_phone` | integer | Optional |
| `buyer_comiss` | integer | Optional |
| `buyer_comiss_payed` | integer | Optional |
| `contract_type` | string | Optional. Use `cash` or `installment` |
| `installment_count` | integer | Optional |
| `installment_amount` | integer | Optional |
| `installment_start_date` | string | Optional. `Y-m-d` |
| `installment_end_date` | string | Optional. `Y-m-d` |
| `installment_note` | string | Optional |
| `date` | string | Required. `Y-m-d` |
| `user_id` | integer | Required |
| `owner_id_image` | file | Optional upload |
| `buyer_id_image` | file | Optional upload |
| `contract_image` | file | Optional upload |

If any file field is sent, use `multipart/form-data`.

## Workflow Endpoints

### Approve Order

`PUT /api/sales/{sale}/approve-order`

Request body:

| Name | Type | Notes |
|---|---|---|
| `ownerName` | string | Required |
| `ownerPhone` | string | Required |

Result:

- Sets `approved` to `1`
- Updates `owner_name` and `owner_phone`
- Sets `approved_at`
- Emits an activity log and a notification

### Complete Sale

`PUT /api/sales/{sale}/complete`

Request body:

| Name | Type | Notes |
|---|---|---|
| `user_comiss` | integer | Optional |
| `owner_comiss` | integer | Optional |
| `owner_comiss_payed` | integer | Optional |
| `buyer_comiss` | integer | Optional |
| `buyer_comiss_payed` | integer | Optional |
| `employee_name` | string | Optional |
| `user_note` | string | Optional |

Result:

- Sets `status` to `done`
- Sets `approved_at` if it is still empty
- Sets `completed_at`
- Updates the car sale state
- Recalculates the related account totals

### Installment Contract

`PUT /api/sales/{sale}/installment-contract`

Request body:

| Name | Type | Notes |
|---|---|---|
| `installment_count` | integer | Required |
| `installment_amount` | integer | Required |
| `installment_start_date` | string | Required. `Y-m-d` |
| `installment_end_date` | string | Required. `Y-m-d` |
| `installment_note` | string | Optional |

Result:

- Forces `contract_type` to `installment`
- Stores the installment contract fields on the sale
- Emits an activity log and a notification

### Delete Sale Order

`DELETE /api/sale-orders/{sale}`

Result:

- Deletes the sale order
- Removes uploaded sale images when present
- Restores the related car sale state when the sale was not already done
- Recalculates the account totals when needed
- Emits an activity log and a notification

## Example Create Response

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": 101,
    "status": "hold",
    "approved": "0",
    "requested_at": "2026-06-14 09:10:00",
    "approved_at": null,
    "completed_at": null
  }
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

- The list endpoint uses `status` in the query string, not the request body.
- `approve-order` uses camelCase fields `ownerName` and `ownerPhone`.
- `requested_at` is set automatically when the sale is created.
- The sales workflow triggers audit logs and manager notifications, so the frontend should expect those side effects without making extra calls.

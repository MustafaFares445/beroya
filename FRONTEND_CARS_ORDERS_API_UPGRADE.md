# Frontend Cars And Orders API Upgrade

Base URL: `https://beroya.mustafafares.com/api`

This contract covers the new backend additions for the cars and orders screens:

- Car filtering by `market_id`
- Car filtering by `market_id=0` with `model_id`
- Latest cars feed limited to 40 rows
- Order `checked` flag updates
- `created_at` and `updated_at` on cars and orders

## Shared Contract

- Send authenticated requests with `Authorization: Bearer {{auth_token}}` when the route is protected.
- Keep the existing field names exactly as the backend expects them.
- Use `Y-m-d H:i:s` for all datetime strings in this contract.
- The success envelope stays:

```json
{
  "status": "success",
  "message": "string",
  "data": {}
}
```

- Forbidden writes usually return:

```json
{
  "status": "failure",
  "message": "message key resolved in Arabic",
  "data": "your computer harmly damaged"
}
```

## Data Types

- `car_sale_state`: integer workflow code.
- `checked`: integer-like boolean flag, `0` or `1`.
- `market_id`: integer.
- `model_id`: integer.
- `created_at`, `updated_at`: `Y-m-d H:i:s`.

## 1) Cars

### Route Map

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `GET` | `/api/cars` | query | `CarResource[]` |
| `GET` | `/api/cars/latest` | none | `CarResource[]` |
| `GET` | `/api/cars/{car}` | none | `CarResource` |

### List Filters

- `market_id` optional.
- If `market_id` is a positive integer, the backend filters cars by `market_id`.
- If `market_id=0`, the backend uses `model_id` instead of market filtering.
- `model_id` is required when `market_id=0`.

Examples:

`GET /api/cars?market_id=1`

`GET /api/cars?market_id=0&model_id=12`

### Latest Cars

- Returns the newest 40 cars.
- Excludes cars where `car_sale_state = 4`.
- Sort order is newest first.

Example:

`GET /api/cars/latest`

### `CarResource`

- `id` integer.
- `market_id` integer.
- `model_id` integer.
- `year` string.
- `gasoline` string.
- `engine` string.
- `transmission` string.
- `color` string.
- `distance` string.
- `imported` string.
- `spray` string.
- `status` string.
- `description` string.
- `plateNumber` string.
- `notes` string.
- `price` integer.
- `possession` string.
- `owner_name` string.
- `owner_phone` string.
- `gallery_id` integer.
- `image_1` to `image_6` string file names.
- `car_sale_state` integer.
- `created_at` datetime string or `null`.
- `updated_at` datetime string or `null`.

### Car Response Example

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": 1,
    "market_id": 1,
    "model_id": 2,
    "year": "2023",
    "gasoline": "gasoline",
    "engine": "2.0",
    "transmission": "automatic",
    "color": "black",
    "distance": "10000",
    "imported": "yes",
    "spray": "none",
    "status": "available",
    "description": "Clean condition",
    "plateNumber": "123456",
    "notes": "new car",
    "price": 20000,
    "possession": "owner",
    "owner_name": "Owner One",
    "owner_phone": "0999999999",
    "gallery_id": 1,
    "image_1": "",
    "image_2": "",
    "image_3": "",
    "image_4": "",
    "image_5": "",
    "image_6": "",
    "car_sale_state": 1,
    "created_at": "2026-07-03 12:00:00",
    "updated_at": "2026-07-03 12:00:00"
  }
}
```

### Visibility Note

- Public car reads are still sanitized for guests.
- Guests receive blank values for `plateNumber`, `owner_name`, and `owner_phone`.
- Authenticated users with permission level `1`, `2`, or `3` receive the full car payload.

## 2) Orders

### Route Map

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `GET` | `/api/orders` | none | `OrderResource[]` |
| `POST` | `/api/orders` | JSON | `OrderResource` |
| `GET` | `/api/orders/{order}` | none | `OrderResource` |
| `PUT` | `/api/orders/{order}` | JSON | `OrderResource` |
| `PUT` | `/api/orders/{order}/checked` | JSON | `OrderResource` |
| `PUT` | `/api/orders/{order}/approve` | JSON | `OrderResource` |
| `PUT` | `/api/orders/{order}/reject` | JSON | `OrderResource` |
| `DELETE` | `/api/orders/{order}` | none | `{"id": <order_id>}` |

### `OrderResource`

- `id` integer.
- `client_name` string.
- `client_phone` string.
- `car_market` string.
- `car_model` string.
- `year` string.
- `price_low` integer.
- `price_high` integer.
- `order_state` string.
- `order_notes` string.
- `user_name` string.
- `gallery_name` string.
- `checked` integer, usually `0` or `1`.
- `approved_at` datetime string or `null`.
- `rejected_at` datetime string or `null`.
- `reviewed_by_user_id` integer or `null`.
- `reject_reason` string or `null`.
- `created_at` datetime string or `null`.
- `updated_at` datetime string or `null`.

### Create Or Update Request Body

```json
{
  "client_name": "Client One",
  "client_phone": "0999999999",
  "car_market": "BMW",
  "car_model": "X5",
  "year": "2023",
  "price_low": 10000,
  "price_high": 20000,
  "order_state": "open",
  "order_notes": "Need low mileage",
  "user_name": "employee",
  "gallery_name": "Aleppo"
}
```

### Checked Update Body

```json
{
  "checked": 1
}
```

### Checked Workflow Notes

- `checked` defaults to `0` when the order is created.
- `PUT /api/orders/{order}/checked` changes only the `checked` flag.
- The `checked` update does not change `order_state`.
- The endpoint uses the same authenticated order-management boundary as other order write routes.

### Order Response Example

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": 1,
    "client_name": "Client One",
    "client_phone": "0999999999",
    "car_market": "BMW",
    "car_model": "X5",
    "year": "2023",
    "price_low": 10000,
    "price_high": 20000,
    "order_state": "open",
    "order_notes": "Need low mileage",
    "user_name": "employee",
    "gallery_name": "Aleppo",
    "checked": 0,
    "approved_at": null,
    "rejected_at": null,
    "reviewed_by_user_id": null,
    "reject_reason": null,
    "created_at": "2026-07-03 12:00:00",
    "updated_at": "2026-07-03 12:00:00"
  }
}
```

## Frontend Notes

- Use `/api/cars/latest` for the newest-cars list instead of filtering the full cars list in the UI.
- Use `market_id=0&model_id=...` when the UI is selecting by model only.
- Keep `checked` as a separate UI state on the order screen.
- Treat `created_at` and `updated_at` as read-only metadata.

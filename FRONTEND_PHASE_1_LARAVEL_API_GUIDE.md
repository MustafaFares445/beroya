# Frontend Phase 1 API Guide: Core Laravel Migration

Base URL: `https://beroya.mustafafares.com/api`

This phase covers the core admin surface: login, users, galleries, markets, cars, orders, weeks, and weekly accounting. The frontend should keep the current field names and route IDs, then progressively replace the old gateway screens with these Laravel endpoints.

## User Flow

1. Sign in with username and password.
2. Cache the returned token and user profile in the app state.
3. Load the master data screens used by the CRUD pages.
4. Create and edit cars, orders, and weekly accounts from route-id based forms.
5. Use the accounts screens to manage bonuses, deductions, and received status.

## Shared Contract

- Send authenticated requests with `Authorization: Bearer {{auth_token}}`.
- Use `GET` for reads, `POST` for creates, `PUT` for updates, and `DELETE` for removals.
- Send route IDs in the URL path when the route expects them.
- Use `multipart/form-data` for any request that uploads files.
- Keep existing request field names exactly as the backend expects them.
- The success envelope is always:

```json
{
  "status": "success",
  "message": "string",
  "data": {}
}
```

- `data` may be an array, an object, `null`, or an object with extra meta keys such as `token`, `token_expiry`, or `unread_count`.
- Forbidden writes usually return:

```json
{
  "status": "failure",
  "message": "message key resolved in Arabic",
  "data": "your computer harmly damaged"
}
```

## Data Types And Values

- `permetions_level`: integer role gate used by the backend. The code checks levels `1`, `2`, `3`, and `4` depending on the screen.
- `contract_type`: `cash` or `installment`.
- `received`: string flag, only `0` or `1`.
- `car_sale_state`: integer workflow code. The backend starts cars at `1`, moves them to `2` from the dedicated sale-state endpoint, and moves them to `3` when a sale is completed.
- `order_state`: string. The create/update screens can send a custom value, but approve/reject workflow actions normalize it to `approved` or `rejected`.
- `status` on sales list filters: `hold` or `done`.
- `date`: `Y-m-d`.
- `created_at`, `updated_at`, `requested_at`, `approved_at`, `completed_at`, `last_login`: `Y-m-d H:i:s`.
- Files must be sent as multipart uploads.

## 1) Auth

### `POST /api/auth/login`

Purpose: authenticate the user and return the token plus the current profile.

Request body:

```json
{
  "username": "admin",
  "password": "admin123",
  "currentWeekNum": 22,
  "currentYear": 2026
}
```

Request fields:

- `username` string, required, max 255.
- `password` string, required.
- `currentWeekNum` integer, optional, between 1 and 53.
- `currentYear` integer, optional, 4 digits.

Success response example:

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": 1,
    "user_name": "admin",
    "gallery_id": 1,
    "real_estate_office_id": 2,
    "real_estate_office_name": "Damascus Center",
    "real_estate_province_id": 1,
    "real_estate_province_name": "Damascus",
    "real_estate_role": "province_manager",
    "real_estate_role_label": "مدير محافظة",
    "permetions_level": 1,
    "salary": 2500,
    "phone": "0999000001",
    "token": "1|sample-sanctum-token",
    "token_expiry": "2026-06-14 12:00:00"
  }
}
```

Notes:

- The backend uses the optional week and year to seed the current week and account records after login.
- The frontend should store `data.token` as the bearer token and `data.token_expiry` as the token expiry timestamp.

### `POST /api/auth/logout`

Purpose: revoke the current Sanctum token.

Request body: none.

Success response example:

```json
{
  "status": "success",
  "message": "تم تسجيل الخروج بنجاح",
  "data": {
    "id": 1
  }
}
```

### `POST /api/auth/bootstrap-admin`

Purpose: development-only helper that creates the initial admin accounts.

Request body: none.

Success response example:

```json
{
  "status": "success",
  "message": "تم إنشاء الحساب الإداري بنجاح",
  "data": {
    "admin": {
      "user_name": "admin",
      "password": "R7d3F2a9X1c8b5e0",
      "note": "Save the temporary password and change it after first login."
    },
    "financial": {
      "user_name": "المدير المالي",
      "password": "T8k1M4p6Q2n9v3s7"
    },
    "guest": {
      "user_name": "guest",
      "password": "H5j2L8x4Z7r1w9c3"
    }
  }
}
```

### `PUT /api/users/{user}/password`

Purpose: allow a user to change only their own password.

Request body:

```json
{
  "old_password": "old-secret",
  "new_password": "new-secret",
  "id": 1
}
```

Request fields:

- `old_password` string, required.
- `new_password` string, required.
- `id` integer, optional legacy compatibility field.

Success response example:

```json
{
  "status": "success",
  "message": "تم تحديث كلمة المرور بنجاح",
  "data": {
    "id": 1
  }
}
```

## 2) Users

### Route Map

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `GET` | `/api/users` | none | `UserResource[]` |
| `POST` | `/api/users` | JSON | `UserResource` |
| `GET` | `/api/users/{user}` | none | `UserResource` |
| `PUT` | `/api/users/{user}` | JSON | `UserResource` |
| `DELETE` | `/api/users/{user}` | none | empty `data` |

### `UserResource`

- `id` integer.
- `user_name` string.
- `gallery_id` integer.
- `real_estate_office_id` integer or `null`.
- `real_estate_office_name` string or `null`.
- `real_estate_province_id` integer or `null`.
- `real_estate_province_name` string or `null`.
- `real_estate_role` string or `null`.
- `real_estate_role_label` string or `null`.
- `permetions_level` integer or `null`.
- `salary` integer.
- `phone` string.
- `last_login` datetime string or `null`.
- `is_active` boolean.

### Request Body For Create And Update

```json
{
  "user_name": "sales2",
  "password": "sales123",
  "gallery_id": 1,
  "real_estate_office_id": 2,
  "real_estate_role": "office_employee",
  "permetions_level": 4,
  "salary": 1300,
  "phone": "0999000003"
}
```

Request fields:

- `user_name` string, required, max 255.
- `password` string, required on create and optional on update.
- `gallery_id` integer, required.
- `real_estate_office_id` integer, optional.
- `real_estate_role` string, optional.
- `permetions_level` integer, required.
- `salary` integer, required.
- `phone` string, required, max 20.

Success response example:

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": 3,
    "user_name": "sales2",
    "gallery_id": 1,
    "real_estate_office_id": 2,
    "real_estate_office_name": "Damascus Center",
    "real_estate_province_id": 1,
    "real_estate_province_name": "Damascus",
    "real_estate_role": "office_employee",
    "real_estate_role_label": "موظف مكتب",
    "permetions_level": 4,
    "salary": 1300,
    "phone": "0999000003",
    "last_login": null,
    "is_active": true
  }
}
```

Delete response example:

```json
{
  "status": "success",
  "message": "تم حذف المستخدم بنجاح",
  "data": null
}
```

## 3) Galleries And Gallery Phones

### Gallery Routes

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `GET` | `/api/galleries` | none | `GalleryResource[]` |
| `GET` | `/api/galleries/{gallery}` | none | `GalleryResource` |
| `POST` | `/api/galleries` | JSON | `GalleryResource` |
| `PUT` | `/api/galleries/{gallery}` | JSON | `GalleryResource` |
| `DELETE` | `/api/galleries/{gallery}` | none | `{"id": <gallery_id>}` |

### Gallery Request Body

```json
{
  "name": "Aleppo",
  "address": "Main Street"
}
```

### Gallery Response Example

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": 1,
    "name": "Aleppo",
    "address": "Main Street"
  }
}
```

### Gallery Phone Routes

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `GET` | `/api/gallery-phones` | none | `GalleryPhoneResource[]` |
| `GET` | `/api/gallery-phones/{galleryPhone}` | none | `GalleryPhoneResource` |
| `POST` | `/api/gallery-phones` | JSON | `GalleryPhoneResource` |
| `PUT` | `/api/gallery-phones/{galleryPhone}` | JSON | `GalleryPhoneResource` |
| `DELETE` | `/api/gallery-phones/{galleryPhone}` | none | `{"id": <gallery_phone_id>}` |

### Gallery Phone Request Body

```json
{
  "phone": "0999000001",
  "gallery_id": 1
}
```

### Gallery Phone Response Example

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": 1,
    "phone": "0999000001",
    "gallery_id": 1
  }
}
```

## 4) Markets And Car Models

### Market Routes

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `GET` | `/api/markets` | none | `MarketResource[]` |
| `GET` | `/api/markets/{market}` | none | `MarketResource` |
| `POST` | `/api/markets` | multipart | `MarketResource` |
| `PUT` | `/api/markets/{market}` | multipart | `MarketResource` |
| `DELETE` | `/api/markets/{market}` | none | deleted market resource |

### Market Request Body

```json
{
  "name": "BMW"
}
```

Send the image as `multipart/form-data` in a field named `image`.

### Market Response Example

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": 1,
    "name": "BMW",
    "image": "bmw.webp"
  }
}
```

### Car Model Routes

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `GET` | `/api/car-models` | none | `CarModelResource[]` |
| `GET` | `/api/car-models/{carModel}` | none | `CarModelResource` |
| `POST` | `/api/car-models` | JSON | `CarModelResource` |
| `PUT` | `/api/car-models/{carModel}` | JSON | `CarModelResource` |
| `DELETE` | `/api/car-models/{carModel}` | none | `{"id": <car_model_id>}` |

### Car Model Request Body

```json
{
  "name": "X5",
  "market_id": 1
}
```

### Car Model Response Example

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": 1,
    "name": "X5",
    "market_id": 1
  }
}
```

## 5) Cars

### Route Map

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `GET` | `/api/cars` | none | `CarResource[]` |
| `GET` | `/api/cars/{car}` | none | `CarResource` |
| `POST` | `/api/cars` | multipart | `CarResource` |
| `PUT` | `/api/cars/{car}` | multipart | `CarResource` |
| `PUT` | `/api/cars/{car}/sale-state` | JSON | `CarResource` |
| `DELETE` | `/api/cars/{car}` | none | `{"id": <car_id>}` |

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
- `car_sale_state` integer workflow code.

### Create Or Update Request Body

```json
{
  "market_id": 1,
  "model_id": 1,
  "year": 2023,
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
  "gallery_id": 1
}
```

Upload car photos as `image_1` to `image_6` in multipart form data. The backend also accepts the legacy aliases `image1` to `image6`.

On update, the backend also accepts:

- `delete_image1` to `delete_image6` boolean-like flags.

### Sale-State Request Body

```json
{
  "sale_state": 2,
  "edit_type": "prim"
}
```

Request fields:

- `sale_state` integer, required.
- `edit_type` string, optional, max 20.

### Car Response Example

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": 1,
    "market_id": 1,
    "model_id": 1,
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
    "image_1": "car1.jpg",
    "image_2": "",
    "image_3": "",
    "image_4": "",
    "image_5": "",
    "image_6": "",
    "car_sale_state": 1
  }
}
```

Frontend note:

- Public car reads are sanitized for guests. The backend blanks `plateNumber`, `owner_name`, and `owner_phone` unless the viewer is authenticated with permission level `1`, `2`, or `3`.

## 6) Orders

### Route Map

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `GET` | `/api/orders` | none | `OrderResource[]` |
| `POST` | `/api/orders` | JSON | `OrderResource` |
| `GET` | `/api/orders/{order}` | none | `OrderResource` |
| `PUT` | `/api/orders/{order}` | JSON | `OrderResource` |
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
- `approved_at` datetime string or `null`.
- `rejected_at` datetime string or `null`.
- `reviewed_by_user_id` integer or `null`.
- `reject_reason` string or `null`.

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

### Reject Request Body

```json
{
  "reject_reason": "The requested price range is not available."
}
```

Approve uses the route only and updates the order to `approved`. Reject uses `reject_reason` and updates the order to `rejected`.

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
    "order_state": "approved",
    "order_notes": "Need low mileage",
    "user_name": "employee",
    "gallery_name": "Aleppo",
    "approved_at": "2026-06-14 09:30:00",
    "rejected_at": null,
    "reviewed_by_user_id": 2,
    "reject_reason": null
  }
}
```

## 7) Sales And Weeks

### Sale Route Map

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `GET` | `/api/sales` | query | `SaleResource[]` |
| `POST` | `/api/sales` | multipart | `SaleResource` |
| `GET` | `/api/sales/{sale}` | none | `SaleResource` |
| `PUT` | `/api/sales/{sale}` | multipart | `SaleResource` |
| `DELETE` | `/api/sale-orders/{sale}` | none | deleted sale snapshot |

### Sale List Query

- `status` required. Use `hold` or `done`.
- `gallery_id` optional. If omitted, the backend falls back to the authenticated user gallery.

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
- `status` string, `hold` or `done`.
- `requested_at` datetime string or `null`.
- `approved_at` datetime string or `null`.
- `completed_at` datetime string or `null`.
- `contract_type` string, `cash` or `installment`.
- `installment_count` integer or `null`.
- `installment_amount` integer or `null`.
- `installment_start_date` date string or `null`.
- `installment_end_date` date string or `null`.
- `installment_note` string or `null`.
- `created_at` datetime string.
- `updated_at` datetime string.
- `approved` string-like flag, usually `0` or `1`.

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

### Sale Response Example

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": 101,
    "user_comiss": 200,
    "user_note": "Note",
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
    "employee_name": "Employee",
    "owner_name": "Owner",
    "owner_phone": "0999999999",
    "status": "hold",
    "requested_at": "2026-06-14 09:30:00",
    "approved_at": null,
    "completed_at": null,
    "contract_type": "cash",
    "installment_count": null,
    "installment_amount": null,
    "installment_start_date": null,
    "installment_end_date": null,
    "installment_note": null,
    "created_at": "2026-06-14 09:30:00",
    "updated_at": "2026-06-14 09:30:00",
    "approved": "0"
  }
}
```

### Week Routes

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `GET` | `/api/weeks` | none | `WeekResource[]` |

### `WeekResource`

- `id` integer.
- `week_num` integer.
- `year` integer.
- `start_date` date string or `null`.
- `end_date` date string or `null`.

### Week Response Example

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": [
    {
      "id": 12,
      "week_num": 22,
      "year": 2026,
      "start_date": "2026-05-29",
      "end_date": "2026-06-04"
    }
  ]
}
```

## 8) Accounts, Bonuses, And Deductions

### Weekly Account Routes

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `GET` | `/api/accounts/weekly` | query | `AccountResource[]` |
| `GET` | `/api/accounts/{account}/details` | none | flat bonus/deduction rows |
| `GET` | `/api/accounts/{account}` | none | `AccountResource` |
| `PUT` | `/api/accounts/{account}` | JSON | `AccountResource` |
| `PUT` | `/api/accounts/{account}/received` | JSON | `AccountResource` |
| `POST` | `/api/accounts/{account}/bonuses` | JSON | `BonusResource` |
| `PUT` | `/api/bonuses/{bonus}` | JSON | `BonusResource` |
| `DELETE` | `/api/bonuses/{bonus}` | none | empty `data` |
| `POST` | `/api/accounts/{account}/deductions` | JSON | `DeductionResource` |
| `PUT` | `/api/deductions/{deduction}` | JSON | `DeductionResource` |
| `DELETE` | `/api/deductions/{deduction}` | none | empty `data` |

### Weekly Account Query

- `week` integer, required by the frontend.
- `year` integer or string, required by the frontend.
- `gallery_id` integer, required by the frontend.

Example:

`GET /api/accounts/weekly?week=22&year=2026&gallery_id=1`

### `AccountResource`

- `id` integer.
- `user_id` integer.
- `user_name` string.
- `user_position` string.
- `user_gallery` string.
- `sales_count` integer.
- `sales_amount` integer.
- `deduction_amount` integer.
- `working_days_count` integer.
- `salary` integer.
- `week_id` integer.
- `year` string.
- `total_amount` integer.
- `received` string, `0` or `1`.

### Weekly Account Response Example

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": [
    {
      "id": 301,
      "user_id": 4,
      "user_name": "sales1",
      "user_position": "موظف مبيعات",
      "user_gallery": "Aleppo",
      "sales_count": 2,
      "sales_amount": 35000,
      "deduction_amount": 150,
      "working_days_count": 6,
      "salary": 1300,
      "week_id": 12,
      "year": "2026",
      "total_amount": 36150,
      "received": "0"
    }
  ]
}
```

### Account Details Response Example

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": [
    {
      "id": 55,
      "amount": 150,
      "description": "Fuel adjustment",
      "accountant_id": 301,
      "type": "deduction"
    },
    {
      "id": 44,
      "amount": 300,
      "description": "Monthly bonus",
      "accountant_id": 301,
      "type": "bonus"
    }
  ]
}
```

### Account Update Request

```json
{
  "deduction_amount": 150
}
```

### Account Received Request

```json
{
  "received": "1",
  "user_id": 4,
  "week_id": 12
}
```

### Bonus And Deduction Request Body

```json
{
  "amount": 300,
  "description": "Monthly bonus",
  "accountant_id": 301
}
```

The same shape is used for create bonus and create deduction. Update routes only need `amount` and `description`.

### Bonus Response Example

```json
{
  "status": "success",
  "message": "تم إضافة المكافأة بنجاح",
  "data": {
    "id": 44,
    "amount": 300,
    "description": "Monthly bonus",
    "accountant_id": 301
  }
}
```

### Deduction Response Example

```json
{
  "status": "success",
  "message": "تم إضافة الحسم بنجاح",
  "data": {
    "id": 55,
    "amount": 150,
    "description": "Fuel adjustment",
    "accountant_id": 301
  }
}
```

### Delete Responses

- `DELETE /api/bonuses/{bonus}` and `DELETE /api/deductions/{deduction}` return a success message with `data: null`.
- `DELETE /api/users/{user}` also returns a success message with `data: null`.

## Frontend Notes

- Use the route ID in the URL for updates and deletes.
- Keep the old body field names when the backend says a legacy key is still tolerated.
- Public read screens can usually be opened without auth, but the app should still work if a token is already present.

# Backend vs Frontend API Compatibility Report
Date: 2026-06-01
Projects compared:
- Backend: `C:\laragon\www\beroya` (Laravel API routes)
- Frontend: `C:\laragon\www\beroya\Beroya_Project\Beroya Cars`

## Executive Summary
- Yes, there are **major backend contract changes for the frontend** at the endpoint transport layer.
- The frontend currently calls a **legacy action gateway**: `.../api.php?action=...`.
- Laravel backend exposes **REST routes** under `/api/...` with route parameters + HTTP verbs.
- Response envelope is still largely aligned (`status`, `message`, `data`), and most entity field names used by frontend are still present.
- Biggest breakage risk is **not response JSON keys**, but **URL format, HTTP method, route params, and auth mechanism**.

## What Matches (Response Shape)
The frontend expects `status` and `data` in many slices, and Laravel returns this envelope via `ApiResponse`.
- Backend envelope source: `app/Support/ApiResponse.php`
- Frontend expectations: multiple slices under `Beroya Cars/src/app/features/**` (e.g. `carsSlice`, `usersSlice`, `ordersSlice`).

Examples of preserved payload compatibility:
- Login still returns user fields + `token_expiry`.
- Cars resource still exposes keys used by UI (`market_id`, `model_id`, `plateNumber`, `image_1..image_6`, `car_sale_state`).
- Sales resource keys used in `salesSlice` transform are present (`user_comiss`, `owner_name`, `buyer_phone`, `approved`, etc.).

## Breaking Differences

### 1) Endpoint style changed (legacy action API vs Laravel REST)
Frontend URL builder:
- `Beroya Cars/src/core/constants/apiLinks.js`
- Uses: `const serverLink = \`${host}/api.php?action=\``

Backend routes:
- `routes/api.php`
- Uses REST endpoints like:
  - `GET /api/markets`
  - `POST /api/markets`
  - `PUT /api/markets/{market}`
  - `DELETE /api/markets/{market}`

Impact:
- If frontend points to Laravel host directly, calls to `/api.php?action=...` will fail (no `public/api.php` in Laravel backend).

### 2) HTTP methods and route parameters changed
Frontend commonly sends `POST` with `id` in body for operations that are now `PUT/DELETE` with path params.

Examples:
- `editUser` frontend sends body `{ id, ... }`, while backend requires `PUT /api/users/{user}`.
- `deleteUser` frontend sends POST body `{ id }`, backend requires `DELETE /api/users/{user}`.
- `editCarSale` frontend sends POST with `{ id, sale_state }`, backend requires `PUT /api/cars/{car}/sale-state`.
- `completeSale` frontend sends POST with body `id`, backend requires `PUT /api/sales/{sale}/complete`.
- `approveSaleOrder` frontend sends POST with body `id`, backend requires `PUT /api/sales/{sale}/approve-order`.
- `deleteSaleOrder` frontend sends POST with body `id`, backend requires `DELETE /api/sale-orders/{sale}`.
- Accounts details/update/bonus/deduction routes now depend on path params (`{account}`, `{bonus}`, `{deduction}`).

### 3) Auth contract mismatch (critical)
Backend protected routes use `auth:sanctum` and token auth:
- `routes/api.php` (middleware group)
- Token created in `app/Services/AuthService.php`
- Bearer token resolution in `app/Support/SanctumUserResolver.php`

Frontend behavior:
- `authStorage.js` intentionally does not store/use token (`getAuthToken() => null`).
- Axios config does not set `Authorization: Bearer ...`.

Impact:
- Authenticated Laravel routes will fail unless frontend starts sending bearer token or backend adds cookie/session-based auth flow compatible with frontend.

### 4) Frontend includes its own legacy API gateway
Frontend project contains:
- `Beroya Cars/public/api.php`
- This file maps `action` names to old PHP files under `../beroya_gallery_backend/...`.

Impact:
- Current frontend can still work against the old backend layout, but this is separate from Laravel `/api` routes.
- Laravel changes will not be fully consumed until frontend is migrated off this gateway.

## Endpoint Migration Map (High-Level)
Legacy action -> Laravel route target

- `signIn` -> `POST /api/auth/login`
- `logout` -> `POST /api/auth/logout`
- `getMarkets` -> `GET /api/markets`
- `addMarket` -> `POST /api/markets`
- `editMarkets` -> `PUT /api/markets/{market}`
- `deleteMarkets` -> `DELETE /api/markets/{market}`
- `getModels` -> `GET /api/car-models`
- `addModel` -> `POST /api/car-models`
- `editModel` -> `PUT /api/car-models/{carModel}`
- `deleteModel` -> `DELETE /api/car-models/{carModel}`
- `getCars` -> `GET /api/cars`
- `addCar` -> `POST /api/cars`
- `editCar` -> `PUT /api/cars/{car}`
- `deleteCar` -> `DELETE /api/cars/{car}`
- `editCarSale` -> `PUT /api/cars/{car}/sale-state`
- `getOrders` -> `GET /api/orders`
- `addOrder` -> `POST /api/orders`
- `getSales` -> `GET /api/sales` (status/gallery inputs must be sent as query params)
- `addSale` -> `POST /api/sales`
- `updateSale` -> `PUT /api/sales/{sale}`
- `completeSale` -> `PUT /api/sales/{sale}/complete`
- `approveSaleOrder` -> `PUT /api/sales/{sale}/approve-order`
- `deleteSaleOrder` -> `DELETE /api/sale-orders/{sale}`
- `getWeeklyAccounts` -> `GET /api/accounts/weekly` (query params)
- `getAccountDetails` -> `GET /api/accounts/{account}/details`
- `updateAccountReceived` -> `PUT /api/accounts/{account}/received`
- `addBonus` -> `POST /api/accounts/{account}/bonuses`
- `editBonus` -> `PUT /api/bonuses/{bonus}`
- `deleteBonus` -> `DELETE /api/bonuses/{bonus}`
- `addDeduction` -> `POST /api/accounts/{account}/deductions`
- `editDeduction` -> `PUT /api/deductions/{deduction}`
- `deleteDeduction` -> `DELETE /api/deductions/{deduction}`

## Conclusion
- **Backend response body format is mostly compatible** with frontend expectations.
- **Backend API transport contract is not compatible** with current frontend implementation.
- To use Laravel backend directly, frontend must migrate from `api.php?action=` to `/api/...` REST calls, pass route IDs in URL, and implement Sanctum token usage (or backend must provide a legacy compatibility adapter).

# Frontend Developer Handoff: Laravel API Migration
Date: 2026-06-01  
Prepared for: Beroya frontend team  
Backend project: `C:\laragon\www\beroya`  
Frontend project: `C:\laragon\www\beroya\Beroya_Project\Beroya Cars`

## 1) Objective
Migrate frontend API integration from legacy gateway style (`api.php?action=...`) to Laravel REST API style (`/api/...`) without changing UI/UX behavior.

This document is a complete handoff for implementation.

## 2) Why Migration Is Required
The backend contract changed from:
- action-based endpoint routing
- mostly POST-driven calls

to:
- resource-based routes
- method-specific behavior (`GET`, `POST`, `PUT`, `DELETE`)
- route-parameter-based entity resolution
- Sanctum-protected authenticated endpoints

If frontend remains on legacy contract, many calls will fail or bypass new backend behavior.

## 3) Current Frontend Constraint
Frontend currently builds URLs using:
- `.../api.php?action=...`

This is incompatible with Laravel backend routes in `routes/api.php`.

## 4) What Stays the Same
Most response envelope remains:
- `status`
- `message`
- `data`

Most resource field names consumed by UI are preserved.  
Main work is transport/auth/routing alignment, not state-model redesign.

## 5) High-Risk Contract Differences
1. URL pattern changed (action string -> REST path).
2. HTTP methods changed (POST-only style is no longer valid for many actions).
3. IDs must be in route path for update/delete/special actions.
4. Protected routes require consistent Sanctum auth context.

## 6) Endpoint Mapping (Legacy Action -> Laravel Route)

## Auth
- `signIn` -> `POST /api/auth/login`
- `logout` -> `POST /api/auth/logout`

## Users
- `getUsers` -> `GET /api/users`
- `addUser` -> `POST /api/users`
- `editUser` -> `PUT /api/users/{user}`
- `deleteUser` -> `DELETE /api/users/{user}`
- `changePassword` -> `PUT /api/users/{user}/password`

## Galleries
- `getGalleries` -> `GET /api/galleries`
- `addGallery` -> `POST /api/galleries`
- `editGallery` -> `PUT /api/galleries/{gallery}`
- `deleteGalleries` -> `DELETE /api/galleries/{gallery}`

## Gallery Phones
- `getGalleriesPhones` -> `GET /api/gallery-phones`
- `addGalleryPhones` -> `POST /api/gallery-phones`
- `editGalleryPhone` -> `PUT /api/gallery-phones/{galleryPhone}`
- `deleteGalleryPhone` -> `DELETE /api/gallery-phones/{galleryPhone}`

## Markets
- `getMarkets` -> `GET /api/markets`
- `addMarket` -> `POST /api/markets`
- `editMarkets` -> `PUT /api/markets/{market}`
- `deleteMarkets` -> `DELETE /api/markets/{market}`

## Models
- `getModels` -> `GET /api/car-models`
- `addModel` -> `POST /api/car-models`
- `editModel` -> `PUT /api/car-models/{carModel}`
- `deleteModel` -> `DELETE /api/car-models/{carModel}`

## Cars
- `getCars` -> `GET /api/cars`
- `addCar` -> `POST /api/cars`
- `editCar` -> `PUT /api/cars/{car}`
- `deleteCar` -> `DELETE /api/cars/{car}`
- `editCarSale` -> `PUT /api/cars/{car}/sale-state`

## Orders
- `getOrders` -> `GET /api/orders`
- `addOrder` -> `POST /api/orders`

## Sales
- `getSales` -> `GET /api/sales` (filters via query parameters)
- `addSale` -> `POST /api/sales`
- `updateSale` -> `PUT /api/sales/{sale}`
- `completeSale` -> `PUT /api/sales/{sale}/complete`
- `approveSaleOrder` -> `PUT /api/sales/{sale}/approve-order`
- `deleteSaleOrder` -> `DELETE /api/sale-orders/{sale}`
- `getWeeks` -> `GET /api/weeks`

## Accounts
- `getWeeklyAccounts` -> `GET /api/accounts/weekly` (query parameters)
- `getAccountDetails` -> `GET /api/accounts/{account}/details`
- `updateAccountReceived` -> `PUT /api/accounts/{account}/received`
- `updateAccount` -> `PUT /api/accounts/{account}`
- `addBonus` -> `POST /api/accounts/{account}/bonuses`
- `editBonus` -> `PUT /api/bonuses/{bonus}`
- `deleteBonus` -> `DELETE /api/bonuses/{bonus}`
- `addDeduction` -> `POST /api/accounts/{account}/deductions`
- `editDeduction` -> `PUT /api/deductions/{deduction}`
- `deleteDeduction` -> `DELETE /api/deductions/{deduction}`

## 7) Auth Contract for Frontend
Protected backend routes are under Sanctum middleware.  
Frontend must reliably send authenticated context after login.

Implementation expectation:
1. Login still returns auth metadata.
2. Frontend stores auth context consistently after login.
3. All protected requests include valid auth credentials.
4. Logout clears auth state and invalidates access behavior.

If this is not done first, downstream feature testing will produce false failures.

## 8) Migration Strategy (Recommended Order)

### Phase 1: API core + Auth
- Replace action URL strategy with REST route strategy.
- Stabilize authenticated request behavior.
- Verify login/logout + protected access.

### Phase 2: Read paths first
- Convert list/read endpoints for all modules.
- Keep reducers/state shape unchanged.
- Confirm pages render correctly with new transport.

### Phase 3: Write paths
- Convert create/update/delete actions per module.
- Convert sales special actions and accounts adjustments last.

### Phase 4: Cleanup
- Remove legacy action assumptions.
- Remove dead API constants and obsolete paths.

Reason for this order:
- Minimizes simultaneous risk.
- Isolates auth and transport failures early.
- Prevents noisy debugging in business logic/UI layers.

## 9) Critical ID Rules
1. For resource routes, use resource primary ID in path.
2. Do not rely on fallback IDs unless they are guaranteed to match backend resource IDs.
3. Accounts module must distinguish:
   - account resource ID
   - bonus/deduction item IDs

Wrong ID source can silently update/delete wrong records.

## 10) Module-Specific Notes

## Users
- Update/delete/password operations must target user route path with user ID.

## Cars
- Car sale-state uses dedicated endpoint.
- Car read-by-id should use resource path, not custom query pattern.

## Sales
- Sales list uses read-style route with filters.
- Complete/approve/reject are route-parameterized actions.

## Accounts
- Most sensitive module.
- Use account ID for account-scoped actions.
- Use adjustment item IDs for edit/delete bonus/deduction actions.

## 11) Validation & QA Checklist
Run this after each phase, not only at the end.

1. Auth
- Login succeeds.
- Protected pages load without unauthorized errors.
- Logout fully clears session behavior.

2. Users
- List, add, edit, delete, password-change all work.

3. Cars/Markets/Models/Galleries/Phones
- List/add/edit/delete flows work.
- Car sale-state update reflects in UI.

4. Orders
- List and add order work.

5. Sales
- Hold/done load works.
- Approve/reject/complete/update flows work.
- Post-action reload is consistent.

6. Accounts
- Weekly accounts load.
- Details load.
- Received status update persists.
- Bonus/deduction add/edit/delete persist and refresh.

7. Regression
- UI behavior unchanged from user perspective.
- No remaining calls to `api.php?action=...`.

## 12) Definition of Done
Migration is complete when all are true:
1. Frontend no longer depends on action-based API URLs.
2. Every endpoint uses correct method + path + parameter strategy.
3. Authenticated routes are stable after login.
4. All module workflows pass QA checklist.
5. Legacy gateway assumptions are fully removed.

## 13) Risks & Controls
Risk: mixed old/new API calls during migration.  
Control: complete module-by-module conversion and mark module complete before moving on.

Risk: hidden ID mismatches.  
Control: document per action which ID is path ID before implementation.

Risk: auth instability masked as feature failure.  
Control: lock auth behavior in Phase 1 before module migration.

## 14) Reference Documents
- `C:\laragon\www\beroya\BACKEND_FRONTEND_API_COMPATIBILITY_REPORT_2026-06-01.md`
- `C:\laragon\www\beroya\FRONTEND_LARAVEL_API_MIGRATION_GUIDE.md`


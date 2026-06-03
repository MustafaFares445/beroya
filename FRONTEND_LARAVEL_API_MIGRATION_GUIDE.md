# Frontend Migration Guide to Laravel API (No Code)
Date: 2026-06-01  
Backend: `C:\laragon\www\beroya`  
Frontend: `C:\laragon\www\beroya\Beroya_Project\Beroya Cars`

## Purpose
This guide explains how to migrate the frontend integration from the legacy action gateway style to the Laravel API style, and why each change is required.  
It is intentionally implementation-focused but code-free.

## What Changed in Backend Contract
The backend moved from a legacy pattern:
- `api.php?action=...`

to a Laravel REST API pattern:
- `/api/...` routes
- route parameters in URL
- HTTP verbs (`GET`, `POST`, `PUT`, `DELETE`)
- Sanctum-protected authenticated routes

Response envelope is still mostly compatible:
- `status`
- `message`
- `data`

So the biggest migration work is transport/auth routing, not UI state shape.

## Why Frontend Must Change
If the frontend keeps using action-based URLs and `POST` for everything:
1. It will not hit Laravel routes correctly.
2. It will fail route model binding (because IDs belong in path).
3. Protected endpoints will fail authentication if token is not sent as expected.
4. Some actions may appear to work on legacy gateway but never reach new Laravel logic.

## Migration Principles
1. Keep frontend business logic and reducers stable first.
2. Change API transport contract first (URL, method, params, auth).
3. Validate per module after each migration block.
4. Avoid full refactor during transport migration.

## Core Conceptual Changes

### 1) Endpoint style
Current frontend contract is action-driven.  
Target contract is route-driven.

Meaning:
- Replace “action name” concept with “resource route” concept.
- Identify resource ID and pass it in URL path when updating/deleting/special actions.

### 2) HTTP method semantics
Legacy style overused `POST`.  
Laravel routes now differentiate operations by method:
- `GET` for read
- `POST` for create
- `PUT` for update/state-change
- `DELETE` for remove

Why this matters:
- Backend authorization and route matching depend on method + path.
- Sending wrong method can hit no route or wrong validation flow.

### 3) Route parameter semantics
Many frontend requests currently send `id` inside body only.  
Laravel expects that ID in route path for many endpoints.

Why this matters:
- Route model binding resolves entities from URL segment.
- Body-only ID often gets ignored for route lookup.

### 4) Auth semantics (critical)
Laravel authenticated API routes are protected by Sanctum middleware.

Why this matters:
- If frontend does not send token in the expected way for protected routes, requests return unauthorized.
- Login success alone is not enough; subsequent requests must carry valid auth context.

## Module-by-Module Conversion Guide

## Auth
Goal:
- Keep login/logout behavior.
- Ensure post-login authenticated requests are accepted.

Understand:
- Login response still includes useful auth metadata.
- Protected routes require consistent authenticated request context.

Validation after migration:
- User logs in successfully.
- User can call protected endpoints without random 401.
- Logout revokes/ends session as expected.

## Users
Goal:
- Read, create, update, delete users using resource-style routes.
- Password update should target specific user resource path.

Why:
- User update/delete/password routes are parameterized by user ID in path.

Validation:
- List users works.
- Edit user works.
- Delete user works.
- Change password works for allowed role/user context.

## Cars / Markets / Models / Galleries / Phones
Goal:
- Keep all CRUD flows identical in UI behavior.
- Align each action with resource route + method.

Why:
- Backend now enforces route-specific validation and authorization by operation.
- For car state changes (sale state), backend has explicit specialized endpoint.

Validation:
- List data works for each module.
- Add/edit/delete works.
- Car sale state changes reflect correctly in lists/views.

## Orders
Goal:
- Keep reading/adding orders, but align read transport to route method.

Why:
- Orders list endpoint is read-oriented and should be called accordingly.

Validation:
- Orders load.
- New order insertion still updates UI state as expected.

## Sales
Goal:
- Keep hold/done flows and actions (approve, reject, complete, update).
- Convert list filtering to the backend’s expected request style for reading.
- Use proper route-based calls for action endpoints tied to sale ID.

Why:
- Sales routes are split into list/create/update/special action endpoints.
- Special actions (approve, complete, delete-order) rely on sale route parameter.

Validation:
- Hold and done lists load.
- Approve/reject/complete actions work and refresh state correctly.
- Edit/update sale persists correctly.

## Accounts (Most Sensitive)
Goal:
- Keep weekly accounts dashboard functionality.
- Ensure details/received/bonus/deduction operations target correct account-related resources.

Why:
- Account routes now separate account resource ID and adjustment item IDs.
- Wrong ID source (fallback mismatches) can call valid route on wrong record.

Validation:
- Weekly accounts load.
- Details per account load.
- Received toggle persists.
- Bonus/deduction add/edit/delete work with proper refresh.

## Recommended Migration Sequence
1. Core transport contract and auth behavior.
2. Read-only endpoints across modules.
3. Write endpoints for users/cars/sales/accounts.
4. Sales special actions.
5. Accounts adjustments and detail refresh paths.
6. Final cleanup of legacy action assumptions.

Reason for this order:
- It minimizes breakage surface.
- It isolates auth/transport errors before domain-level debugging.
- It preserves UI state logic while backend wiring changes.

## Risk Areas and How to Think About Them
1. Mixed old/new endpoint usage:
   - Risk: inconsistent behavior by module.
   - Control: migrate by vertical slice and mark complete per module.

2. Hidden ID mapping assumptions:
   - Risk: wrong record gets updated/deleted.
   - Control: explicitly define which ID each operation uses (resource ID vs related item ID).

3. Auth drift:
   - Risk: intermittent unauthorized errors after login.
   - Control: verify consistent auth context handling across all protected calls.

4. Request shape drift:
   - Risk: validation failures due to route/body mismatch.
   - Control: compare operation-by-operation against Laravel request expectations.

## Acceptance Criteria
Migration is complete when:
1. No frontend calls depend on `api.php?action=...`.
2. CRUD + special actions map to route + method + route param correctly.
3. Protected routes work consistently after login.
4. Existing UI workflows and state updates remain functionally unchanged.
5. No module depends on legacy gateway behavior.

## Reference
Use this compatibility report alongside this guide:
- `C:\laragon\www\beroya\BACKEND_FRONTEND_API_COMPATIBILITY_REPORT_2026-06-01.md`


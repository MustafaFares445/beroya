# Phase 2 Split: Real Estate Module

## Summary
- Build the real-estate module as a staged Laravel API slice that follows the same route/controller/resource/service pattern already used by `galleries`, `markets`, `cars`, `orders`, `sales`, and `accounts`.
- Keep compatibility first: preserve the existing envelope, do not rename current global contract props, and add real-estate fields additively.
- Split the work into five implementation phases so each phase ends with a working, testable API slice and no hidden contract decisions.

## Phase 2.1 - Foundation And Lookups
- Endpoints: `GET/POST/PUT/DELETE /api/provinces`, `GET/POST/PUT/DELETE /api/real-estate/offices`, `GET/POST/PUT/DELETE /api/real-estate/office-phones`, `GET /api/real-estate/property-categories`, `GET /api/real-estate/property-subcategories`.
- Data props added: `ProvinceResource` with `id`, `name`, `is_active`; `RealEstateOfficeResource` with `id`, `province_id`, `province_name`, `name`, `address`, `is_active`; `OfficePhoneResource` with `id`, `real_estate_office_id`, `phone`; category and subcategory lookup resources with the same flat style as the existing resources.
- Exit criteria: province and office master data can be created, edited, listed, and deleted; lookup tables are seed-backed; all routes use the same `FormRequest -> Service -> Resource -> ApiResponse` flow already used in the current API.

## Phase 2.2 - User Assignment And Office Scope
- Endpoints: extend the existing `GET/POST/PUT /api/users`, `GET /api/users/{user}`, and `POST /api/auth/login` contract rather than adding a separate staff API.
- Data props added: `UserResource` and login payload gain `real_estate_office_id`, `real_estate_office_name`, `real_estate_province_id`, `real_estate_province_name`, and `real_estate_role`; keep `gallery_id` and `permetions_level` untouched.
- Exit criteria: office staff can be assigned and scoped by office/province; `real_estate_role` is additive and derived from the real-estate assignment layer, not a replacement for the existing numeric permission system.

## Phase 2.3 - Property Inventory
- Endpoints: `GET/POST/PUT/DELETE /api/real-estate/properties`, `POST /api/real-estate/properties/{property}/images`, `DELETE /api/real-estate/property-images/{image}`.
- Data props added: `PropertyResource` with `id`, `offer_number`, `province_id`, `province_name`, `office_id`, `office_name`, `main_category_id`, `main_category_name`, `subcategory_id`, `subcategory_name`, `property_nature`, `area`, `district`, `address`, `building`, `floor`, `direction`, `rooms_count`, `area_size`, `price`, `ownership_type`, `offer_type`, `rent_duration`, `owner_name`, `owner_phone`, `status`, `images`, `created_at`, and `updated_at`.
- Exit criteria: property inventory can be created, updated, filtered, read, and deleted; image uploads are unlimited through a related `property_images` table; guest reads mask owner contact fields while authorized real-estate users see the full payload.

## Phase 2.4 - Submissions And Approval
- Endpoints: `GET/POST /api/real-estate/property-submissions`, `GET /api/real-estate/property-submissions/{submission}`, `PUT /api/real-estate/property-submissions/{submission}/approve`, `PUT /api/real-estate/property-submissions/{submission}/reject`.
- Data props added: `PropertySubmissionResource` reuses the same property fields plus `status`, `reject_reason`, `published_property_id`, and `reviewed_at`; `office_id` is nullable on create and `province_id` is resolved from office when available.
- Exit criteria: customer submissions can be stored as `pending`, approved into live properties, or rejected with a persisted reason while keeping review history intact.

## Phase 2.5 - Hardening And Regression Tests
- Endpoints covered: all phase 2 routes above, with route verification against the current API style and the same HTTP verb restrictions used elsewhere.
- Data props verified: response snapshots for every new resource, login payload expansion, guest masking, office/province scoping, submission review fields, and image URLs/filesystem cleanup.
- Exit criteria: PHPUnit feature tests cover success, failure, validation, authorization, upload, masking, and approval paths; the contract stays additive and the existing envelope stays unchanged.

## Implementation Notes
- Every write path should mirror the current repository pattern: explicit route, Form Request validation, service transaction, and flat API Resource output.
- Property creation should derive `province_id` from the selected office instead of accepting it as an independent source of truth.
- Store property uploads under `public/data/uploads/properties` and delete files when the related image or parent record is removed.
- If the frontend needs Beroya Group grouping metadata, add it additively to the same bootstrap/dashboard payload the app already uses, not through a separate contract.

## Assumptions
- `property_submissions` stays separate from `properties` so approval can preserve audit history.
- Category and subcategory endpoints are read-only in this phase unless the legacy PHP screens prove they need CRUD.
- Lists stay unpaginated in phase 2 unless the legacy contract already depends on pagination.
- `real_estate_office_id` and `real_estate_role` are additive user fields; `gallery_id` and `permetions_level` remain untouched.

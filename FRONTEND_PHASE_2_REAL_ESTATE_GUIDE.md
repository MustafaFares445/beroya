# Frontend Phase 2 API Guide: Real Estate Module

Base URL: `https://beroya.mustafafares.com/api`

This phase introduces the real-estate module: lookup data, offices, office phones, property categories, properties, images, and property-submission review screens.

## User Flow

1. Load the lookup endpoints first so the property forms have valid dropdown values.
2. Assign users to a real-estate office and role when the admin creates or edits staff.
3. Create and edit properties, then attach images from the property detail screen.
4. Submit incoming offers through the public submission form.
5. Review submissions, approve them into live properties, or reject them with a reason.

## Shared Contract

- Public `GET` endpoints are readable without auth, but the frontend can still send the auth token if it already has one.
- Write routes require authentication and are permission-gated by the backend.
- Use `multipart/form-data` for property image uploads.
- The canonical select-box source is `GET /api/real-estate/options`.

## Data Types And Enum Sets

### `property_natures`

- `سكني`
  - aliases: `residential`, `apartment`, `house`
- `تجاري`
  - aliases: `commercial`, `office`, `shop`, `hotel`, `restaurant`
- `صناعي`
  - aliases: `industrial`, `factory`
- `أراضي`
  - aliases: `land`, `investment-land`, `agricultural-land`
- `فيلات`
  - aliases: `villa`, `chalet`, `tourism-farm`

### `title_types`

- `ملك`
- `فروغ`
- `أميري`
- `حجري`

### `offer_types`

- `sale`
- `rent`
- `investment`

### `rent_durations`

- `daily`
- `weekly`
- `monthly`
- `yearly`

### `property_statuses`

- `sold`
- `available`
- `unavailable`

### `real_estate_roles`

- `province_manager` => `مدير محافظة`
- `office_manager` => `مدير مكتب`
- `office_employee` => `موظف مكتب`
- `reviewer` => `مدير مكتب`
- `agent` => `موظف مكتب`
- `senior-agent` => `موظف مكتب`

Roles with review permission in the backend are `province_manager`, `office_manager`, and `reviewer`.

## 1) Lookup Data

### Provinces

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `GET` | `/api/provinces` | none | `ProvinceResource[]` |
| `GET` | `/api/provinces/{province}` | none | `ProvinceResource` |
| `POST` | `/api/provinces` | JSON | `ProvinceResource` |
| `PUT` | `/api/provinces/{province}` | JSON | `ProvinceResource` |
| `DELETE` | `/api/provinces/{province}` | none | `{"id": <province_id>}` |

Request body:

```json
{
  "name": "Damascus",
  "is_active": true
}
```

Response example:

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": 1,
    "name": "Damascus",
    "is_active": true
  }
}
```

### Real-Estate Options

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `GET` | `/api/real-estate/options` | none | option groups object |

Use this endpoint to populate every property form select box. The returned keys are:

- `property_natures`
- `title_types`
- `offer_types`
- `rent_durations`
- `statuses`
- `real_estate_roles`

Response example:

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "property_natures": [
      { "value": "سكني", "label": "سكني", "aliases": ["residential", "apartment", "house"] }
    ],
    "title_types": [
      { "value": "ملك", "label": "ملك" }
    ],
    "offer_types": [
      { "value": "sale", "label": "بيع" }
    ],
    "rent_durations": [
      { "value": "daily", "label": "يومي" }
    ],
    "statuses": [
      { "value": "available", "label": "متاح" }
    ],
    "real_estate_roles": [
      { "value": "office_employee", "label": "موظف مكتب" }
    ]
  }
}
```

### Property Taxonomy

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `GET` | `/api/real-estate/property-categories` | none | `PropertyCategoryResource[]` |
| `GET` | `/api/real-estate/property-subcategories` | none | `PropertySubcategoryResource[]` |

Response fields:

- Property categories: `id`, `name`
- Property subcategories: `id`, `property_category_id`, `name`

## 2) Offices And Phones

### Offices

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `GET` | `/api/real-estate/offices` | none | `RealEstateOfficeResource[]` |
| `GET` | `/api/real-estate/offices/{realEstateOffice}` | none | `RealEstateOfficeResource` |
| `POST` | `/api/real-estate/offices` | JSON | `RealEstateOfficeResource` |
| `PUT` | `/api/real-estate/offices/{realEstateOffice}` | JSON | `RealEstateOfficeResource` |
| `DELETE` | `/api/real-estate/offices/{realEstateOffice}` | none | `{"id": <office_id>}` |

Request body:

```json
{
  "province_id": 1,
  "name": "Damascus Center",
  "address": "Main Street",
  "is_active": true
}
```

Response example:

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": 2,
    "province_id": 1,
    "province_name": "Damascus",
    "name": "Damascus Center",
    "address": "Main Street",
    "is_active": true
  }
}
```

### Office Phones

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `GET` | `/api/real-estate/office-phones` | none | `OfficePhoneResource[]` |
| `GET` | `/api/real-estate/office-phones/{realEstateOfficePhone}` | none | `OfficePhoneResource` |
| `POST` | `/api/real-estate/office-phones` | JSON | `OfficePhoneResource` |
| `PUT` | `/api/real-estate/office-phones/{realEstateOfficePhone}` | JSON | `OfficePhoneResource` |
| `DELETE` | `/api/real-estate/office-phones/{realEstateOfficePhone}` | none | `{"id": <office_phone_id>}` |

Request body:

```json
{
  "real_estate_office_id": 2,
  "phone": "01122334455"
}
```

Response example:

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": 7,
    "real_estate_office_id": 2,
    "phone": "01122334455"
  }
}
```

## 3) User Real-Estate Assignment

The login payload and user CRUD responses include the real-estate assignment fields below:

- `real_estate_office_id`
- `real_estate_office_name`
- `real_estate_province_id`
- `real_estate_province_name`
- `real_estate_role`
- `real_estate_role_label`

### User Create Or Update

The general user fields from phase 1 still apply.

Add or update these fields when assigning a user to a real-estate office:

- `real_estate_office_id` integer, optional.
- `real_estate_role` string, optional.

## 4) Properties

### Route Map

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `GET` | `/api/real-estate/properties` | query | `PropertyResource[]` |
| `GET` | `/api/real-estate/properties/{property}` | none | `PropertyResource` |
| `POST` | `/api/real-estate/properties` | JSON | `PropertyResource` |
| `PUT` | `/api/real-estate/properties/{property}` | JSON | `PropertyResource` |
| `DELETE` | `/api/real-estate/properties/{property}` | none | `{"id": <property_id>}` |
| `POST` | `/api/real-estate/properties/{property}/images` | multipart | `PropertyResource` |
| `DELETE` | `/api/real-estate/property-images/{image}` | none | updated `PropertyResource` |

### List Filters

`GET /api/real-estate/properties` accepts:

- `province_id`
- `office_id`
- `main_category_id`
- `subcategory_id`
- `status`
- `title_type`
- `offer_type`
- `ownership_type`
- `property_nature`
- `rent_duration`
- `search`

### `PropertyResource`

- `id` integer.
- `offer_number` string.
- `province_id` integer or `null`.
- `province_name` string or `null`.
- `office_id` integer or `null`.
- `office_name` string or `null`.
- `main_category_id` integer.
- `main_category_name` string or `null`.
- `subcategory_id` integer.
- `subcategory_name` string or `null`.
- `property_nature` string.
- `title_type` string.
- `area` string.
- `district` string.
- `address` string.
- `building` string.
- `floor` string.
- `direction` string.
- `rooms_count` integer.
- `area_size` integer.
- `price` integer.
- `ownership_type` string.
- `offer_type` string.
- `rent_duration` string or `null`.
- `owner_name` string.
- `owner_phone` string.
- `status` string.
- `images` array of `PropertyImageResource`.
- `created_at` datetime string or `null`.
- `updated_at` datetime string or `null`.

### Property Request Body

```json
{
  "offer_number": "OFFER-2026-001",
  "office_id": 2,
  "main_category_id": 1,
  "subcategory_id": 3,
  "property_nature": "سكني",
  "title_type": "ملك",
  "area": "Damascus",
  "district": "Mazzeh",
  "address": "Main Street",
  "building": "B12",
  "floor": "3",
  "direction": "North",
  "rooms_count": 3,
  "area_size": 150,
  "price": 250000,
  "ownership_type": "private",
  "offer_type": "sale",
  "rent_duration": null,
  "owner_name": "Owner One",
  "owner_phone": "0999999999",
  "status": "available"
}
```

If `offer_type` is `rent`, `rent_duration` becomes required and must be one of `daily`, `weekly`, `monthly`, or `yearly`.

### Property Response Example

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": 11,
    "offer_number": "OFFER-2026-001",
    "province_id": 1,
    "province_name": "Damascus",
    "office_id": 2,
    "office_name": "Damascus Center",
    "main_category_id": 1,
    "main_category_name": "Apartments",
    "subcategory_id": 3,
    "subcategory_name": "Two Bedrooms",
    "property_nature": "سكني",
    "title_type": "ملك",
    "area": "Damascus",
    "district": "Mazzeh",
    "address": "Main Street",
    "building": "B12",
    "floor": "3",
    "direction": "North",
    "rooms_count": 3,
    "area_size": 150,
    "price": 250000,
    "ownership_type": "private",
    "offer_type": "sale",
    "rent_duration": null,
    "owner_name": "Owner One",
    "owner_phone": "0999999999",
    "status": "available",
    "images": [
      {
        "id": 41,
        "property_id": 11,
        "image": "prop-1.webp",
        "url": "/data/uploads/properties/prop-1.webp"
      }
    ],
    "created_at": "2026-06-14 09:30:00",
    "updated_at": "2026-06-14 09:30:00"
  }
}
```

### Property Image Upload

Send files as multipart form data under `images[]`. The backend also accepts a single `image` field and will merge it into the array.

Request body:

- `images[]` file array, required, minimum 1 file.

Response:

- the updated `PropertyResource`

### Guest Masking

- Public property reads are allowed.
- If the viewer is not a real-estate manager, the backend blanks `owner_name` and `owner_phone`.
- Authenticated real-estate users see the full property data.

## 5) Property Submissions

### Route Map

| Method | Path | Body | Result |
| --- | --- | --- | --- |
| `POST` | `/api/real-estate/property-submissions` | JSON | `PropertySubmissionResource` |
| `GET` | `/api/real-estate/property-submissions` | none | `PropertySubmissionResource[]` |
| `GET` | `/api/real-estate/property-submissions/{submission}` | none | `PropertySubmissionResource` |
| `PUT` | `/api/real-estate/property-submissions/{submission}/approve` | none | `PropertySubmissionResource` |
| `PUT` | `/api/real-estate/property-submissions/{submission}/reject` | JSON | `PropertySubmissionResource` |

### `PropertySubmissionResource`

- `id` integer.
- `offer_number` string.
- `province_id` integer or `null`.
- `province_name` string or `null`.
- `office_id` integer or `null`.
- `office_name` string or `null`.
- `main_category_id` integer.
- `main_category_name` string or `null`.
- `subcategory_id` integer.
- `subcategory_name` string or `null`.
- `property_nature` string.
- `title_type` string.
- `area` string.
- `district` string.
- `address` string.
- `building` string.
- `floor` string.
- `direction` string.
- `rooms_count` integer.
- `area_size` integer.
- `price` integer.
- `ownership_type` string.
- `offer_type` string.
- `rent_duration` string or `null`.
- `owner_name` string.
- `owner_phone` string.
- `submission_note` string or `null`.
- `status` string, usually `pending`, `approved`, or `rejected`.
- `reject_reason` string or `null`.
- `published_property_id` integer or `null`.
- `reviewed_at` datetime string or `null`.
- `created_at` datetime string or `null`.
- `updated_at` datetime string or `null`.

### Submission Request Body

```json
{
  "offer_number": "SUB-2026-001",
  "province_id": 1,
  "office_id": 2,
  "main_category_id": 1,
  "subcategory_id": 3,
  "property_nature": "سكني",
  "title_type": "ملك",
  "area": "Damascus",
  "district": "Mazzeh",
  "address": "Main Street",
  "building": "B12",
  "floor": "3",
  "direction": "North",
  "rooms_count": 3,
  "area_size": 150,
  "price": 250000,
  "ownership_type": "private",
  "offer_type": "sale",
  "rent_duration": null,
  "owner_name": "Owner One",
  "owner_phone": "0999999999",
  "submission_note": "Call before visiting"
}
```

If `office_id` is omitted, `province_id` becomes required.

### Submission Response Example

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": 21,
    "offer_number": "SUB-2026-001",
    "province_id": 1,
    "province_name": "Damascus",
    "office_id": 2,
    "office_name": "Damascus Center",
    "main_category_id": 1,
    "main_category_name": "Apartments",
    "subcategory_id": 3,
    "subcategory_name": "Two Bedrooms",
    "property_nature": "سكني",
    "title_type": "ملك",
    "area": "Damascus",
    "district": "Mazzeh",
    "address": "Main Street",
    "building": "B12",
    "floor": "3",
    "direction": "North",
    "rooms_count": 3,
    "area_size": 150,
    "price": 250000,
    "ownership_type": "private",
    "offer_type": "sale",
    "rent_duration": null,
    "owner_name": "Owner One",
    "owner_phone": "0999999999",
    "submission_note": "Call before visiting",
    "status": "pending",
    "reject_reason": null,
    "published_property_id": null,
    "reviewed_at": null,
    "created_at": "2026-06-14 09:30:00",
    "updated_at": "2026-06-14 09:30:00"
  }
}
```

### Review Actions

- `PUT /api/real-estate/property-submissions/{submission}/approve` publishes or links a property and marks the submission as `approved`.
- `PUT /api/real-estate/property-submissions/{submission}/reject` requires `reject_reason` and marks the submission as `rejected`.

Reject request body:

```json
{
  "reject_reason": "The offer is missing a valid owner phone number."
}
```

## Frontend Notes

- Call `/api/real-estate/options` before rendering the create and edit forms.
- Keep property forms multipart-ready because the image upload screen uses file fields.
- Do not assume every submission includes `office_id`. The backend can resolve the province from the office, or fall back to the supplied province.
- Approval screens should read `published_property_id` and `reviewed_at` from the submission resource after review.

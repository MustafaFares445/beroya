# Property Submissions Frontend API Contract

This contract documents the complete customer-to-staff property submission flow:

1. A customer submits a property offer without authentication.
2. The backend stores it with `pending` status.
3. An authorized real-estate manager lists and reviews submissions.
4. The manager approves the submission and publishes a property, or rejects it with a reason.

## Endpoint Map

| Actor | Purpose | Method | Path | Authentication |
| --- | --- | --- | --- | --- |
| Customer | Submit a property | `POST` | `/api/real-estate/property-submissions` | Public |
| Manager | List submissions | `GET` | `/api/real-estate/property-submissions` | Sanctum token |
| Manager | View submission | `GET` | `/api/real-estate/property-submissions/{submission}` | Sanctum token |
| Manager | Approve and publish | `PUT` | `/api/real-estate/property-submissions/{submission}/approve` | Sanctum token |
| Manager | Reject | `PUT` | `/api/real-estate/property-submissions/{submission}/reject` | Sanctum token |

All successful endpoints currently return HTTP `200`.

## Permissions

The customer submission endpoint is public. Do not send an authentication token unless the frontend already includes one globally.

The list, details, approve, and reject endpoints require:

```http
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

Only users whose backend permission level is `1`, `2`, or `3` can review submissions. These levels represent the general manager, province manager, and office manager in the current permission model.

A normal office employee at permission level `4` cannot list, view, approve, or reject submissions, even when assigned to an office.

## Supporting Lookup Endpoints

Load these public endpoints before displaying the customer form:

| Purpose | Method | Path |
| --- | --- | --- |
| Enum values and labels | `GET` | `/api/real-estate/options` |
| Provinces | `GET` | `/api/provinces` |
| Offices | `GET` | `/api/real-estate/offices` |
| Main categories | `GET` | `/api/real-estate/property-categories` |
| Subcategories | `GET` | `/api/real-estate/property-subcategories` |

The selected `subcategory_id` must belong to `main_category_id`.

The customer can choose either:

- An `office_id`. The backend derives the province from that office and ignores the submitted province for storage.
- No office. In this case, `province_id` is required.

## Enums

Use the exact `value` from `GET /api/real-estate/options`. Use `label` for display.

### Property nature

| Value | Label |
| --- | --- |
| `سكني` | سكني |
| `تجاري` | تجاري |
| `صناعي` | صناعي |
| `أراضي` | أراضي |
| `فيلات` | فيلات |

The backend also accepts these legacy aliases, but the frontend should submit the canonical Arabic value:

- `سكني`: `residential`, `apartment`, `house`
- `تجاري`: `commercial`, `office`, `shop`, `hotel`, `restaurant`
- `صناعي`: `industrial`, `factory`
- `أراضي`: `land`, `investment-land`, `agricultural-land`
- `فيلات`: `villa`, `chalet`, `tourism-farm`

### Title type

| Value | Label |
| --- | --- |
| `ملك` | ملك |
| `فروغ` | فروغ |
| `أميري` | أميري |
| `حجري` | حجري |

### Offer type

| Value | Label | Rent duration |
| --- | --- | --- |
| `sale` | بيع | Send `null` or omit it |
| `rent` | إيجار | Required |
| `investment` | استثمار | Send `null` or omit it |

### Rent duration

| Value | Label |
| --- | --- |
| `daily` | يومي |
| `weekly` | أسبوعي |
| `monthly` | شهري |
| `yearly` | سنوي |

### Submission status

| Value | Meaning | Frontend action |
| --- | --- | --- |
| `pending` | Waiting for review | Show approve and reject actions to authorized managers. |
| `approved` | Published as a property | Link to `published_property_id`. |
| `rejected` | Rejected by a manager | Display `reject_reason`. |

```ts
export type PropertyNature = 'سكني' | 'تجاري' | 'صناعي' | 'أراضي' | 'فيلات';
export type PropertyTitleType = 'ملك' | 'فروغ' | 'أميري' | 'حجري';
export type PropertyOfferType = 'sale' | 'rent' | 'investment';
export type PropertyRentDuration = 'daily' | 'weekly' | 'monthly' | 'yearly';
export type PropertySubmissionStatus = 'pending' | 'approved' | 'rejected';
```

## Shared Response Types

```ts
export interface ApiEnvelope<T> {
  status: 'success' | 'failure';
  message: string;
  data: T;
}

export interface ValidationFailureData {
  errors: Record<string, string[]>;
}

export interface PropertySubmission {
  id: number;
  offer_number: string;
  province_id: number;
  province_name: string | null;
  office_id: number | null;
  office_name: string | null;
  main_category_id: number;
  main_category_name: string | null;
  subcategory_id: number;
  subcategory_name: string | null;
  property_nature: PropertyNature;
  title_type: PropertyTitleType;
  area: string;
  district: string;
  address: string;
  building: string;
  floor: string;
  direction: string;
  rooms_count: number;
  area_size: number;
  price: number;
  ownership_type: string;
  offer_type: PropertyOfferType;
  rent_duration: PropertyRentDuration | null;
  owner_name: string;
  owner_phone: string;
  submission_note: string | null;
  status: PropertySubmissionStatus;
  reject_reason: string | null;
  published_property_id: number | null;
  reviewed_at: string | null;
  created_at: string | null;
  updated_at: string | null;
}
```

Date values use `YYYY-MM-DD HH:mm:ss` when present.

## 1. Customer Submits a Property

```http
POST /api/real-estate/property-submissions
Accept: application/json
Content-Type: application/json
```

### Request fields

| Field | Type | Required | Validation and behavior |
| --- | --- | --- | --- |
| `offer_number` | `string` | Yes | Maximum 100 characters and unique across existing submissions and published properties. |
| `province_id` | `integer` | Conditional | Required when `office_id` is missing; must reference an existing province. |
| `office_id` | `integer` or `null` | No | Must reference an existing office. Its province takes precedence when supplied. |
| `main_category_id` | `integer` | Yes | Must reference an existing category. |
| `subcategory_id` | `integer` | Yes | Must reference a subcategory belonging to `main_category_id`. |
| `property_nature` | enum string | Yes | Use a value from the property-nature options. |
| `title_type` | enum string | Yes | Required for all offer types. |
| `area` | `string` | Yes | Maximum 255 characters. |
| `district` | `string` | Yes | Maximum 255 characters. |
| `address` | `string` | Yes | Maximum 1000 characters. |
| `building` | `string` | Yes | Maximum 255 characters. |
| `floor` | `string` | Yes | Maximum 50 characters. |
| `direction` | `string` | Yes | Maximum 100 characters. |
| `rooms_count` | `integer` | Yes | Minimum `0`. |
| `area_size` | `integer` | Yes | Minimum `0`; no unit is defined by the API. |
| `price` | `integer` | Yes | Minimum `0`; no currency is defined by the API. |
| `ownership_type` | `string` | Yes | Maximum 100 characters; free text. |
| `offer_type` | enum string | Yes | `sale`, `rent`, or `investment`. |
| `rent_duration` | enum string or `null` | Conditional | Required when `offer_type` is `rent`. |
| `owner_name` | `string` | Yes | Maximum 255 characters. |
| `owner_phone` | `string` | Yes | Maximum 30 characters. |
| `submission_note` | `string` or `null` | No | Optional customer note; maximum 5000 characters. |

Images are not supported by the property-submission endpoint. Images can be added to the published property after approval through the property image endpoint documented in `PROPERTY_STORE_FRONTEND_API_CONTRACT.md`.

### TypeScript request type

```ts
export interface StorePropertySubmissionPayload {
  offer_number: string;
  province_id?: number;
  office_id?: number | null;
  main_category_id: number;
  subcategory_id: number;
  property_nature: PropertyNature;
  title_type: PropertyTitleType;
  area: string;
  district: string;
  address: string;
  building: string;
  floor: string;
  direction: string;
  rooms_count: number;
  area_size: number;
  price: number;
  ownership_type: string;
  offer_type: PropertyOfferType;
  rent_duration?: PropertyRentDuration | null;
  owner_name: string;
  owner_phone: string;
  submission_note?: string | null;
}
```

### Request example

```json
{
  "offer_number": "SUB-2026-0001",
  "province_id": 1,
  "office_id": null,
  "main_category_id": 1,
  "subcategory_id": 3,
  "property_nature": "سكني",
  "title_type": "ملك",
  "area": "دمشق",
  "district": "المزة",
  "address": "الشارع الرئيسي، بناء 10",
  "building": "A",
  "floor": "3",
  "direction": "شرقي",
  "rooms_count": 3,
  "area_size": 120,
  "price": 65000,
  "ownership_type": "طابو أخضر",
  "offer_type": "sale",
  "rent_duration": null,
  "owner_name": "أحمد محمد",
  "owner_phone": "0999000001",
  "submission_note": "يرجى الاتصال قبل زيارة العقار"
}
```

### Frontend request example

```ts
export async function submitProperty(
  apiBaseUrl: string,
  payload: StorePropertySubmissionPayload,
): Promise<PropertySubmission> {
  const response = await fetch(`${apiBaseUrl}/api/real-estate/property-submissions`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });

  const result = (await response.json()) as ApiEnvelope<
    PropertySubmission | ValidationFailureData | null
  >;

  if (!response.ok || result.status !== 'success') {
    throw result;
  }

  return result.data as PropertySubmission;
}
```

### Success response

The backend creates the submission with `pending` status:

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": 21,
    "offer_number": "SUB-2026-0001",
    "province_id": 1,
    "province_name": "Damascus",
    "office_id": null,
    "office_name": null,
    "main_category_id": 1,
    "main_category_name": "سكني",
    "subcategory_id": 3,
    "subcategory_name": "منزل",
    "property_nature": "سكني",
    "title_type": "ملك",
    "area": "دمشق",
    "district": "المزة",
    "address": "الشارع الرئيسي، بناء 10",
    "building": "A",
    "floor": "3",
    "direction": "شرقي",
    "rooms_count": 3,
    "area_size": 120,
    "price": 65000,
    "ownership_type": "طابو أخضر",
    "offer_type": "sale",
    "rent_duration": null,
    "owner_name": "أحمد محمد",
    "owner_phone": "0999000001",
    "submission_note": "يرجى الاتصال قبل زيارة العقار",
    "status": "pending",
    "reject_reason": null,
    "published_property_id": null,
    "reviewed_at": null,
    "created_at": "2026-07-17 12:30:00",
    "updated_at": "2026-07-17 12:30:00"
  }
}
```

The customer receives the submission object but there is currently no public endpoint for checking its status later. If the frontend needs customer-side tracking, that requires a separate backend feature.

## 2. Manager Lists Submissions

```http
GET /api/real-estate/property-submissions
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

The response is an unpaginated array ordered by newest submission ID first. The endpoint currently has no filter or search parameters.

```ts
export async function listPropertySubmissions(
  apiBaseUrl: string,
  token: string,
): Promise<PropertySubmission[]> {
  const response = await fetch(`${apiBaseUrl}/api/real-estate/property-submissions`, {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });

  const result = (await response.json()) as ApiEnvelope<
    PropertySubmission[] | string | null
  >;

  if (!response.ok || result.status !== 'success') {
    throw result;
  }

  return result.data as PropertySubmission[];
}
```

## 3. Manager Views One Submission

```http
GET /api/real-estate/property-submissions/{submissionId}
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

Returns one `PropertySubmission`. A missing submission ID returns `404`.

## 4. Manager Approves and Publishes

```http
PUT /api/real-estate/property-submissions/{submissionId}/approve
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

The endpoint has no request body.

On approval, the backend:

1. Chooses an office in this order:
   - The office selected by the customer.
   - The reviewing manager's assigned office.
   - The first active office in the submission's province.
2. Creates a published property when `published_property_id` is still `null`.
3. Copies the submitted property data into the new property.
4. Sets the published property's status to `available`.
5. Updates the submission to `approved`.
6. Sets `published_property_id` and `reviewed_at`.

Example response fragment:

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": 21,
    "status": "approved",
    "reject_reason": null,
    "published_property_id": 48,
    "reviewed_at": "2026-07-17 13:00:00"
  }
}
```

The real response contains the complete `PropertySubmission` object, not only the fields shown above. After approval, use `published_property_id` to open the published property or upload its images.

## 5. Manager Rejects

```http
PUT /api/real-estate/property-submissions/{submissionId}/reject
Authorization: Bearer YOUR_TOKEN
Accept: application/json
Content-Type: application/json
```

Request body:

```json
{
  "reject_reason": "بيانات الملكية غير مكتملة"
}
```

`reject_reason` is required, must be a string, and has a maximum length of 1000 characters.

On rejection, the backend sets:

- `status` to `rejected`.
- `reject_reason` to the submitted reason.
- `reviewed_at` to the current backend time.
- `published_property_id` remains unchanged, normally `null` for a pending submission.

Example response fragment:

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": 21,
    "status": "rejected",
    "reject_reason": "بيانات الملكية غير مكتملة",
    "published_property_id": null,
    "reviewed_at": "2026-07-17 13:00:00"
  }
}
```

The real response contains the complete `PropertySubmission` object.

## Errors

All errors use the same top-level envelope:

```ts
export interface ApiFailure<T = null> {
  status: 'failure';
  message: string;
  data: T;
}
```

### `401 Unauthorized`

Applies to manager endpoints when the token is missing or invalid:

```json
{
  "status": "failure",
  "message": "التوكن غير صالح",
  "data": null
}
```

### `403 Forbidden`

Applies when an authenticated user does not have review permission:

```json
{
  "status": "failure",
  "message": "لا تملك صلاحية تنفيذ هذا الإجراء",
  "data": "your computer harmly damaged"
}
```

Display the localized `message`; do not expose the internal `data` string to the user.

### `404 Not Found`

Applies when the requested submission does not exist, or when approval cannot resolve a required office:

```json
{
  "status": "failure",
  "message": "المورد المطلوب غير موجود",
  "data": null
}
```

### `422 Validation Error`

Applies to customer submission and rejection validation:

```json
{
  "status": "failure",
  "message": "فشل التحقق من صحة البيانات",
  "data": {
    "errors": {
      "offer_number": [
        "The offer number has already been taken."
      ],
      "rent_duration": [
        "The rent duration field is required when offer type is rent."
      ]
    }
  }
}
```

Map the first message from each `data.errors[field]` array to the corresponding form field.

## Frontend State Rules

- Customer screen: submit the form, show the returned submission ID, and explain that the request is pending review.
- Manager list: the API returns every submission; filter by `status` locally if needed.
- Manager details: show the complete property and customer information.
- Pending submission: show approve and reject actions.
- Approved submission: hide review actions and link to `published_property_id`.
- Rejected submission: hide review actions and display `reject_reason`.
- Disable action buttons while a review request is running to prevent accidental duplicate requests.
- Refresh the selected submission from the response after approve or reject.

The backend currently does not enforce that approve or reject is called only while the submission is `pending`. The frontend should expose those actions only for `pending` submissions, but this UI rule is not a security or data-integrity guarantee. In particular, rejecting an already-approved submission does not delete or unpublish the property referenced by `published_property_id`.

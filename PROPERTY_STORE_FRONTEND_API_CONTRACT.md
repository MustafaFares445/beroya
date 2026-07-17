# Property Store API Contract for Frontend

This contract documents the current backend behavior for creating a published property and uploading its images.

## Endpoint Summary

| Purpose | Method | Path | Authentication | Content type |
| --- | --- | --- | --- | --- |
| Load enum options | `GET` | `/api/real-estate/options` | Public | — |
| Load offices | `GET` | `/api/real-estate/offices` | Public | — |
| Load categories | `GET` | `/api/real-estate/property-categories` | Public | — |
| Load subcategories | `GET` | `/api/real-estate/property-subcategories` | Public | — |
| Create property | `POST` | `/api/real-estate/properties` | Sanctum bearer token | `application/json` |
| Upload property images | `POST` | `/api/real-estate/properties/{property}/images` | Sanctum bearer token | `multipart/form-data` |

The create endpoint returns HTTP `200`, not `201`.

## Authentication and Permission

Send the authenticated user's Sanctum token:

```http
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

Authentication alone is not sufficient. The user must be allowed to manage real-estate properties. A user without a valid token receives `401`; an authenticated user without permission receives `403`.

## Form Lookup Data

Load these endpoints before displaying the create form:

- `GET /api/real-estate/options` supplies the supported enum values and Arabic labels.
- `GET /api/real-estate/offices` supplies `office_id` values.
- `GET /api/real-estate/property-categories` supplies `main_category_id` values.
- `GET /api/real-estate/property-subcategories` supplies `subcategory_id` values and their parent `property_category_id`.

The selected subcategory must belong to the selected main category. Filter the subcategory list in the frontend with:

```ts
const visibleSubcategories = subcategories.filter(
  (subcategory) => subcategory.property_category_id === form.main_category_id,
);
```

The backend derives `province_id` from the selected office. Do not send `province_id` in the create payload.

## Enums

Use the `value` returned by `/api/real-estate/options` as the submitted value and use `label` only for display.

### Property nature

| Value | Display label |
| --- | --- |
| `سكني` | سكني |
| `تجاري` | تجاري |
| `صناعي` | صناعي |
| `أراضي` | أراضي |
| `فيلات` | فيلات |

The backend also accepts legacy English aliases and normalizes them to the canonical Arabic value:

- `سكني`: `residential`, `apartment`, `house`
- `تجاري`: `commercial`, `office`, `shop`, `hotel`, `restaurant`
- `صناعي`: `industrial`, `factory`
- `أراضي`: `land`, `investment-land`, `agricultural-land`
- `فيلات`: `villa`, `chalet`, `tourism-farm`

Frontend forms should submit the canonical Arabic value returned by the options endpoint.

### Title type

| Value | Display label |
| --- | --- |
| `ملك` | ملك |
| `فروغ` | فروغ |
| `أميري` | أميري |
| `حجري` | حجري |

### Offer type

| Value | Display label | `rent_duration` behavior |
| --- | --- | --- |
| `sale` | بيع | Send `null` |
| `rent` | إيجار | Required |
| `investment` | استثمار | Send `null` |

### Rent duration

| Value | Display label |
| --- | --- |
| `daily` | يومي |
| `weekly` | أسبوعي |
| `monthly` | شهري |
| `yearly` | سنوي |

### Property status

| Value | Display label |
| --- | --- |
| `sold` | مباع |
| `available` | متاح |
| `unavailable` | غير متاح |

### TypeScript enum types

```ts
export type PropertyNature = 'سكني' | 'تجاري' | 'صناعي' | 'أراضي' | 'فيلات';
export type PropertyTitleType = 'ملك' | 'فروغ' | 'أميري' | 'حجري';
export type PropertyOfferType = 'sale' | 'rent' | 'investment';
export type PropertyRentDuration = 'daily' | 'weekly' | 'monthly' | 'yearly';
export type PropertyStatus = 'sold' | 'available' | 'unavailable';
```

The options endpoint remains the runtime source of truth. The TypeScript unions provide compile-time safety but should be updated if the backend configuration changes.

## Create Request

### Request fields

| Field | Type | Required | Rules and explanation |
| --- | --- | --- | --- |
| `offer_number` | `string` | Yes | Unique in properties; maximum 100 characters. |
| `office_id` | `integer` | Yes | Must reference an existing real-estate office. The backend derives the province from it. |
| `main_category_id` | `integer` | Yes | Must reference an existing property category. |
| `subcategory_id` | `integer` | Yes | Must reference a subcategory belonging to `main_category_id`. |
| `property_nature` | enum string | Yes | Use a canonical property-nature value from the options endpoint. |
| `title_type` | enum string | Yes | Required for every offer type, including `investment`. |
| `area` | `string` | Yes | Maximum 255 characters. |
| `district` | `string` | Yes | Maximum 255 characters. |
| `address` | `string` | Yes | Maximum 1000 characters. |
| `building` | `string` | Yes | Maximum 255 characters. |
| `floor` | `string` | Yes | Maximum 50 characters. It is a string, so values such as `ground` are supported. |
| `direction` | `string` | Yes | Maximum 100 characters. |
| `rooms_count` | `integer` | Yes | Minimum `0`. |
| `area_size` | `integer` | Yes | Minimum `0`; the API does not define a unit. |
| `price` | `integer` | Yes | Minimum `0`; the API does not define a currency. |
| `ownership_type` | `string` | Yes | Maximum 100 characters; free text, not a backend enum. |
| `offer_type` | enum string | Yes | `sale`, `rent`, or `investment`. |
| `rent_duration` | enum string or `null` | Conditional | Required when `offer_type` is `rent`; otherwise send `null`. |
| `owner_name` | `string` | Yes | Maximum 255 characters. |
| `owner_phone` | `string` | Yes | Maximum 30 characters. Keep it as a string. |
| `status` | enum string | Yes | `sold`, `available`, or `unavailable`. |

Images are not part of this JSON payload. Create the property first, then upload images using the returned property `id`.

### TypeScript request type

```ts
export interface StorePropertyPayload {
  offer_number: string;
  office_id: number;
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
  rent_duration: PropertyRentDuration | null;
  owner_name: string;
  owner_phone: string;
  status: PropertyStatus;
}
```

### JSON example: sale

```json
{
  "offer_number": "OFF-2026-0001",
  "office_id": 2,
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
  "status": "available"
}
```

### JSON example: rent

For a rental, the only contract difference is that `rent_duration` must contain a supported value:

```json
{
  "offer_type": "rent",
  "rent_duration": "monthly"
}
```

The snippet above shows the changed fields only; the request must still contain every other required field from the full sale example.

### Fetch example

```ts
interface ApiEnvelope<T> {
  status: 'success' | 'failure';
  message: string;
  data: T;
}

interface ValidationFailureData {
  errors: Record<string, string[]>;
}

export async function createProperty(
  apiBaseUrl: string,
  token: string,
  payload: StorePropertyPayload,
): Promise<Property> {
  const response = await fetch(`${apiBaseUrl}/api/real-estate/properties`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });

  const result = (await response.json()) as ApiEnvelope<
    Property | ValidationFailureData | string | null
  >;

  if (!response.ok || result.status !== 'success') {
    throw result;
  }

  return result.data as Property;
}
```

## Success Response

The response envelope is:

```ts
export interface ApiSuccess<T> {
  status: 'success';
  message: string;
  data: T;
}
```

Property response types:

```ts
export interface PropertyImage {
  id: number;
  property_id: number;
  image: string;
  url: string;
}

export interface Property {
  id: number;
  offer_number: string;
  province_id: number;
  province_name: string | null;
  office_id: number;
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
  status: PropertyStatus;
  images: PropertyImage[];
  created_at: string | null;
  updated_at: string | null;
}
```

Example HTTP `200` response:

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {
    "id": 15,
    "offer_number": "OFF-2026-0001",
    "province_id": 1,
    "province_name": "Damascus",
    "office_id": 2,
    "office_name": "Damascus Center",
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
    "status": "available",
    "images": [],
    "created_at": "2026-07-17 12:30:00",
    "updated_at": "2026-07-17 12:30:00"
  }
}
```

The category, subcategory, office, and province names depend on the selected database records. The timestamps use `YYYY-MM-DD HH:mm:ss`.

## Error Responses

All API errors use the same top-level keys: `status`, `message`, and `data`.

### `401 Unauthorized`

Returned when the bearer token is missing or invalid:

```json
{
  "status": "failure",
  "message": "التوكن غير صالح",
  "data": null
}
```

Frontend action: clear invalid authentication state and send the user to login.

### `403 Forbidden`

Returned when the authenticated user cannot manage properties:

```json
{
  "status": "failure",
  "message": "لا تملك صلاحية تنفيذ هذا الإجراء",
  "data": "your computer harmly damaged"
}
```

Frontend action: do not retry. Hide or disable property-management actions for this user and show the localized `message`. Do not display the internal `data` string to the user.

### `422 Validation Error`

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

Frontend action: map the first message in each `data.errors[field]` array to the corresponding form control. Validation messages currently follow the backend validation locale and should be displayed as returned.

### `500 Server Error`

```json
{
  "status": "failure",
  "message": "فشل تنفيذ الطلب",
  "data": null
}
```

Frontend action: show a general retry message. Do not assume the property was created; refresh the property list or search by `offer_number` before retrying to avoid a duplicate submission.

## Image Upload After Creation

After a successful create response, use `data.id`:

```http
POST /api/real-estate/properties/{propertyId}/images
Authorization: Bearer YOUR_TOKEN
Accept: application/json
Content-Type: multipart/form-data
```

Form data:

- Preferred: one or more files using `images[]`.
- Also accepted: one file using `image`.
- Allowed types: `jpg`, `jpeg`, `png`, `webp`, `gif`.
- Maximum size: 51,200 KB per file (50 MB).
- At least one valid image is required.

Browser example:

```ts
export async function uploadPropertyImages(
  apiBaseUrl: string,
  token: string,
  propertyId: number,
  files: File[],
): Promise<Property> {
  const body = new FormData();

  for (const file of files) {
    body.append('images[]', file);
  }

  const response = await fetch(
    `${apiBaseUrl}/api/real-estate/properties/${propertyId}/images`,
    {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body,
    },
  );

  const result = (await response.json()) as ApiEnvelope<
    Property | ValidationFailureData | string | null
  >;

  if (!response.ok || result.status !== 'success') {
    throw result;
  }

  return result.data as Property;
}
```

Do not set the multipart `Content-Type` header manually in browser code; the browser must add the boundary. The upload response contains the complete updated property, including its `images` array. Each image `url` is relative, for example `/data/uploads/properties/example.webp`; resolve it against the backend origin when rendering.

## Recommended Frontend Flow

1. Load options, offices, categories, and subcategories.
2. Render selects with backend labels and retain their exact values or numeric IDs.
3. When the category changes, clear an incompatible subcategory selection.
4. Show `rent_duration` only when `offer_type === 'rent'`; otherwise set it to `null`.
5. Submit the complete property JSON payload.
6. Store the returned property `id`.
7. Upload selected images in a second multipart request.
8. Replace local property state with the property returned by the image-upload response.
9. Handle `401`, `403`, `422`, and `500` separately as described above.

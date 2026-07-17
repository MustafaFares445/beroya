# عقد API لصلاحيات قسم العقارات

## المصادقة

جميع المسارات الإدارية المحمية تستخدم Sanctum وتتطلب الرأس التالي:

```http
Authorization: Bearer {token}
Accept: application/json
```

تبدأ جميع المسارات بالبادئة `/api`.

## غلاف الاستجابة

### نجاح

```json
{
  "status": "success",
  "message": "تم تنفيذ الطلب بنجاح",
  "data": {}
}
```

### فشل الصلاحية

```json
{
  "status": "failure",
  "message": "لا تملك صلاحية تنفيذ هذا الإجراء",
  "data": "your computer harmly damaged"
}
```

الحالات المستخدمة:

| HTTP status | المعنى |
| --- | --- |
| `200` | نجحت العملية. |
| `401` | رمز المصادقة مفقود أو غير صالح لمسار محمي. |
| `403` | المستخدم مسجل، لكنه خارج المستوى أو المحافظة أو المكتب المسموح. |
| `404` | المورد الموجود في المسار غير موجود. |
| `422` | بيانات الطلب غير صالحة أو كلمة المرور القديمة غير صحيحة. |

## عقد المستخدم العقاري

تظهر الحقول التالية ضمن بيانات المستخدم في تسجيل الدخول ومسارات المستخدمين:

```json
{
  "id": 15,
  "user_name": "damascus-manager",
  "gallery_id": 0,
  "real_estate_province_id": 1,
  "real_estate_province_name": "Damascus",
  "real_estate_office_id": null,
  "real_estate_office_name": null,
  "real_estate_role": "province_manager",
  "real_estate_role_label": "مدير محافظة",
  "permetions_level": 2,
  "salary": 0,
  "phone": "0999000000",
  "last_login": null,
  "is_active": true
}
```

### إنشاء أو تعديل مدير محافظة - المستوى 2

```json
{
  "user_name": "damascus-manager",
  "password": "secret-password",
  "gallery_id": 0,
  "real_estate_province_id": 1,
  "real_estate_role": "province_manager",
  "permetions_level": 2,
  "salary": 0,
  "phone": "0999000000"
}
```

لا يرسل `real_estate_office_id` للمستوى `2`.

### إنشاء أو تعديل مدير مكتب - المستوى 3

```json
{
  "user_name": "office-manager",
  "password": "secret-password",
  "gallery_id": 0,
  "real_estate_office_id": 10,
  "real_estate_role": "office_manager",
  "permetions_level": 3,
  "salary": 0,
  "phone": "0999000001"
}
```

### إنشاء أو تعديل موظف مكتب - المستوى 4

```json
{
  "user_name": "office-employee",
  "password": "secret-password",
  "gallery_id": 0,
  "real_estate_office_id": 10,
  "real_estate_role": "office_employee",
  "permetions_level": 4,
  "salary": 0,
  "phone": "0999000002"
}
```

في `PUT /api/users/{user}` يمكن حذف `password` أو إرساله بقيمة `null` للاحتفاظ بكلمة المرور الحالية. بقية الحقول المذكورة في الطلب مطلوبة.

## مصفوفة Endpoints والصلاحيات

### المسارات العامة

| Method | Endpoint | الوصول |
| --- | --- | --- |
| `GET` | `/api/provinces` | عام. |
| `GET` | `/api/provinces/{province}` | عام. |
| `GET` | `/api/real-estate/options` | عام. |
| `GET` | `/api/real-estate/offices` | عام. |
| `GET` | `/api/real-estate/offices/{realEstateOffice}` | عام. |
| `GET` | `/api/real-estate/office-phones` | عام. |
| `GET` | `/api/real-estate/office-phones/{realEstateOfficePhone}` | عام. |
| `GET` | `/api/real-estate/property-categories` | عام. |
| `GET` | `/api/real-estate/property-subcategories` | عام. |
| `GET` | `/api/real-estate/properties` | عام؛ بيانات المالك مخفية عن غير المخول. |
| `GET` | `/api/real-estate/properties/{property}` | عام؛ بيانات المالك مخفية عن غير المخول. |
| `POST` | `/api/real-estate/property-submissions` | عام لإرسال طلب جديد. |

### المحافظات

| Method | Endpoint | المستوى المسموح |
| --- | --- | --- |
| `POST` | `/api/provinces` | `1` فقط. |
| `PUT` | `/api/provinces/{province}` | `1` فقط. |
| `DELETE` | `/api/provinces/{province}` | `1` فقط. |

### المكاتب

| Method | Endpoint | المستوى والنطاق |
| --- | --- | --- |
| `POST` | `/api/real-estate/offices` | `1` لأي محافظة؛ `2` داخل محافظته. |
| `PUT` | `/api/real-estate/offices/{realEstateOffice}` | `1` لأي مكتب؛ `2` داخل محافظته؛ `3` لمكتبه فقط دون نقل المكتب إلى محافظة أخرى. |
| `DELETE` | `/api/real-estate/offices/{realEstateOffice}` | `1` لأي مكتب؛ `2` داخل محافظته. |

حمولة إنشاء أو تعديل المكتب:

```json
{
  "province_id": 1,
  "name": "Damascus Center Office",
  "address": "Main Street",
  "is_active": true
}
```

### هواتف المكاتب

| Method | Endpoint | المستوى والنطاق |
| --- | --- | --- |
| `POST` | `/api/real-estate/office-phones` | `1` لأي مكتب؛ `2` لمكتب داخل محافظته. |
| `PUT` | `/api/real-estate/office-phones/{realEstateOfficePhone}` | `1` لأي سجل؛ `2` داخل محافظته؛ `3` لسجل مكتبه فقط ودون نقله. |
| `DELETE` | `/api/real-estate/office-phones/{realEstateOfficePhone}` | `1` لأي سجل؛ `2` داخل محافظته. |

```json
{
  "real_estate_office_id": 10,
  "phone": "0111234567"
}
```

### العقارات والصور

| Method | Endpoint | المستوى المسموح |
| --- | --- | --- |
| `POST` | `/api/real-estate/properties` | `1` أو `2` أو `3`؛ و`4` إذا كان مرتبطاً بمكتب. |
| `PUT` | `/api/real-estate/properties/{property}` | نفس قاعدة الإضافة. |
| `DELETE` | `/api/real-estate/properties/{property}` | نفس قاعدة الإضافة. |
| `POST` | `/api/real-estate/properties/{property}/images` | نفس قاعدة الإضافة؛ الطلب `multipart/form-data`. |
| `DELETE` | `/api/real-estate/property-images/{image}` | نفس قاعدة الإضافة. |

صلاحية العقارات غير مقيدة بمحافظة المستخدم أو مكتبه. يبقى عقد حقول العقار والتصفية مطابقاً لدليل المرحلة الثانية `FRONTEND_PHASE_2_REAL_ESTATE_GUIDE.md`.

### طلبات العقارات

| Method | Endpoint | المستوى المسموح |
| --- | --- | --- |
| `GET` | `/api/real-estate/property-submissions` | `1` أو `2` أو `3`. |
| `GET` | `/api/real-estate/property-submissions/{submission}` | `1` أو `2` أو `3`. |
| `PUT` | `/api/real-estate/property-submissions/{submission}/approve` | `1` أو `2` أو `3`. |
| `PUT` | `/api/real-estate/property-submissions/{submission}/reject` | `1` أو `2` أو `3`، ويتطلب `reject_reason`. |

```json
{
  "reject_reason": "بيانات الملكية غير مكتملة"
}
```

### المستخدمون

| Method | Endpoint | الوصول |
| --- | --- | --- |
| `GET` | `/api/users` | `1`: الجميع؛ `2`: المستويان `3` و`4` داخل محافظته؛ `3`: المستوى `4` داخل مكتبه. |
| `GET` | `/api/users/{user}` | نفس نطاق القائمة. |
| `POST` | `/api/users` | `1`: أي مستخدم؛ `2`: مستوى `3` أو `4` داخل محافظته؛ `3`: مستوى `4` داخل مكتبه. |
| `PUT` | `/api/users/{user}` | يجب أن يكون المستخدم الحالي والمستوى والمكتب الجديد ضمن نطاق المدير نفسه. |
| `DELETE` | `/api/users/{user}` | نفس نطاق العرض؛ لا يستطيع المستخدم حذف نفسه. |
| `PUT` | `/api/users/{user}/password` | المستويات `1` إلى `4` لحسابها فقط. |

حمولة تغيير كلمة المرور:

```json
{
  "old_password": "current-password",
  "new_password": "new-password"
}
```

يمكن إرسال `id` اختيارياً، وإذا أرسل فيجب أن يساوي `{user}` في المسار.

## ملاحظات للواجهة

- استخدم `permetions_level` لإظهار الوظائف، لكن تعامل دائماً مع `403` لأن الخادم يطبق نطاق المحافظة والمكتب.
- استخدم `real_estate_province_id` عند إنشاء مدير محافظة.
- استخدم `real_estate_office_id` عند إنشاء مدير مكتب أو موظف مكتب.
- لا تعرض إدارة المحافظات إلا للمستوى `1`.
- لا تعرض مراجعة الطلبات للمستوى `4`.
- قائمة المستخدمين تعود مفلترة من الخادم حسب نطاق المستخدم المسجل.

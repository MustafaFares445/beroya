<?php

return [
    'provinces' => [
        'Damascus',
        'Rif Dimashq',
        'Aleppo',
        'Homs',
        'Hama',
        'Latakia',
        'Tartus',
        'Deir ez-Zor',
        'Raqqa',
        'Idlib',
        'Daraa',
        'Quneitra',
        'Al-Hasakah',
        'As-Suwayda',
    ],

    'taxonomy' => [
        'سكني' => [
            'منزل',
            'غرفة فندقية',
            'غرفة شبابية',
        ],
        'تجاري' => [
            'صالات تجارية',
            'مكاتب',
            'فنادق',
            'مطاعم',
            'محلات',
            'تعليمي',
            'طبي',
        ],
        'صناعي' => [
            'معامل',
            'منشئات صناعية',
        ],
        'أراضي' => [
            'أراضي استثمارية',
            'أراضي زراعية',
            'مداجن',
            'مباقر',
            'ميادين خيول',
        ],
        'فيلات' => [
            'فيلات سكنية',
            'شاليهات',
            'مزارع سياحية',
        ],
    ],

    'property_natures' => [
        [
            'value' => 'سكني',
            'label' => 'سكني',
            'aliases' => ['residential', 'apartment', 'house'],
        ],
        [
            'value' => 'تجاري',
            'label' => 'تجاري',
            'aliases' => ['commercial', 'office', 'shop', 'hotel', 'restaurant'],
        ],
        [
            'value' => 'صناعي',
            'label' => 'صناعي',
            'aliases' => ['industrial', 'factory'],
        ],
        [
            'value' => 'أراضي',
            'label' => 'أراضي',
            'aliases' => ['land', 'investment-land', 'agricultural-land'],
        ],
        [
            'value' => 'فيلات',
            'label' => 'فيلات',
            'aliases' => ['villa', 'chalet', 'tourism-farm'],
        ],
    ],

    'title_types' => [
        [
            'value' => 'ملك',
            'label' => 'ملك',
        ],
        [
            'value' => 'فروغ',
            'label' => 'فروغ',
        ],
        [
            'value' => 'أميري',
            'label' => 'أميري',
        ],
        [
            'value' => 'حجري',
            'label' => 'حجري',
        ],
    ],

    'offer_types' => [
        [
            'value' => 'sale',
            'label' => 'بيع',
        ],
        [
            'value' => 'rent',
            'label' => 'إيجار',
        ],
        [
            'value' => 'investment',
            'label' => 'استثمار',
        ],
    ],

    'rent_durations' => [
        [
            'value' => 'daily',
            'label' => 'يومي',
        ],
        [
            'value' => 'weekly',
            'label' => 'أسبوعي',
        ],
        [
            'value' => 'monthly',
            'label' => 'شهري',
        ],
        [
            'value' => 'yearly',
            'label' => 'سنوي',
        ],
    ],

    'property_statuses' => [
        [
            'value' => 'sold',
            'label' => 'مباع',
        ],
        [
            'value' => 'available',
            'label' => 'متاح',
        ],
        [
            'value' => 'unavailable',
            'label' => 'غير متاح',
        ],
    ],

    'real_estate_roles' => [
        'general_manager' => [
            'value' => 'general_manager',
            'label' => 'مدير عام',
            'can_review' => true,
        ],
        'province_manager' => [
            'value' => 'province_manager',
            'label' => 'مدير محافظة',
            'can_review' => true,
        ],
        'office_manager' => [
            'value' => 'office_manager',
            'label' => 'مدير مكتب',
            'can_review' => true,
        ],
        'office_employee' => [
            'value' => 'office_employee',
            'label' => 'موظف مكتب',
            'can_review' => false,
        ],
        'reviewer' => [
            'value' => 'reviewer',
            'label' => 'مدير مكتب',
            'can_review' => true,
        ],
        'agent' => [
            'value' => 'agent',
            'label' => 'موظف مكتب',
            'can_review' => false,
        ],
        'senior-agent' => [
            'value' => 'senior-agent',
            'label' => 'موظف مكتب',
            'can_review' => false,
        ],
    ],
];

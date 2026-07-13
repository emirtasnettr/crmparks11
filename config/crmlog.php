<?php

return [
    'name' => env('APP_NAME', 'CRMLog'),

    'company' => [
        'name' => env('CRMLOG_COMPANY_NAME', 'CRMLog'),
        'email' => env('CRMLOG_COMPANY_EMAIL', 'info@crmlog.com'),
    ],

    'pagination' => [
        'per_page' => 25,
    ],

    'upload' => [
        'max_size' => 10240, // KB
        'allowed_mimes' => [
            'pdf', 'xlsx', 'xls', 'csv', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'zip',
        ],
    ],

    'contract' => [
        'default_reminder_days' => 30,
    ],

    'roles' => [
        'super_admin' => 'Süper Admin',
        'general_manager' => 'Genel Müdür',
        'sales_manager' => 'Satış Müdürü',
        'operations_specialist' => 'Operasyon Uzmanı',
        'courier' => 'Kurye',
        'business' => 'İşletme',
        'agency' => 'Acente',
    ],
];

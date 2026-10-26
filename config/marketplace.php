<?php

return [
    'brand' => env('MARKETPLACE_BRAND', 'ChachaPrime'),
    'default_currency' => env('MARKETPLACE_DEFAULT_CURRENCY', 'USD'),
    'currencies' => [
        'PKR' => ['name' => 'Pakistani Rupee', 'symbol' => 'Rs'],
        'CNY' => ['name' => 'Chinese Yuan', 'symbol' => '¥'],
        'USD' => ['name' => 'US Dollar', 'symbol' => '$'],
        'EUR' => ['name' => 'Euro', 'symbol' => '€'],
        'GBP' => ['name' => 'British Pound', 'symbol' => '£'],
        'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥'],
        'AED' => ['name' => 'UAE Dirham', 'symbol' => 'د.إ'],
        'SAR' => ['name' => 'Saudi Riyal', 'symbol' => 'ر.س'],
        'TRY' => ['name' => 'Turkish Lira', 'symbol' => '₺'],
    ],
    'locales' => [
        'en' => 'English',
        'zh_CN' => '简体中文',
        'ur' => 'اردو',
        'ar' => 'العربية',
        'ja' => '日本語',
    ],
];

<?php

return [
    'roles' => [
        'admin' => 'مدير النظام',
        'supervisor' => 'مشرف',
        'agent' => 'موظف دعم',
    ],
    'statuses' => [
        'open' => 'مفتوحة',
        'pending' => 'بانتظار العميل',
        'on_hold' => 'معلّقة',
        'solved' => 'تم الحل',
        'closed' => 'مغلقة',
    ],
    'priorities' => [
        'low' => 'منخفضة',
        'normal' => 'عادية',
        'high' => 'عالية',
        'urgent' => 'عاجلة',
    ],
    'channels' => [
        'portal' => 'البوابة',
        'email' => 'البريد',
        'agent' => 'فريق الدعم',
    ],
    'authors' => [
        'requester' => 'مقدّم الطلب',
        'agent' => 'موظف الدعم',
        'system' => 'النظام',
    ],
    'kb_statuses' => [
        'draft' => 'مسودة',
        'published' => 'منشورة',
    ],
];

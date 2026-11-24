<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Support timezone
    |--------------------------------------------------------------------------
    | Business hours, SLA deadlines and dates shown in the interface are
    | interpreted in this timezone. Timestamps are stored in UTC.
    */
    'timezone' => env('HELPDESK_TIMEZONE', 'Africa/Cairo'),

    /*
    |--------------------------------------------------------------------------
    | Demo company (fictional)
    |--------------------------------------------------------------------------
    | The demo instance presents itself as the support centre of this fictional
    | ISP. All contact details are intentionally fake/reserved.
    */
    'company' => [
        'name_ar' => 'شبكة فيبر نت',
        'name_en' => 'FiberNet ISP',
        'phone' => '01000000000',
        'email' => 'support@fibernet.example.com',
        'address_ar' => 'القاهرة الجديدة',
        'address_en' => 'New Cairo',
    ],

    /*
    |--------------------------------------------------------------------------
    | Demo mode
    |--------------------------------------------------------------------------
    | When enabled the interface shows a clear "demo data" banner. Demo
    | accounts and sample tickets are only created by DemoDataSeeder.
    */
    'demo' => (bool) env('DEMO_MODE', false),

    'demo_notice' => 'This installation contains demo data created by DemoDataSeeder.',

    /*
    |--------------------------------------------------------------------------
    | Demo sign-in accounts
    |--------------------------------------------------------------------------
    | One instant demo account per role. They are created by DemoDataSeeder
    | and are flagged with users.is_demo so they cannot be edited or removed.
    */
    'demo_accounts' => [
        'admin' => 'admin@demo.helpdesk.test',
        'supervisor' => 'supervisor@demo.helpdesk.test',
        'agent' => 'agent@demo.helpdesk.test',
    ],

    'demo_attachments' => [
        'max_kilobytes' => 2048,
        'max_files' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Ticket references
    |--------------------------------------------------------------------------
    */
    'reference_prefix' => 'TKT',
    'tracking_link_days' => (int) env('HELPDESK_TRACKING_DAYS', 90),
    'csat_link_days' => (int) env('HELPDESK_CSAT_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Attachments
    |--------------------------------------------------------------------------
    | Files are stored on the private "local" disk and are only reachable
    | through temporary signed URLs generated after an authorization check.
    */
    'attachments' => [
        'disk' => 'local',
        'directory' => 'tickets',
        'max_kilobytes' => (int) env('HELPDESK_MAX_ATTACHMENT_KB', 5120),
        'max_files' => (int) env('HELPDESK_MAX_ATTACHMENTS', 5),
        'extensions' => [
            'jpg', 'jpeg', 'png', 'webp', 'gif',
            'pdf', 'txt', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'zip',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Inbox simulation
    |--------------------------------------------------------------------------
    */
    'inbox_path' => 'inbox',
    'max_inbound_attachment_kilobytes' => 5120,

];

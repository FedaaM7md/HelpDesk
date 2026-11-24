<?php

return [
    'roles' => [
        'admin' => 'Administrator',
        'supervisor' => 'Supervisor',
        'agent' => 'Agent',
    ],
    'statuses' => [
        'open' => 'Open',
        'pending' => 'Pending customer',
        'on_hold' => 'On hold',
        'solved' => 'Solved',
        'closed' => 'Closed',
    ],
    'priorities' => [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],
    'channels' => [
        'portal' => 'Portal',
        'email' => 'Email',
        'agent' => 'Agent',
    ],
    'authors' => [
        'requester' => 'Requester',
        'agent' => 'Agent',
        'system' => 'System',
    ],
    'kb_statuses' => [
        'draft' => 'Draft',
        'published' => 'Published',
    ],
];

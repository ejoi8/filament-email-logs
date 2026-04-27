<?php

use Filament\Support\Icons\Heroicon;

return [
    'logging' => [
        'enabled' => true,
    ],

    'authorization' => [
        'ability' => null,
    ],

    'navigation' => [
        'group' => 'System',
        'sort' => 1,
        'icon' => Heroicon::OutlinedEnvelope,
    ],
];

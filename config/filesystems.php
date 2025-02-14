<?php

return [

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'backups' => [
            'driver' => 'local',
            'root' => env('BACKUP_DIR_PATH', dirname(base_path())),
            'throw' => false,
        ],

        'tests' => [
            'driver' => 'local',
            'root' => storage_path('tests'),
            'throw' => false,
        ],

        'demo' => [
            'driver' => 'local',
            'root' => storage_path('demo'),
            'throw' => false,
        ],
    ],

];

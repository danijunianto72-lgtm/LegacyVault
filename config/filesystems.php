<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

'disks' => [

    'local' => [
        'driver' => 'local',
        'root' => env('APP_ENV') === 'production' 
            ? '/tmp/storage/app' 
            : storage_path('app'),
        'throw' => false,
    ],

    'public' => [
        'driver' => 'local',
        'root' => env('APP_ENV') === 'production' 
            ? '/tmp/storage/app/public' 
            : storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
        'throw' => false,
    ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],
        'supabase' => [
            'driver' => 's3',
            'key' => env('SUPABASE_ACCESS_KEY_ID'),
            'secret' => env('SUPABASE_SECRET_ACCESS_KEY'),
            'region' => env('SUPABASE_DEFAULT_REGION', 'ap-northeast-1'),
            'bucket' => 'vault_attachments',
            'url' => 'https://krfhgwclwpqbyujxlcji.supabase.co/storage/v1/object/public/vault_attachments',
            'endpoint' => env('SUPABASE_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'throw' => false,
        ],
    'supabase_proofs' => [              
            'driver' => 's3',
            'key' => env('SUPABASE_ACCESS_KEY_ID'),
            'secret' => env('SUPABASE_SECRET_ACCESS_KEY'),
            'region' => env('SUPABASE_DEFAULT_REGION', 'ap-northeast-1'),
            'bucket' => 'payment_proofs',
            'url' => 'https://krfhgwclwpqbyujxlcji.supabase.co/storage/v1/object/public/payment_proofs',
            'endpoint' => env('SUPABASE_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'throw' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];

<?php

return [
    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK', 'local'),
        // Keep temp-upload validation broad; enforce strict file type in component rules.
        'rules' => ['required', 'file', 'max:' . env('LIVEWIRE_TEMP_MAX_UPLOAD_KB', 20480)],
        'directory' => env('LIVEWIRE_TEMP_UPLOAD_DIRECTORY', 'livewire-tmp'),
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
            'pdf',
        ],
        'max_upload_time' => (int) env('LIVEWIRE_MAX_UPLOAD_TIME_MINUTES', 10),
        'cleanup' => true,
    ],

    // Increase guard rails to avoid 422 on larger multipart payloads.
    'payload' => [
        'max_size' => (int) env('LIVEWIRE_PAYLOAD_MAX_SIZE_BYTES', 15 * 1024 * 1024),
        'max_nesting_depth' => 10,
        'max_calls' => 50,
        'max_components' => 20,
    ],
];

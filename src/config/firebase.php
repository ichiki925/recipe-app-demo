<?php

return [
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'credentials' => env('FIREBASE_CREDENTIALS', storage_path('firebase-credentials.json')),
    'database_url' => env('FIREBASE_DATABASE_URL'),
];

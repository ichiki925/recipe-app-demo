<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

Route::get('/avatars/{filename}', function ($filename) {
    $path = storage_path('app/public/avatars/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    return Response::file($path);
});

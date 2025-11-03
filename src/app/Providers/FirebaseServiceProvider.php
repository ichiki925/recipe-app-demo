<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('firebase', function ($app) {
            $serviceAccountPath = storage_path('firebase-credentials.json');
            
            if (!file_exists($serviceAccountPath)) {
                throw new \Exception("Firebase credentials file not found at: {$serviceAccountPath}");
            }

            return (new Factory)
                ->withServiceAccount($serviceAccountPath)
                ->create();
        });

        $this->app->singleton('firebase.auth', function ($app) {
            return $app->make('firebase')->createAuth();
        });
    }

    public function boot()
    {
        //
    }
}

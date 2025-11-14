<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\HealthServiceProvider::class,
    
    // Load development-only providers
    ...app()->environment('local', 'testing') && class_exists(Laravel\Pail\PailServiceProvider::class)
        ? [Laravel\Pail\PailServiceProvider::class]
        : [],
];

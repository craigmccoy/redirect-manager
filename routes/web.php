<?php

use App\Models\Redirect;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $stats = [
        'total_redirects' => Redirect::count(),
        'active_redirects' => Redirect::active()->count(),
        'domain_redirects' => Redirect::where('source_type', 'domain')->count(),
        'url_redirects' => Redirect::where('source_type', 'url')->count(),
    ];
    
    return view('status', compact('stats'));
});

// Fallback route to let redirect middleware handle all unmatched requests
Route::fallback(function () {
    abort(404);
});

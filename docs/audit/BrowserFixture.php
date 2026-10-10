<?php

// Creates only the explicitly selected disposable database for this audit.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$expected = str_replace('\\', '/', realpath(__DIR__.'/../../storage/framework/testing').'/requirements-audit.sqlite');
if (config('database.default') !== 'sqlite' || str_replace('\\', '/', config('database.connections.sqlite.database')) !== $expected) {
    throw new RuntimeException('Refusing to seed a database other than the disposable audit SQLite file.');
}

Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
$owner = App\Models\User::factory()->create(['name' => 'Audit Owner', 'email' => 'lendly.audit.owner@gmail.com', 'password' => 'AuditPass2026!', 'email_verified_at' => now()]);
$renter = App\Models\User::factory()->create(['name' => 'Audit Renter', 'email' => 'lendly.audit.renter@gmail.com', 'password' => 'AuditPass2026!', 'email_verified_at' => now()]);
$category = App\Models\Category::create(['name' => 'Audit cameras', 'slug' => 'audit-cameras']);
$base = ['owner_id' => $owner->id, 'category_id' => $category->id, 'description' => 'Disposable browser fixture.', 'condition' => 'good', 'price_per_day' => 100, 'location' => 'Manila', 'latitude' => 14.5995, 'longitude' => 120.9842, 'max_rental_duration_days' => 10, 'status' => 'published'];
$camera = App\Models\Listing::create(array_merge($base, ['name' => 'Audit camera']));
App\Models\ListingImage::create(['listing_id' => $camera->id, 'path' => 'audit/photo.png', 'sort_order' => 0]);
App\Models\Listing::create(array_merge($base, ['name' => 'Audit far camera', 'latitude' => 14.0997, 'longitude' => 120.9425]));
App\Models\Listing::create(array_merge($base, ['name' => '<img src=x onerror="this.outerHTML=\'<b id=audit-xss-proof>Audit script executed</b>\'">', 'latitude' => 14.61]));
echo "Disposable browser fixture ready.\n";

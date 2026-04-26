<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $tag = App\Models\Tag::first();
    if (!$tag) die("No tags found");
    event(new App\Events\RfidScanned($tag, 'check', 'Test'));
    echo "Broadcast Sent\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

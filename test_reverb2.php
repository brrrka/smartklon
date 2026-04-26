<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tag;
use App\Events\RfidScanned;
use Illuminate\Support\Facades\Event;

// We will use the broadcast manager directly to see if it throws
$tag = Tag::first();
if (!$tag) die("No tags");

try {
    // Send event
    broadcast(new RfidScanned($tag, 'check', 'Test'));
    echo "Broadcast dispatched.\n";
} catch (\Exception $e) {
    echo "Broadcast failed: " . $e->getMessage() . "\n";
}

<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
$ended = \App\Models\ChatbotLead::where('live_chat_status', 'ended')->count();
echo "Ended leads: " . $ended . "\n";
$all = \App\Models\ChatbotLead::count();
echo "All leads: " . $all . "\n";
$statuses = \App\Models\ChatbotLead::pluck('live_chat_status')->toArray();
echo "Statuses: " . implode(', ', $statuses) . "\n";

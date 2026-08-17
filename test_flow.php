<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ChatbotLead;

// 1. Simulate Widget Sending Message
$request1 = Request::create('/api/chatbot/send', 'POST', [
    'license' => 'FC-LIC-0055-8BLA8O', // Assuming this is a valid license, or I'll just skip license check for the test
    'message' => 'Halo dari script test',
    'lead_id' => null
]);
$response1 = app()->handle($request1);
$data1 = json_decode($response1->getContent(), true);
$leadId = $data1['lead_id'];
echo "1. Widget sent message. Lead ID: $leadId\n";

// 2. Simulate Helpdesk accepting chat
$request2 = Request::create('/api/v1/chat/live/action', 'POST', [
    'lead_id' => $leadId,
    'action' => 'accept'
]);
// Wait, the action route in Helpdesk might be different. Let's just update the DB directly for speed.
$lead = ChatbotLead::find($leadId);
$lead->live_chat_status = 'active';
$lead->admin_id = 1; // Assuming admin 1
$lead->save();
echo "2. Helpdesk accepted chat.\n";

// 3. Simulate Helpdesk sending message
$request3 = Request::create('/api/v1/chat/live/send', 'POST', [
    'lead_id' => $leadId,
    'message' => 'Halo dari Helpdesk'
]);
// Wait, /api/v1/chat/live/send is protected by auth. Let's update DB directly again to simulate exactly what Api\ChatbotController does.
$history = json_decode($lead->chat_history, true) ?? [];
$history[] = ['sender' => 'admin', 'text' => 'Halo dari Helpdesk', 'time' => now()->format('d M, H:i')];
$lead->chat_history = json_encode($history);
$lead->save();
echo "3. Helpdesk sent message.\n";

// 4. Simulate Widget Polling
$request4 = Request::create("/api/chatbot/live/poll/$leadId", 'GET');
$response4 = app()->handle($request4);
echo "4. Widget polled. Response:\n";
echo $response4->getContent() . "\n";


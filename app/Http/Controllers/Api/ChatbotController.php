<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\ChatbotKnowledge;
use App\Models\ChatbotLead;

class ChatbotController extends Controller
{
    /**
     * Process a chat message from a visitor.
     */
    public function send(Request $request)
    {
        $licenseKey = $request->header('X-FutureCloud-License');

        if (!$licenseKey) {
            return response()->json(['error' => 'Missing License Key'], 401);
        }

        $client = Client::where('license_key', $licenseKey)->first();

        if (!$client || $client->status !== 'active') {
            return response()->json(['error' => 'Invalid or inactive License Key'], 403);
        }

        $message = strtolower($request->input('message'));
        $sessionId = $request->input('session_id');

        // Basic keyword matching (can be upgraded to NLP/AI later)
        $knowledge = ChatbotKnowledge::where('client_id', $client->id)
            ->where(function($q) use ($message) {
                $q->where('keyword', 'LIKE', "%{$message}%")
                  ->orWhereRaw("? LIKE CONCAT('%', keyword, '%')", [$message]);
            })->first();

        $reply = $knowledge ? $knowledge->response : "Maaf, saya tidak mengerti. Ingin berbicara dengan admin?";

        return response()->json([
            'reply' => $reply,
            'source' => 'bot'
        ]);
    }

    /**
     * Request a live chat session with an admin.
     */
    public function requestLiveChat(Request $request)
    {
        $licenseKey = $request->header('X-FutureCloud-License');
        $client = Client::where('license_key', $licenseKey)->first();

        if (!$client || $client->status !== 'active') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $lead = ChatbotLead::create([
            'client_id' => $client->id,
            'session_id' => $request->input('session_id'),
            'name' => $request->input('name') ?? 'Visitor',
            'phone' => $request->input('phone'),
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Live chat requested. Please wait for an admin to join.',
            'lead_id' => $lead->id
        ]);
    }
}

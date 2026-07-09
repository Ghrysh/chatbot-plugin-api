<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatbotLead;
use Carbon\Carbon;

class LiveChatAdminController extends Controller
{
    public function getHistory($id)
    {
        $lead = ChatbotLead::findOrFail($id);
        
        // Only return if it's pending or active
        return response()->json([
            'status' => $lead->live_chat_status,
            'history' => json_decode($lead->chat_history, true) ?? [],
            'lead_name' => $lead->name ?? $lead->ip_address
        ]);
    }

    public function replyMessage(Request $request, $id)
    {
        $request->validate(['message' => 'required|string']);
        
        $lead = ChatbotLead::findOrFail($id);
        $history = json_decode($lead->chat_history, true) ?? [];
        
        $history[] = [
            'sender' => 'admin',
            'text' => $request->message,
            'time' => now()->format('d M, H:i')
        ];
        
        $lead->update([
            'chat_history' => json_encode($history),
            'live_chat_status' => 'active',
            'admin_id' => auth()->id() // link to the logged in admin
        ]);

        return response()->json(['success' => true]);
    }

    public function resolveChat($id)
    {
        $lead = ChatbotLead::findOrFail($id);
        
        $history = json_decode($lead->chat_history, true) ?? [];
        $history[] = [
            'sender' => 'system',
            'text' => 'Sesi Live Chat telah ditutup oleh Admin.',
            'time' => now()->format('d M, H:i')
        ];

        $lead->update([
            'live_chat_status' => 'ended',
            'chat_history' => json_encode($history)
        ]);

        return response()->json(['success' => true]);
    }
}

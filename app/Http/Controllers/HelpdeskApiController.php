<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatbotLead;
use App\Models\Client;

class HelpdeskApiController extends Controller
{
    private function getClient(Request $request)
    {
        $license = $request->input('license') ?? $request->query('license');
        return Client::where('license_key', $license)->first();
    }

    /**
     * Poll chats for helpdesk dashboard
     */
    public function poll(Request $request)
    {
        $client = $this->getClient($request);
        if (!$client) {
            return response()->json(['pending' => [], 'active' => [], 'ended' => []]);
        }

        $helpdeskId = $request->input('helpdesk_id') ?? $request->query('helpdesk_id');

        $leads = ChatbotLead::where('client_id', $client->id)
            ->orderBy('updated_at', 'desc')
            ->get();

        // Pending: belum di-claim siapapun
        $pending = $leads->where('live_chat_status', 'pending')
            ->whereNull('helpdesk_id')
            ->values();

        // Active: yang saya handle
        $active = $leads->where('live_chat_status', 'active')
            ->where('helpdesk_id', (int)$helpdeskId)
            ->values();

        // Active milik helpdesk lain (tampilkan dengan info siapa yang handle)
        $activeOthers = $leads->where('live_chat_status', 'active')
            ->where('helpdesk_id', '!=', null)
            ->where('helpdesk_id', '!=', (int)$helpdeskId)
            ->values();

        // Ended: semua yang sudah selesai (terbatas 20 terakhir)
        $ended = $leads->where('live_chat_status', 'ended')
            ->take(20)
            ->values();

        return response()->json([
            'pending' => $pending,
            'active' => $active,
            'active_others' => $activeOthers,
            'ended' => $ended,
        ]);
    }

    /**
     * Claim a pending chat (anti-double handle)
     */
    public function claim(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|integer',
            'helpdesk_id' => 'required|integer',
            'helpdesk_name' => 'required|string',
        ]);

        $lead = ChatbotLead::find($request->lead_id);

        if (!$lead) {
            return response()->json(['success' => false, 'error' => 'Chat tidak ditemukan.'], 404);
        }

        // Anti-double handle: cek apakah sudah di-claim
        if ($lead->helpdesk_id !== null && $lead->live_chat_status === 'active') {
            return response()->json([
                'success' => false,
                'error' => "Chat ini sudah ditangani oleh {$lead->helpdesk_name}.",
            ], 409);
        }

        $history = json_decode($lead->chat_history, true) ?? [];
        $history[] = [
            'sender' => 'system',
            'text' => "{$request->helpdesk_name} bergabung dalam obrolan.",
            'time' => now()->format('d M, H:i'),
        ];

        $lead->update([
            'live_chat_status' => 'active',
            'helpdesk_id' => $request->helpdesk_id,
            'helpdesk_name' => $request->helpdesk_name,
            'chat_history' => json_encode($history),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Send a message from helpdesk
     */
    public function send(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|integer',
            'helpdesk_id' => 'required|integer',
            'helpdesk_name' => 'required|string',
            'message' => 'required|string',
        ]);

        $lead = ChatbotLead::find($request->lead_id);

        if (!$lead) {
            return response()->json(['success' => false, 'error' => 'Chat tidak ditemukan.'], 404);
        }

        // Verify this helpdesk owns this chat
        if ((int)$lead->helpdesk_id !== (int)$request->helpdesk_id) {
            return response()->json(['success' => false, 'error' => 'Anda tidak menangani chat ini.'], 403);
        }

        $history = json_decode($lead->chat_history, true) ?? [];
        $history[] = [
            'sender' => 'admin',
            'text' => $request->message,
            'time' => now()->format('d M, H:i'),
            'agent' => $request->helpdesk_name,
        ];

        $lead->update([
            'chat_history' => json_encode($history),
            'live_chat_status' => 'active',
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * End a chat session
     */
    public function endChat(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|integer',
            'helpdesk_id' => 'required|integer',
            'helpdesk_name' => 'required|string',
        ]);

        $lead = ChatbotLead::find($request->lead_id);

        if (!$lead) {
            return response()->json(['success' => false, 'error' => 'Chat tidak ditemukan.'], 404);
        }

        $history = json_decode($lead->chat_history, true) ?? [];
        $history[] = [
            'sender' => 'system',
            'text' => "Sesi Live Chat dengan {$request->helpdesk_name} telah berakhir. Anda kembali terhubung dengan Asisten Virtual.",
            'time' => now()->format('d M, H:i'),
        ];

        $lead->update([
            'live_chat_status' => 'ended',
            'chat_history' => json_encode($history),
        ]);

        return response()->json(['success' => true]);
    }
}

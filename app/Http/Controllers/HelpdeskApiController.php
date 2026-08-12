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
     * Poll ALL chats for helpdesk dashboard
     * Shows chatbot AI conversations AND live chat
     */
    public function poll(Request $request)
    {
        $client = $this->getClient($request);
        if (!$client) {
            return response()->json(['all_chats' => [], 'active' => [], 'active_others' => [], 'ended' => []]);
        }

        $helpdeskId = $request->input('helpdesk_id') ?? $request->query('helpdesk_id');

        $leads = ChatbotLead::where('client_id', $client->id)
            ->orderBy('updated_at', 'desc')
            ->get();

        // ALL CHATS: semua chat yang belum di-handle oleh helpdesk manapun
        // (masih dihandle oleh bot, atau baru saja masuk live chat pending)
        $allChats = $leads->filter(function ($lead) {
            // Tampilkan chat yang:
            // 1. Belum di-handle helpdesk (helpdesk_id null) DAN belum ended
            // 2. Atau yang pending live chat
            return ($lead->helpdesk_id === null && $lead->live_chat_status !== 'ended');
        })->values();

        // Active: yang saya handle
        $active = $leads->filter(function ($lead) use ($helpdeskId) {
            return $lead->live_chat_status === 'active'
                && (int)$lead->helpdesk_id === (int)$helpdeskId;
        })->values();

        // Active milik helpdesk lain
        $activeOthers = $leads->filter(function ($lead) use ($helpdeskId) {
            return $lead->live_chat_status === 'active'
                && $lead->helpdesk_id !== null
                && (int)$lead->helpdesk_id !== (int)$helpdeskId;
        })->values();

        // Ended: semua yang sudah selesai (terbatas 20 terakhir)
        $ended = $leads->where('live_chat_status', 'ended')
            ->take(20)
            ->values();

        return response()->json([
            'all_chats' => $allChats,
            'active' => $active,
            'active_others' => $activeOthers,
            'ended' => $ended,
        ]);
    }

    /**
     * Claim/Handle a chat (anti-double handle)
     * This takes over from the bot with an automatic handover message
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
        
        // Pesan otomatis dari bot bahwa sesi diserahkan ke tim helpdesk
        $history[] = [
            'sender' => 'bot',
            'text' => "Untuk membantu Anda lebih lanjut, saya serahkan percakapan ini kepada tim kami. {$request->helpdesk_name} akan melanjutkan percakapan ini. Terima kasih atas kesabarannya! 🙏",
            'time' => now()->format('d M, H:i'),
        ];

        $history[] = [
            'sender' => 'system',
            'text' => "{$request->helpdesk_name} telah mengambil alih percakapan ini.",
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
            'text' => "Sesi dengan {$request->helpdesk_name} telah berakhir. Anda kembali terhubung dengan Asisten Virtual kami.",
            'time' => now()->format('d M, H:i'),
        ];

        $lead->update([
            'live_chat_status' => 'ended',
            'helpdesk_id' => null,
            'helpdesk_name' => null,
            'chat_history' => json_encode($history),
        ]);

        return response()->json(['success' => true]);
    }
}

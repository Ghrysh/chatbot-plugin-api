<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatbotLead;
use App\Models\ChatbotKnowledge;
use Illuminate\Support\Str;

class ChatbotApiController extends Controller
{
    /**
     * Helper to get the first client ID.
     * In a real SaaS, the client ID would be identified via an API Key or Origin header.
     */
    private function getClientId(Request $request)
    {
        // Dummy logic: Get the first client from DB.
        $client = \App\Models\Client::first();
        return $client ? $client->id : null;
    }

    public function sendMessage(Request $request)
    {
        $clientId = $this->getClientId($request);
        if (!$clientId) return response()->json(['reply' => 'Sistem belum dikonfigurasi.']);

        $message = $request->input('message');
        $isFollowUp = $request->input('is_followup', false);
        $leadId = $request->input('lead_id');
        $history = $request->input('chat_history', []);
        
        $lead = null;
        if ($leadId) {
            $lead = ChatbotLead::find($leadId);
        }
        
        if (!$lead) {
            $lead = ChatbotLead::create([
                'client_id' => $clientId,
                'ip_address' => $request->ip(),
                'topic_context' => 'Umum',
                'chat_history' => json_encode($history),
                'live_chat_status' => 'none',
                'contact_info' => '-'
            ]);
        } else {
            $lead->chat_history = json_encode($history);
            $lead->save();
        }

        if ($request->input('is_autoclose')) {
            $lead->live_chat_status = 'ended';
            $lead->save();
            return response()->json(['success' => true]);
        }

        if ($isFollowUp) {
            $lead->contact_info = $message;
            $lead->topic_context = $request->input('last_chat', 'Umum');
            $lead->save();

            return response()->json([
                'reply' => "Terima kasih! Kontak Anda ($message) telah kami simpan. Tim CS kami akan segera menghubungi Anda.",
                'is_finished' => true,
                'lead_id' => $lead->id
            ]);
        }

        // Basic NLP / Keyword Matching
        $knowledges = ChatbotKnowledge::where('client_id', $clientId)->get();
        $reply = null;
        $matchedTopic = 'Umum';

        $lowerMsg = strtolower($message);
        
        foreach ($knowledges as $k) {
            $keywords = is_string($k->keywords) ? json_decode($k->keywords, true) : $k->keywords;
            if (!$keywords) continue;

            foreach ($keywords as $kw) {
                if (Str::contains($lowerMsg, strtolower(trim($kw)))) {
                    $reply = $k->response;
                    $matchedTopic = $k->topic ?? 'Umum';
                    break 2;
                }
            }
        }

        if (!$reply) {
            $reply = "Maaf, Bot belum mengerti pertanyaan Anda. Apakah Anda ingin berbicara langsung dengan tim Support/CS kami?";
            $lead->topic_context = 'Unrecognized: ' . Str::limit($message, 30);
            $lead->save();
            return response()->json([
                'reply' => $reply,
                'show_live_chat_btn' => true,
                'lead_id' => $lead->id
            ]);
        }

        $lead->topic_context = $matchedTopic;
        $lead->save();

        return response()->json([
            'reply' => $reply,
            'lead_id' => $lead->id
        ]);
    }

    public function requestLiveChat(Request $request)
    {
        $lead = ChatbotLead::findOrFail($request->input('lead_id'));
        $lead->live_chat_status = 'pending';
        $lead->topic_context = 'Live Chat Request';
        $lead->save();

        return response()->json(['success' => true, 'lead_id' => $lead->id]);
    }

    public function pollLiveChat($lead_id)
    {
        $lead = ChatbotLead::find($lead_id);
        if (!$lead) return response()->json(['status' => 'none']);

        $history = json_decode($lead->chat_history, true) ?? [];
        $adminName = 'CS Agent'; // Can be fetched from relation if needed
        
        if ($lead->admin_id) {
            $admin = \App\Models\User::find($lead->admin_id);
            if ($admin) $adminName = $admin->name;
        }

        return response()->json([
            'status' => $lead->live_chat_status, // pending, active, ended
            'history' => $history,
            'admin_name' => $adminName
        ]);
    }

    public function sendLiveChatMessage(Request $request)
    {
        $lead = ChatbotLead::findOrFail($request->input('lead_id'));
        $history = json_decode($lead->chat_history, true) ?? [];
        
        // Ensure we don't overwrite if client already appended locally, but usually we just replace with what the client sends
        if ($request->has('chat_history')) {
            $lead->chat_history = json_encode($request->input('chat_history'));
        } else {
            $history[] = [
                'sender' => 'user',
                'text' => $request->input('message'),
                'time' => now()->format('d M, H:i')
            ];
            $lead->chat_history = json_encode($history);
        }

        $lead->save();

        return response()->json(['success' => true]);
    }
}

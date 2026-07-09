<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\ChatbotKnowledge;
use App\Models\ChatbotLead;
use Illuminate\Support\Facades\Http;

class ChatbotController extends Controller
{
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

        $topic = $request->topic ?? 'Umum'; 
        $rawMessage = strtolower(trim($request->message));
        $originalMessage = trim($request->message);
        $sessionId = $request->input('session_id');

        // 1. DICTIONARY SLANG
        $slangDict = [
            'gmn' => 'bagaimana', 'gimana' => 'bagaimana', 'bgmn' => 'bagaimana', 'gmna' => 'bagaimana',
            'brp' => 'berapa', 'brapa' => 'berapa', 'brpa' => 'berapa', 'brap' => 'berapa', 'piro' => 'berapa',
            'klo' => 'kalau', 'kalo' => 'kalau', 'klau' => 'kalau',
            'bikin' => 'buat', 'bs' => 'bisa', 'gk' => 'tidak', 'ga' => 'tidak', 'gak' => 'tidak', 'ngga' => 'tidak', 'nggak' => 'tidak',
            'tdk' => 'tidak', 'dgn' => 'dengan', 'yg' => 'yang', 'utk' => 'untuk',
            'makasih' => 'terimakasih', 'trims' => 'terimakasih', 'thx' => 'terimakasih', 'mksh' => 'terimakasih',
            'pw' => 'password', 'pass' => 'password', 'loginnya' => 'login',
            'hrga' => 'harga', 'hrg' => 'harga', 'haarga' => 'harga', 'harg' => 'harga',
            'pket' => 'paket', 'pkt' => 'paket', 'pakat' => 'paket', 'pakt' => 'paket',
            'dpt' => 'dapat', 'dapet' => 'dapat', 'dapetnya' => 'dapat', 'dptnya' => 'dapat',
            'aja' => 'saja', 'sja' => 'saja', 'doang' => 'saja',
            'gartis' => 'gratis', 'grts' => 'gratis', 'free' => 'gratis', 'gratisan' => 'gratis', 'gretong' => 'gratis',
            'pmoela' => 'pemula', 'pmula' => 'pemula', 'pemola' => 'pemula', 'pmla' => 'pemula', 'pemulaa' => 'pemula', 'mula' => 'pemula',
            'propesional' => 'profesional', 'pro' => 'profesional', 'profesinal' => 'profesional', 'prfessional' => 'profesional', 'ptofesional' => 'profesional',
            'bisns' => 'bisnis', 'bsnis' => 'bisnis', 'bsns' => 'bisnis', 'bussines' => 'bisnis', 'business' => 'bisnis', 'biznis' => 'bisnis',
            'ftr' => 'fitur', 'isinya' => 'fitur', 'fasilitas' => 'fitur',
            'bda' => 'beda', 'bdanya' => 'beda', 'bedanya' => 'beda', 'perbedaan' => 'beda'
        ];

        // 2. CLEANSING PESAN UNTUK PENCOCOKAN KEYWORD
        $cleanMessage = preg_replace('/[^\w\s]/', '', $rawMessage);
        $words = explode(' ', $cleanMessage);
        foreach($words as &$w) {
            if(isset($slangDict[$w])) $w = $slangDict[$w];
        }
        $message = implode(' ', $words);

        // 3. GET IP & IDENTIFIKASI LEAD
        $realIp = $request->ip();
        if ($request->hasHeader('X-Forwarded-For')) {
            $ips = explode(',', $request->header('X-Forwarded-For'));
            $realIp = trim($ips[0]);
        }

        $lead = null;
        if ($request->lead_id) {
            $lead = ChatbotLead::where('client_id', $client->id)->find($request->lead_id);
        }

        // Jika Sedang Live Chat
        if ($lead && in_array($lead->live_chat_status, ['pending', 'active']) && !$request->is_autoclose) {
            $history = json_decode($lead->chat_history, true) ?? [];
            $history[] = ['sender' => 'user', 'text' => $originalMessage, 'time' => now()->format('d M, H:i')];
            $lead->update(['chat_history' => json_encode($history), 'last_message' => $originalMessage]);
            return response()->json(['reply' => null, 'lead_id' => $lead->id, 'show_live_chat_btn' => false]);
        }

        if ($request->is_autoclose) {
            if ($lead) {
                $contactInfo = 'Diakhiri Otomatis';
                $lead->update(['contact_info' => $contactInfo, 'chat_history' => json_encode($request->chat_history)]);
            }
            return response()->json(['success' => true]);
        }

        if (!$lead) {
            $lead = ChatbotLead::create([
                'client_id' => $client->id,
                'session_id' => $sessionId,
                'user_id' => null, // plugins typically don't share user table
                'ip_address' => $realIp, 'topic_context' => $topic,
                'contact_info' => '-', 'chat_history' => json_encode($request->chat_history ?? []), 'last_message' => $originalMessage
            ]);
        } else {
            $lead->update(['chat_history' => json_encode($request->chat_history ?? []), 'last_message' => $originalMessage]);
        }

        if ($request->is_followup) {
            $lead->update(['contact_info' => $originalMessage]);
            return response()->json([
                'reply' => 'Terima kasih! Tim kami akan segera menindaklanjuti kendala Anda. Sesi chat ini ditutup! 👋',
                'is_finished' => true, 'lead_id' => $lead->id
            ]);
        }

        // =========================================================================
        // 4. RULE-BASED FAST RESPONSE
        // =========================================================================
        
        if (preg_match('/\b(halo|hallo|hai|p|ping|pagi|siang|sore|malam|test|tes)\b/i', $cleanMessage) && str_word_count($cleanMessage) <= 4) {
            return response()->json([
                'reply' => 'Halo Kak! 👋 Ada yang bisa kami bantu?',
                'lead_id' => $lead->id,
                'show_live_chat_btn' => false
            ]);
        }

        if (preg_match('/\b(makasih|terima kasih|terimakasih|thanks|thx|thank you|oke|ok|sip|baik|baiklah)\b/i', $cleanMessage) && str_word_count($cleanMessage) <= 5) {
            return response()->json([
                'reply' => 'Sama-sama Kak! 😊 Apakah ada hal lain yang bisa dibantu?',
                'lead_id' => $lead->id,
                'show_live_chat_btn' => false
            ]);
        }

        // =========================================================================
        // 5. PENYIAPAN KONTEKS & KNOWLEDGE UNTUK AI
        // =========================================================================

        $showLiveChatBtn = false;
        $ollamaUrl = env('OLLAMA_URL', 'http://ollama:11434/api/chat');

        $systemContent = "Kamu adalah asisten virtual (Customer Service) yang ramah dan profesional. Selalu awali dengan sapaan 'Halo Kak'. Jawab dengan bahasa Indonesia yang santai tapi sopan. Jawablah secara singkat, maksimal 2 kalimat.\n\n";

        // Pencarian Knowledge Base dengan Levenshtein (Plugin version)
        $knowledges = ChatbotKnowledge::where('client_id', $client->id)->get();
        $bestMatch = null;
        $highestScore = 0;

        foreach ($knowledges as $k) {
            $keywords = $k->keywords ?? [];
            if (is_string($keywords)) {
                $keywords = json_decode($keywords, true) ?? [];
            }

            $score = 0;
            foreach ($keywords as $kw) {
                $kw = strtolower(trim($kw));
                if (str_contains($message, $kw)) {
                    $score += strlen($kw) * 2; 
                } else {
                    $kwWords = explode(' ', $kw);
                    foreach($kwWords as $kww) {
                        foreach($words as $userWord) {
                            if (strlen($userWord) > 3 && levenshtein($userWord, $kww) <= 1) {
                                $score += 2;
                            }
                        }
                    }
                }
            }
            if ($score > $highestScore) {
                $highestScore = $score;
                $bestMatch = $k;
            }
        }

        if ($bestMatch && $highestScore > 2) {
            $systemContent .= "Berikut adalah INFORMASI (SOP) untuk menjawab pertanyaan user:\n" . $bestMatch->response . "\n\nJawab HANYA berdasarkan informasi di atas. Jika informasi kurang jelas, beritahu user untuk klik tombol Live Chat CS.";
        } else {
            $systemContent .= "Kamu TIDAK TAHU jawaban dari pertanyaan user karena tidak ada di database kamu. Tugasmu adalah meminta maaf dengan sopan, dan wajib mengarahkan user untuk menekan tombol 'Live Chat CS' agar bisa dibantu oleh agen manusia.";
            $showLiveChatBtn = true;
        }

        // =========================================================================
        // 6. BUILD CHAT MESSAGES ARRAY
        // =========================================================================
        $chatMessages = [];
        
        $chatMessages[] = [
            'role' => 'system',
            'content' => $systemContent
        ];

        $chatHistoryArr = json_decode($lead->chat_history, true) ?? [];
        $recentHistory = array_slice($chatHistoryArr, -3); 
        foreach ($recentHistory as $h) {
            $chatMessages[] = [
                'role' => ($h['sender'] === 'user') ? 'user' : 'assistant',
                'content' => $h['text']
            ];
        }

        $chatMessages[] = [
            'role' => 'user',
            'content' => $originalMessage
        ];

        // =========================================================================
        // 7. REQUEST KE OLLAMA AI
        // =========================================================================
        $reply = "";
        try {
            $llmResponse = Http::timeout(40)->post($ollamaUrl, [
                'model' => env('OLLAMA_MODEL', 'gemma2:2b'),
                'messages' => $chatMessages,
                'stream' => false,
                'options' => [
                    'temperature' => 0.1,
                    'top_p' => 0.8,
                    'repeat_penalty' => 1.2
                ]
            ]);

            if ($llmResponse->successful()) {
                $aiText = trim($llmResponse->json('message.content'));
                $aiText = preg_replace('/^(aturan|rules|system|mimin:).*$/im', '', $aiText);
                $aiText = trim($aiText);
                if (!empty($aiText)) {
                    $reply = nl2br($aiText);
                }
            } else {
                throw new \Exception("LLM Error");
            }
        } catch (\Exception $e) {
            $reply = isset($bestMatch) ? "Halo Kak! " . $bestMatch->response : "Halo Kak, koneksi AI sedang sibuk. Ada yang bisa dibantu oleh Tim Live Chat kami?";
            $showLiveChatBtn = true;
        }

        if (empty($reply)) {
            $reply = "Maaf Kak, kami sedang kesulitan memproses jawaban saat ini. Ingin terhubung dengan Admin (Live Chat)?";
            $showLiveChatBtn = true;
        }

        if (preg_match('/(live chat|agen manusia|cs|customer service|admin)/i', $reply)) {
            $showLiveChatBtn = true;
        }

        return response()->json([
            'reply' => $reply,
            'lead_id' => $lead->id,
            'show_live_chat_btn' => $showLiveChatBtn
        ]);
    }

    public function pollLiveChat(Request $request)
    {
        $licenseKey = $request->header('X-FutureCloud-License');
        $client = Client::where('license_key', $licenseKey)->first();

        if (!$client || $client->status !== 'active') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $lead = ChatbotLead::where('client_id', $client->id)->find($request->lead_id);
        
        return response()->json([
            'status' => $lead ? $lead->live_chat_status : 'none',
            'history' => $lead ? json_decode($lead->chat_history) : [],
            'admin_name' => ($lead && $lead->admin_id) ? \App\Models\User::find($lead->admin_id)->name : null
        ]);
    }

    public function sendLiveChatMessage(Request $request)
    {
        $licenseKey = $request->header('X-FutureCloud-License');
        $client = Client::where('license_key', $licenseKey)->first();

        if (!$client || $client->status !== 'active') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $lead = ChatbotLead::where('client_id', $client->id)->find($request->lead_id);
        
        if ($lead) {
            $history = json_decode($lead->chat_history, true) ?? [];
            $history[] = ['sender' => 'user', 'text' => $request->message, 'time' => now()->format('d M, H:i')];
            $lead->update(['chat_history' => json_encode($history)]);
        }
        
        return response()->json(['success' => true]);
    }

    public function requestLiveChat(Request $request)
    {
        $licenseKey = $request->header('X-FutureCloud-License');
        $client = Client::where('license_key', $licenseKey)->first();

        if (!$client || $client->status !== 'active') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $lead = null;
        
        if ($request->lead_id) {
            $lead = ChatbotLead::where('client_id', $client->id)->find($request->lead_id);
        }

        if (!$lead) {
            $realIp = $request->ip();
            if ($request->hasHeader('X-Forwarded-For')) {
                $ips = explode(',', $request->header('X-Forwarded-For'));
                $realIp = trim($ips[0]);
            }

            $lead = ChatbotLead::create([
                'client_id' => $client->id,
                'session_id' => $request->input('session_id'),
                'user_id' => null,
                'ip_address' => $realIp,
                'topic_context' => 'Live Chat',
                'contact_info' => '-',
                'chat_history' => json_encode([]),
                'last_message' => 'Meminta Live Chat',
                'live_chat_status' => 'pending'
            ]);
        } else {
            $lead->update([
                'live_chat_status' => 'pending'
            ]);
        }

        return response()->json([
            'success' => true,
            'lead_id' => $lead->id
        ]);
    }
}

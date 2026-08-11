<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\ChatbotKnowledge;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WhatsAppController extends Controller
{
    /**
     * Webhook secret to verify incoming requests from the Node.js WA server.
     */
    private function verifyWebhook(Request $request): bool
    {
        $secret = $request->input('webhook_secret');
        return $secret === env('WA_WEBHOOK_SECRET', 'futurecloud-wa-secret');
    }

    /**
     * POST /api/whatsapp/incoming
     * Called by the Node.js WhatsApp server when a message is received.
     * Processes the message using the same AI logic as the chatbot.
     */
    public function incoming(Request $request)
    {
        if (!$this->verifyWebhook($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $clientId = $request->input('client_id');
        $from = $request->input('from');  // e.g. "6281234567890@c.us"
        $senderName = $request->input('sender_name', '');
        $message = trim($request->input('message', ''));

        if (empty($message)) {
            return response()->json(['reply' => null]);
        }

        $client = Client::find($clientId);
        if (!$client || $client->status !== 'active') {
            return response()->json(['reply' => 'Maaf, layanan sedang tidak aktif.']);
        }

        // =========================================================================
        // SLANG DICTIONARY (same as ChatbotController)
        // =========================================================================
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
        ];

        // Cleanse message
        $rawMessage = strtolower(trim($message));
        $cleanMessage = preg_replace('/[^\w\s]/', '', $rawMessage);
        $words = explode(' ', $cleanMessage);
        foreach ($words as &$w) {
            if (isset($slangDict[$w])) $w = $slangDict[$w];
        }
        $processedMessage = implode(' ', $words);

        // =========================================================================
        // RULE-BASED FAST RESPONSE
        // =========================================================================
        if (preg_match('/\b(halo|hallo|hai|p|ping|pagi|siang|sore|malam|test|tes)\b/i', $cleanMessage) && str_word_count($cleanMessage) <= 4) {
            return response()->json(['reply' => 'Halo Kak! 👋 Ada yang bisa kami bantu?']);
        }

        if (preg_match('/\b(makasih|terima kasih|terimakasih|thanks|thx|thank you|oke|ok|sip|baik|baiklah)\b/i', $cleanMessage) && str_word_count($cleanMessage) <= 5) {
            return response()->json(['reply' => 'Sama-sama Kak! 😊 Apakah ada hal lain yang bisa dibantu?']);
        }

        // =========================================================================
        // KNOWLEDGE BASE MATCHING (same logic as ChatbotController)
        // =========================================================================
        $showLiveChatBtn = false;
        $ollamaUrl = env('OLLAMA_URL', 'http://ollama:11434/api/chat');

        $systemContent = "Kamu adalah asisten virtual (Customer Service) yang ramah dan profesional melalui WhatsApp. Selalu awali dengan sapaan 'Halo Kak'. Jawab dengan bahasa Indonesia yang santai tapi sopan. Jawablah secara singkat, maksimal 2 kalimat. JANGAN gunakan format HTML apapun, jawab dalam plain text saja.\n\n";

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
                if (str_contains($processedMessage, $kw)) {
                    $score += strlen($kw) * 2;
                } else {
                    $kwWords = explode(' ', $kw);
                    foreach ($kwWords as $kww) {
                        foreach ($words as $userWord) {
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
            $systemContent .= "Berikut adalah INFORMASI (SOP) untuk menjawab pertanyaan user:\n" . $bestMatch->response . "\n\nJawab HANYA berdasarkan informasi di atas. Jika informasi kurang jelas, minta user menghubungi CS secara langsung.";
        } else {
            $systemContent .= "Kamu TIDAK TAHU jawaban dari pertanyaan user karena tidak ada di database kamu. Tugasmu adalah meminta maaf dengan sopan, dan menginformasikan bahwa pertanyaan ini akan diteruskan ke tim CS yang akan menghubungi mereka.";
        }

        // =========================================================================
        // AI REQUEST
        // =========================================================================
        $chatMessages = [
            ['role' => 'system', 'content' => $systemContent],
            ['role' => 'user', 'content' => $message]
        ];

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
                // Strip HTML tags for WhatsApp (plain text only)
                $aiText = strip_tags($aiText);
                $aiText = html_entity_decode($aiText);
                $aiText = trim($aiText);
                if (!empty($aiText)) {
                    $reply = $aiText;
                }
            } else {
                throw new \Exception("LLM Error");
            }
        } catch (\Exception $e) {
            $reply = isset($bestMatch) ? "Halo Kak! " . strip_tags($bestMatch->response) : "Halo Kak, sistem AI sedang sibuk. Tim CS kami akan segera menghubungi Anda.";
        }

        if (empty($reply)) {
            $reply = "Maaf Kak, kami sedang kesulitan memproses jawaban saat ini. Tim CS kami akan menghubungi Anda secepatnya.";
        }

        return response()->json(['reply' => $reply]);
    }

    /**
     * POST /api/whatsapp/status
     * Called by the Node.js WhatsApp server to update connection status.
     */
    public function updateStatus(Request $request)
    {
        if (!$this->verifyWebhook($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $clientId = $request->input('client_id');
        $event = $request->input('event'); // connected | disconnected
        $data = $request->input('data', []);

        $client = Client::find($clientId);
        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        if ($event === 'connected') {
            $client->update([
                'whatsapp_connected' => true,
                'whatsapp_phone' => $data['phone'] ?? null,
                'whatsapp_name' => $data['name'] ?? null,
            ]);
        } elseif ($event === 'disconnected') {
            $client->update([
                'whatsapp_connected' => false,
                'whatsapp_phone' => null,
                'whatsapp_name' => null,
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * POST /api/whatsapp/connect
     * Called from frontend to start a WhatsApp session via the Node.js server.
     */
    public function connect(Request $request)
    {
        $licenseKey = $request->header('X-FutureCloud-License') ?? $request->input('license');
        $client = Client::where('license_key', $licenseKey)->where('status', 'active')->first();

        if (!$client) {
            return response()->json(['error' => 'Invalid license'], 403);
        }

        $waServerUrl = env('WA_SERVER_URL', 'http://localhost:3100');
        $waApiKey = env('WA_API_KEY', 'futurecloud-wa-api-key');

        try {
            $response = Http::withHeaders([
                'X-WA-API-Key' => $waApiKey
            ])->timeout(20)->post("{$waServerUrl}/session/start", [
                'client_id' => $client->id
            ]);

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => 'WhatsApp server not available: ' . $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/whatsapp/session-status
     * Called from frontend to poll session status / QR code.
     */
    public function sessionStatus(Request $request)
    {
        $licenseKey = $request->header('X-FutureCloud-License') ?? $request->input('license');
        $client = Client::where('license_key', $licenseKey)->where('status', 'active')->first();

        if (!$client) {
            return response()->json(['error' => 'Invalid license'], 403);
        }

        $waServerUrl = env('WA_SERVER_URL', 'http://localhost:3100');
        $waApiKey = env('WA_API_KEY', 'futurecloud-wa-api-key');

        try {
            $response = Http::withHeaders([
                'X-WA-API-Key' => $waApiKey
            ])->timeout(10)->get("{$waServerUrl}/session/status", [
                'client_id' => $client->id
            ]);

            $data = $response->json();
            // Also merge DB info
            $data['db_connected'] = $client->whatsapp_connected;
            $data['db_phone'] = $client->whatsapp_phone;
            $data['db_name'] = $client->whatsapp_name;

            return response()->json($data);
        } catch (\Exception $e) {
            // WA server might be down, fall back to DB status
            return response()->json([
                'status' => $client->whatsapp_connected ? 'ready' : 'not_started',
                'qrDataUrl' => null,
                'info' => $client->whatsapp_connected ? [
                    'phone' => $client->whatsapp_phone,
                    'name' => $client->whatsapp_name
                ] : null,
                'db_connected' => $client->whatsapp_connected,
                'wa_server_offline' => true
            ]);
        }
    }

    /**
     * POST /api/whatsapp/disconnect
     * Called from frontend to disconnect WhatsApp.
     */
    public function disconnect(Request $request)
    {
        $licenseKey = $request->header('X-FutureCloud-License') ?? $request->input('license');
        $client = Client::where('license_key', $licenseKey)->where('status', 'active')->first();

        if (!$client) {
            return response()->json(['error' => 'Invalid license'], 403);
        }

        $waServerUrl = env('WA_SERVER_URL', 'http://localhost:3100');
        $waApiKey = env('WA_API_KEY', 'futurecloud-wa-api-key');

        try {
            Http::withHeaders([
                'X-WA-API-Key' => $waApiKey
            ])->timeout(10)->post("{$waServerUrl}/session/stop", [
                'client_id' => $client->id
            ]);
        } catch (\Exception $e) {
            // WA server might be down, just update DB
        }

        $client->update([
            'whatsapp_connected' => false,
            'whatsapp_phone' => null,
            'whatsapp_name' => null,
        ]);

        return response()->json(['success' => true]);
    }
}

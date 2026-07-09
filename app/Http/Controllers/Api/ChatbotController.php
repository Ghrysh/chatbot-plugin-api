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

        // =========================================================================
        // IMPLEMENTASI OLLAMA AI (Porting dari ScanYuk)
        // =========================================================================
        $ollamaUrl = env('OLLAMA_URL', 'http://host.docker.internal:11434/api/chat');
        $systemContent = "Kamu adalah asisten virtual (Customer Service) yang ramah dan profesional. Jawablah dengan bahasa Indonesia yang santai tapi sopan. Jawablah secara singkat, maksimal 2 kalimat.\n\n";

        // Ambil Data Knowledge Base
        $knowledges = ChatbotKnowledge::where('client_id', $client->id)->get();
        $bestMatch = null;
        $highestScore = 0;

        foreach ($knowledges as $k) {
            $keyword = strtolower(trim($k->keyword));
            if (str_contains($message, $keyword)) {
                $score = strlen($keyword);
                if ($score > $highestScore) {
                    $highestScore = $score;
                    $bestMatch = $k;
                }
            }
        }

        if ($bestMatch) {
            $systemContent .= "Berikut adalah INFORMASI (SOP) untuk menjawab pertanyaan user:\n" . $bestMatch->response . "\n\nJawab HANYA berdasarkan informasi di atas. Jika informasi kurang jelas, sarankan user untuk request Live Chat.";
        } else {
            $systemContent .= "Kamu TIDAK TAHU jawaban dari pertanyaan user karena tidak ada di database pengetahuan (SOP) kamu. Tugasmu adalah meminta maaf dengan sopan, dan arahkan user untuk menekan tombol 'Live Chat'.";
        }

        // Build Messages array
        $chatMessages = [
            ['role' => 'system', 'content' => $systemContent],
            ['role' => 'user', 'content' => $request->input('message')]
        ];

        $reply = "";
        try {
            $llmResponse = \Illuminate\Support\Facades\Http::timeout(10)->post($ollamaUrl, [
                'model' => 'gemma2:2b',
                'messages' => $chatMessages,
                'stream' => false
            ]);

            if ($llmResponse->successful()) {
                $aiText = trim($llmResponse->json('message.content'));
                $reply = preg_replace('/^(aturan|rules|system|mimin:).*$/im', '', $aiText);
            } else {
                throw new \Exception("Ollama LLM Error");
            }
        } catch (\Exception $e) {
            $reply = $bestMatch ? $bestMatch->response : "Halo, koneksi AI sedang sibuk. Silakan request Live Chat jika butuh bantuan admin.";
        }

        return response()->json([
            'reply' => $reply,
            'source' => 'ai_bot'
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

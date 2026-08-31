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
                $lead->update(['contact_info' => $contactInfo]);
            }
            return response()->json(['success' => true]);
        }

        if (!$lead) {
            $lead = ChatbotLead::create([
                'client_id' => $client->id,
                'session_id' => $sessionId,
                'user_id' => null, // plugins typically don't share user table
                'ip_address' => $realIp, 'topic_context' => $topic,
                'contact_info' => '-', 
                'chat_history' => json_encode([['sender' => 'user', 'text' => $originalMessage, 'time' => now()->format('d M, H:i')]]), 
                'last_message' => $originalMessage
            ]);
        } else {
            $currentHistory = json_decode($lead->chat_history, true) ?? [];
            $lastMsg = end($currentHistory);
            if (!$lastMsg || $lastMsg['text'] !== $originalMessage || $lastMsg['sender'] !== 'user') {
                $currentHistory[] = ['sender' => 'user', 'text' => $originalMessage, 'time' => now()->format('d M, H:i')];
            }
            $lead->update([
                'chat_history' => json_encode($currentHistory), 
                'last_message' => $originalMessage
            ]);
        }

        // Helper: simpan user message + bot reply ke chat_history DB
        $saveReplyToHistory = function($reply) use ($lead, $originalMessage) {
            $history = json_decode($lead->chat_history, true) ?? [];
            // Tambahkan bot reply
            $history[] = ['sender' => 'bot', 'text' => $reply, 'time' => now()->format('d M, H:i')];
            $lead->update(['chat_history' => json_encode($history)]);
        };

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
            $botReply = 'Halo Kak! 👋 Ada yang bisa kami bantu?';
            $saveReplyToHistory($botReply);
            return response()->json([
                'reply' => $botReply,
                'lead_id' => $lead->id,
                'show_live_chat_btn' => false
            ]);
        }

        if (preg_match('/\b(makasih|terima kasih|terimakasih|thanks|thx|thank you|oke|ok|sip|baik|baiklah)\b/i', $cleanMessage) && str_word_count($cleanMessage) <= 5) {
            $botReply = 'Sama-sama Kak! 😊 Apakah ada hal lain yang bisa dibantu?';
            $saveReplyToHistory($botReply);
            return response()->json([
                'reply' => $botReply,
                'lead_id' => $lead->id,
                'show_live_chat_btn' => false
            ]);
        }

        // =========================================================================
        // 5. PENYIAPAN KONTEKS & KNOWLEDGE UNTUK AI
        // =========================================================================

        $showLiveChatBtn = false;
        $ollamaUrl = env('OLLAMA_URL', 'http://ollama:11434/api/chat');

        $systemContent = "Kamu adalah asisten virtual (Customer Service) yang ramah. Awali jawaban dengan 'Halo Kak'. Jawab dengan bahasa Indonesia santai dan sopan. Jawablah secara singkat maksimal 2 kalimat.\n\n";

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
                        if (strlen($kww) < 3) continue; // skip very short words
                        foreach($words as $userWord) {
                            if (strlen($userWord) >= 3) {
                                // Exact match inside word
                                if (str_contains($userWord, $kww) || str_contains($kww, $userWord)) {
                                    $score += 3;
                                }
                                // Typos
                                elseif (levenshtein($userWord, $kww) <= 1) {
                                    $score += 2;
                                }
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

        if ($bestMatch && $highestScore >= 3) {
            $systemContent .= "INFORMASI UNTUK MENJAWAB:\n" . $bestMatch->response . "\n\nATURAN:\n1. WAJIB jawab menggunakan bahasa Indonesia.\n2. Jawab HANYA berdasarkan informasi di atas. JANGAN MENGARANG.";
        } else {
            $dbDataJson = null;
            if ($client->db_allow_read && !empty($client->db_allowed_tables)) {
                $dbDataJson = $this->queryDatabaseWithAi($client, $originalMessage);
                \Illuminate\Support\Facades\Log::info("DEBUG DB DATA JSON: " . $dbDataJson);
            }

            if ($dbDataJson && $dbDataJson !== '[]' && stripos($dbDataJson, 'ERROR') !== 0) {
                 $dbContextForUser = true;
                 $showLiveChatBtn = false;
            } else {
                 // OPTIMIZATION: Skip AI generation completely if we don't have any data! 
                 // Saves CPU/API costs and guarantees 0% hallucination.
                 return response()->json([
                     'reply' => "Halo Kak! Maaf sekali, untuk saat ini aku belum punya informasi terkait hal tersebut.\n\nSilakan klik tombol 'Live Chat CS' di bawah agar Kakak bisa langsung dibantu oleh agen manusia kami ya! 🙏",
                     'show_live_chat' => true
                 ]);
            }
        }

        // =========================================================================
        // 6. BUILD CHAT MESSAGES ARRAY
        // =========================================================================
        $chatMessages = [];
        
        $chatMessages[] = [
            'role' => 'system',
            'content' => $systemContent
        ];
        
        if ($lead && $lead->chat_history) {
            $history = json_decode($lead->chat_history, true) ?? [];
            // Limit history to last 5 messages
            $history = array_slice($history, -5);
            foreach($history as $index => $msg) {
                $role = $msg['sender'] === 'user' ? 'user' : 'assistant';
                $chatMessages[] = ['role' => $role, 'content' => $msg['text']];
            }
        } else {
            $chatMessages[] = [
                'role' => 'user',
                'content' => $originalMessage
            ];
        }

        // PUSH DB CONTEXT AS A SEPARATE SYSTEM MESSAGE AT THE VERY END
        if (isset($dbContextForUser)) {
            $chatMessages[] = [
                'role' => 'system',
                'content' => "=== INFORMASI DATABASE ===\n" . $dbDataJson . "\n\nINSTRUKSI: Jawab pertanyaan user terakhir HANYA berdasarkan data di atas secara natural. Tulis ANGKA PERSIS sesuai aslinya, JANGAN ditambah/dikurang dan JANGAN diubah (misal 50000 jangan diubah jadi 500.000). Jika user meminta daftar/list seluruh item, sebutkan semua data di atas. Jawab secara profesional, singkat, dan DILARANG menggunakan bahasa gaul!"
            ];
        }

        // =========================================================================
        // 7. REQUEST KE OLLAMA / API AI
        // =========================================================================
        $reply = "";
        $apiUrl = env('AI_API_URL', 'https://api.moonshot.cn/v1/chat/completions');
        $apiKey = env('AI_API_KEY', '');
        try {
            $req = Http::timeout(300);
            if ($apiKey) {
                $req = $req->withToken($apiKey);
            }
            $llmResponse = $req->post($apiUrl, [
                'model' => env('AI_MODEL', 'moonshot-v1-8k'),
                'messages' => $chatMessages,
                'stream' => false,
                'max_tokens' => 150, // Batasi panjang teks untuk AI lokal
                'options' => [
                    'temperature' => 0.1,
                    'top_p' => 0.85,
                    'repeat_penalty' => 1.2
                ] // options mostly for ollama, but harmless for openai (ignored if unsupported)
            ]);

            if ($llmResponse->successful()) {
                $aiText = trim($llmResponse->json('choices.0.message.content') ?? $llmResponse->json('message.content', ''));
                $aiText = preg_replace('/^(aturan|rules|system|mimin:).*$/im', '', $aiText);
                $aiText = trim($aiText);
                if (!empty($aiText)) {
                    $reply = nl2br($aiText);
                }
            } else {
                throw new \Exception("LLM Error: " . $llmResponse->status());
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("AI API Error: " . $e->getMessage());
            $reply = "DEBUG ERROR AI: " . $e->getMessage();
            $showLiveChatBtn = true;
        }

        if (empty($reply)) {
            $reply = "Maaf Kak, kami sedang kesulitan memproses jawaban saat ini. Ingin terhubung dengan Admin (Live Chat)?";
            $showLiveChatBtn = true;
        }

        if (preg_match('/(live chat|agen manusia|cs|customer service|admin)/i', $reply)) {
            $showLiveChatBtn = true;
        }

        $saveReplyToHistory($reply);

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

        $lead = ChatbotLead::where('client_id', $client->id)->findOrFail($request->lead_id);
        
        $history = json_decode($lead->chat_history, true) ?? [];
        $history[] = [
            'sender' => 'user',
            'text' => $request->message,
            'time' => now()->format('d M, H:i')
        ];
        
        $lead->chat_history = json_encode($history);
        $lead->last_message = $request->message;
        $lead->save();

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

    private function queryDatabaseWithAi(\App\Models\Client $client, string $message)
    {
        // 1. Setup connection
        // Auto-detect driver (support MySQL, PgSQL, SQL Server)
        $driver = 'mysql';
        if (in_array($client->db_port, [5432, 5433])) {
            $driver = 'pgsql';
        } elseif ($client->db_port == 1433) {
            $driver = 'sqlsrv';
        }
        
        $config = [
            'driver' => $driver,
            'host' => $client->db_host,
            'port' => $client->db_port,
            'database' => $client->db_database,
            'username' => $client->db_username,
            'password' => $client->db_password,
        ];
        
        if ($driver === 'mysql') {
            $config['charset'] = 'utf8mb4';
            $config['collation'] = 'utf8mb4_unicode_ci';
        } elseif ($driver === 'pgsql') {
            $config['charset'] = 'utf8';
        }

        config(['database.connections.client_db_ai' => $config]);
        
        \Illuminate\Support\Facades\DB::purge('client_db_ai');
        
        // 2. Fetch schema for allowed tables (Agnostic to MySQL/PgSQL/SQLServer)
        $schemaText = "";
        try {
            foreach ($client->db_allowed_tables as $table) {
                $columns = \Illuminate\Support\Facades\Schema::connection('client_db_ai')->getColumns($table);
                
                $colDetails = [];
                foreach ($columns as $col) {
                    $colDetails[] = $col['name'] . " (" . $col['type_name'] . ")";
                }
                $schemaText .= "Table: $table
Columns: " . implode(", ", $colDetails) . "

";
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Schema Error: " . $e->getMessage());
            return "ERROR: Gagal membaca struktur database.";
        }

        // 3. Ask AI to generate SQL
        $apiUrl = env('AI_API_URL', 'https://api.moonshot.cn/v1/chat/completions');
        $model = env('AI_MODEL', 'moonshot-v1-8k');
        $apiKey = env('AI_API_KEY', '');

        $promptSql = "You are a strict SQL generator. Based on this database schema:

$schemaText

User Question: '$message'

Write ONLY a valid $driver SELECT query. 
YOU MUST OBEY THESE RULES OR THE SYSTEM WILL CRASH:
1. Output ONLY the raw SQL. No markdown, no \`\`\`sql.
2. YOU MUST USE \`SELECT *\`. DO NOT select specific columns like \`SELECT price\`.
3. If the user asks for ALL items, DO NOT use a WHERE clause. If they ask for a specific item, use \`LOWER(column) LIKE '%keyword%'\`. NEVER use \`=\` for strings.

EXAMPLES (Adapt to the provided schema!):
User: 'what items do you have / list all items / ada apa saja' -> SELECT * FROM [your_table_name];
User: 'how much is [specific_item]' -> SELECT * FROM [your_table_name] WHERE LOWER([item_column]) LIKE '%[specific_item]%';";

        $sqlQuery = "";
        try {
            $req = \Illuminate\Support\Facades\Http::timeout(300);
            if ($apiKey) {
                $req = $req->withToken($apiKey);
            }
            $response = $req->post($apiUrl, [
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $promptSql]],
                'stream' => false,
                'max_tokens' => 100, // Cegah halusinasi kepanjangan
                'temperature' => 0.0, // Sangat deterministik
            ]);
            
            if ($response->successful()) {
                $sqlQuery = trim($response->json('choices.0.message.content') ?? $response->json('message.content', ''));
                $sqlQuery = str_replace(['```sql', '```mysql', '```'], '', $sqlQuery);
                $sqlQuery = trim($sqlQuery);
                \Illuminate\Support\Facades\Log::info("RAW SQL GENERATED: " . $sqlQuery);
            } else {
                return "ERROR: LLM API gagal (" . $response->status() . ").";
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LLM Error: " . $e->getMessage());
            return "ERROR: Gagal menghubungi AI untuk generate SQL.";
        }

        // 4. Validasi minimal
        if (empty($sqlQuery) || stripos($sqlQuery, 'SELECT') !== 0) {
            return "ERROR: Query bukan SELECT yang valid.";
        }
        
        // 4. Execute SQL
        try {
            $results = \Illuminate\Support\Facades\DB::connection('client_db_ai')->select($sqlQuery);
            $resultsArray = array_map(function($row) { return (array)$row; }, $results);
            
            if (empty($resultsArray)) {
                return "[]";
            }
            
            $textOutput = "";
            foreach ($resultsArray as $idx => $row) {
                if ($idx >= 5) {
                    $textOutput .= "... dan data lainnya.\n";
                    break;
                }
                $rowStrings = [];
                foreach ($row as $key => $val) {
                    $rowStrings[] = "$key: $val";
                }
                $textOutput .= "- " . implode(', ', $rowStrings) . "\n";
            }
            return trim($textOutput);
        } catch (\Exception $e) {
            return "ERROR: Gagal menjalankan query database.";
        }
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatbotLead;
use App\Models\ChatbotKnowledge;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
class ChatbotApiController extends Controller
{
    /**
     * Helper to get the first client ID.
     * In a real SaaS, the client ID would be identified via an API Key or Origin header.
     */
    private function getClientId(Request $request)
    {
        $licenseKey = $request->header('X-FutureCloud-License');
        if (!$licenseKey) return null;

        $client = \App\Models\Client::where('license_key', $licenseKey)
            ->where('status', 'active')
            ->first();
            
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
                'chat_history' => json_encode([
                    ['sender' => 'user', 'text' => $message, 'time' => now()->format('d M, H:i')]
                ]),
                'live_chat_status' => 'none',
                'contact_info' => '-'
            ]);
        } else {
            $currentHistory = json_decode($lead->chat_history, true) ?? [];
            $currentHistory[] = ['sender' => 'user', 'text' => $message, 'time' => now()->format('d M, H:i')];
            $lead->chat_history = json_encode($currentHistory);
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
            // Coba tanya ke Database via AI Text-to-SQL
            $client = \App\Models\Client::find($clientId);
            if ($client && $client->db_allow_read && !empty($client->db_allowed_tables)) {
                $dbReply = $this->queryDatabaseWithAi($client, $message);
                // Jika tidak ada error (string 'Maaf,'), maka gunakan jawaban dari DB
                if ($dbReply && stripos($dbReply, 'Maaf,') !== 0) {
                    $reply = $dbReply;
                    $matchedTopic = 'Database Query';
                }
            }

            if (!$reply) {
                $reply = "Maaf, Bot belum mengerti pertanyaan Anda. Apakah Anda ingin berbicara langsung dengan tim Support/CS kami?";
                $lead->topic_context = 'Unrecognized: ' . Str::limit($message, 30);
                
                $history = json_decode($lead->chat_history, true) ?? [];
                $history[] = ['sender' => 'bot', 'text' => $reply, 'time' => now()->format('d M, H:i')];
                $lead->chat_history = json_encode($history);
                
                $lead->save();
                return response()->json([
                    'reply' => $reply,
                    'show_live_chat_btn' => true,
                    'lead_id' => $lead->id
                ]);
            }
        }

        $lead->topic_context = $matchedTopic;
        
        $history = json_decode($lead->chat_history, true) ?? [];
        $history[] = ['sender' => 'bot', 'text' => $reply, 'time' => now()->format('d M, H:i')];
        $lead->chat_history = json_encode($history);
        
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

    public function pollLiveChat(int $lead_id)
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
        if ($request->input('is_autoclose')) {
            $lead->live_chat_status = 'ended';
            $contactInfo = 'Diakhiri Otomatis';
            $lead->contact_info = $contactInfo;
            $lead->save();
            return response()->json(['success' => true]);
        }
        
        $history[] = [
            'sender' => 'user',
            'text' => $request->input('message'),
            'time' => now()->format('d M, H:i')
        ];
        
        $lead->chat_history = json_encode($history);
        $lead->save();

        return response()->json(['success' => true]);
    }

    private function queryDatabaseWithAi(\App\Models\Client $client, string $message)
    {
        // 1. Setup connection
        $driver = $client->db_driver ?? 'mysql';
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
        
        DB::purge('client_db_ai');
        
        // 2. Fetch schema for allowed tables
        $schemaText = "";
        try {
            foreach ($client->db_allowed_tables as $table) {
                // To save prompt tokens, we just get columns
                $columns = DB::connection('client_db_ai')->select("SHOW COLUMNS FROM `$table`");
                $colDetails = [];
                foreach ($columns as $col) {
                    $colDetails[] = $col->Field . " (" . $col->Type . ")";
                }
                $schemaText .= "Table: $table\nColumns: " . implode(", ", $colDetails) . "\n\n";
            }
        } catch (\Exception $e) {
            return "Maaf, terjadi kesalahan saat membaca struktur database klien.";
        }

        // 3. Ask AI to generate SQL
        $ollamaUrl = env('OLLAMA_URL', 'http://127.0.0.1:11434/api/chat');
        $model = env('OLLAMA_MODEL', 'kimi-k3-in-c'); // Updated to use the requested lightweight model

        $promptSql = "You are an expert SQL generator. Based on this MySQL schema:\n\n$schemaText\n\nUser Question: '$message'\n\nWrite ONLY a valid MySQL SELECT query to answer this. Do NOT add markdown, explanations, or any text other than the SQL query. If it requires counting, use COUNT(). If it requires multiple tables, use JOIN. ALWAYS use `SELECT` only. Return exactly the SQL string.";

        $sqlQuery = "";
        try {
            $response = Http::timeout(30)->post($ollamaUrl, [
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $promptSql]],
                'stream' => false,
            ]);
            
            if ($response->successful()) {
                $sqlQuery = trim($response->json('message.content', ''));
                $sqlQuery = str_replace(['```sql', '```mysql', '```'], '', $sqlQuery);
                $sqlQuery = trim($sqlQuery);
            } else {
                return "Maaf, layanan AI sedang sibuk (Generate SQL gagal).";
            }
        } catch (\Exception $e) {
             return "Maaf, terjadi kesalahan komunikasi dengan server AI.";
        }
        
        if (empty($sqlQuery) || stripos($sqlQuery, 'SELECT') !== 0) {
            return "Maaf, AI tidak dapat membuat query yang aman untuk pertanyaan ini.";
        }
        
        // 4. Execute SQL
        try {
            $results = DB::connection('client_db_ai')->select($sqlQuery);
            $resultsArray = array_map(function($row) { return (array)$row; }, $results);
            
            // As per user request, we do not strictly limit to 10-20. We will pass all results.
            // But we must encode it to JSON to feed back to AI.
            $dataJson = json_encode($resultsArray);
        } catch (\Exception $e) {
            return "Maaf, query database gagal dijalankan.";
        }

        // 5. Ask AI to generate natural language response
        $promptAnswer = "You are a helpful AI assistant. User Question: '$message'\n\nData from database: $dataJson\n\nPlease formulate a natural, polite, and helpful answer in Indonesian based on the data. Do NOT mention the SQL query or database structure. Just answer the user's question directly.";

        try {
            $response = Http::timeout(45)->post($ollamaUrl, [
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $promptAnswer]],
                'stream' => false,
            ]);
            if ($response->successful()) {
                return trim($response->json('message.content', ''));
            }
        } catch (\Exception $e) {
             return "Maaf, terjadi kesalahan saat merumuskan jawaban.";
        }
        
        return "Maaf, bot tidak dapat memberikan jawaban dari database saat ini.";
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatbotKnowledge;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;
use ZipArchive;

class KnowledgeController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'keywords' => 'required|string',
            'response' => 'required|string',
        ]);

        // Convert comma separated string to array
        $keywordsArray = array_map('trim', explode(',', $request->keywords));

        // For this SaaS version, we assume the admin's currently selected client, 
        // but since we haven't built the full multi-tenant auth yet, we'll hardcode client_id 1 or get first.
        $client = $request->has('license') ? \App\Models\Client::where('license_key', $request->license)->first() : \App\Models\Client::first();
        if (!$client) {
            $url = url()->previous();
        if (!str_contains($url, 'tab=')) {
            $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'tab=knowledge';
        }
        return redirect($url)->with('error', 'No client found. Please setup database.');
        }

        ChatbotKnowledge::create([
            'client_id' => $client->id,
            'topic' => 'General',
            'keywords' => $keywordsArray,
            'response' => $request->response
        ]);

        $url = url()->previous();
        if (!str_contains($url, 'tab=')) {
            $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'tab=knowledge';
        }
        return redirect($url)->with('success', 'Knowledge base added successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'keywords' => 'required|string',
            'response' => 'required|string',
        ]);

        $knowledge = ChatbotKnowledge::findOrFail($id);
        
        $keywordsArray = array_map('trim', explode(',', $request->keywords));
        
        $knowledge->update([
            'keywords' => $keywordsArray,
            'response' => $request->response
        ]);

        $url = url()->previous();
        if (!str_contains($url, 'tab=')) {
            $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'tab=knowledge';
        }
        return redirect($url)->with('success', 'Knowledge base updated successfully.');
    }

    public function destroy($id)
    {
        $knowledge = ChatbotKnowledge::findOrFail($id);
        $knowledge->delete();

        $url = url()->previous();
        if (!str_contains($url, 'tab=')) {
            $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'tab=knowledge';
        }
        return redirect($url)->with('success', 'Knowledge base deleted successfully.');
    }

    public function generate(Request $request)
    {
        set_time_limit(900); // 5 minutes max execution time
        $request->validate([
            'document' => 'nullable|file|mimes:pdf,docx|max:5120',
            'raw_text' => 'nullable|string',
        ]);

        $text = '';

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $ext = $file->getClientOriginalExtension();

            if ($ext === 'pdf') {
                $parser = new PdfParser();
                $pdf = $parser->parseFile($file->getPathname());
                $text = $pdf->getText();
            } elseif ($ext === 'docx') {
                $zip = new ZipArchive;
                if ($zip->open($file->getPathname()) === true) {
                    $xml = $zip->getFromName('word/document.xml');
                    $zip->close();
                    if ($xml !== false) {
                        $text = strip_tags($xml);
                    }
                }
            }
        } elseif ($request->filled('raw_text')) {
            $text = $request->raw_text;
        }

        $text = trim($text);

        if (empty($text)) {
            return back()->with('error', 'Teks atau dokumen kosong, tidak dapat digenerate.');
        }

        // Prompt Ollama
        $prompt = "Anda adalah AI Asisten Pembuat Standar Operasional (SOP) dan Knowledge Base untuk Chatbot Customer Service.
Tugas Anda adalah merangkum teks berikut menjadi JSON murni yang terstruktur.

Ekstrak HANYA informasi terpenting dan kembalikan array JSON berisi object dengan struktur berikut:
- \"topic\": (string, Kategori/Topik. Contoh: 'Akun & Login', 'Layanan')
- \"keywords\": (array of string, hasilkan 5-8 kata kunci atau pertanyaan terkait. Contoh: ['lupa password', 'sandi', 'tidak bisa masuk'])
- \"response\": (string, BALASAN BOT. Rangkai ulang intisari teks menjadi jawaban yang jelas, ramah, dan solutif. Maksimal 3-4 kalimat padat.)

Hasilkan 3-5 topik utama yang paling relevan.
PENTING: Hanya kembalikan array JSON valid, tanpa markdown, tanpa teks awalan/akhiran.
Teks: " . substr($text, 0, 8000);

        $ollamaUrl = env('OLLAMA_URL', 'http://127.0.0.1:11434/api/chat'); // Fallback to localhost if host is missing
        $model = env('OLLAMA_MODEL', 'gemma2:2b');

        \Log::info("Starting Ollama request to {$ollamaUrl} with model {$model}. Prompt length: " . strlen($prompt));

        $client = $request->has('license') ? \App\Models\Client::where('license_key', $request->license)->first() : \App\Models\Client::first();
        $clientId = $client ? $client->id : 1;
        
        // Mencegah penumpukan proses AI yang akan membuat VPS crash (OOM / 100% CPU)
        if (\Illuminate\Support\Facades\Cache::get('ai_job_client_' . $clientId) === 'processing') {
            return back()->with('error', 'Sistem AI saat ini sedang memproses dokumen Anda. Harap tunggu hingga selesai sebelum memulai tugas baru.');
        }
        
        \Illuminate\Support\Facades\Cache::put('ai_job_client_' . $clientId, 'processing', 3600);

        // Mencegah PHP menghentikan script di tengah jalan
        set_time_limit(0);
        ignore_user_abort(true);

        // Mencegah Nginx 504 Timeout dengan merespons lebih awal dan membiarkan proses berjalan di background
        if (function_exists('fastcgi_finish_request')) {
            session()->flash('success', 'Sistem AI sedang mengekstrak dokumen Anda di latar belakang. Proses ini memakan waktu 5-10 menit. Anda dapat menutup halaman ini dan kembali nanti, hasilnya akan otomatis ditambahkan ke daftar.');
            session()->save();
            
            // Redirect user back immediately to prevent white screen
            header("Location: " . url()->previous(), true, 302);
            fastcgi_finish_request();
        }

        try {
            // Membatasi penggunaan CPU Ollama agar tidak membuat server VPS hang (ERR_TIMED_OUT)
            $response = Http::timeout(1800)->post($ollamaUrl, [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Return ONLY valid JSON array.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'options' => [
                    'num_thread' => 1
                ],
                'stream' => false,
            ]);

            \Log::info("Ollama responded with status: " . $response->status());

            if ($response->successful()) {
                $content = $response->json('message.content');
                \Log::info("Ollama success! Content length: " . strlen($content));
                
                // Clean markdown from response
                $cleanJson = preg_replace('/```json\s*(.*?)\s*```/is', '$1', $content);
                $cleanJson = trim($cleanJson);
                
                \Log::info("Ollama cleaned JSON (first 500 chars): " . substr($cleanJson, 0, 500));
                
                $faqs = json_decode($cleanJson, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    \Illuminate\Support\Facades\Cache::put('ai_job_client_' . $clientId, 'failed', 3600);
                    \Log::error('Ollama JSON Error: ' . json_last_error_msg() . ' Raw (first 1000 chars): ' . substr($content, 0, 1000));
                    return back()->with('error', 'Gagal memparsing JSON dari AI.');
                }

                if (\Illuminate\Support\Facades\Cache::get('ai_job_client_' . $clientId) === 'cancelled') {
                    \Log::info("Ollama AI job cancelled by user.");
                    return response()->json(['status' => 'cancelled']);
                }

                \Log::info("Ollama parsed FAQs count: " . (is_array($faqs) ? count($faqs) : 'NOT_ARRAY'));

                if (is_array($faqs) && count($faqs) > 0) {
                    $added = 0;
                    foreach ($faqs as $idx => $faq) {
                        if (isset($faq['topic'], $faq['keywords'], $faq['response'])) {
                            ChatbotKnowledge::create([
                                'client_id' => $clientId,
                                'topic' => $faq['topic'] ?? 'Umum',
                                'keywords' => is_array($faq['keywords']) ? $faq['keywords'] : explode(',', $faq['keywords']),
                                'response' => $faq['response']
                            ]);
                            $added++;
                        } else {
                            \Log::warning("Ollama FAQ item #$idx missing required keys. Keys present: " . implode(', ', array_keys($faq)));
                        }
                    }

                    \Log::info("Ollama AI job completed. Added $added knowledge items for client $clientId.");
                    \Illuminate\Support\Facades\Cache::put('ai_job_client_' . $clientId, 'completed', 3600);
                    $url = url()->previous();
                    if (!str_contains($url, 'tab=')) {
                        $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'tab=knowledge';
                    }
                    return redirect($url)->with('success', "Berhasil menambahkan $added pengetahuan baru dari dokumen.");
                } else {
                    \Illuminate\Support\Facades\Cache::put('ai_job_client_' . $clientId, 'failed', 3600);
                    \Log::error('Ollama returned empty or non-array data. Type: ' . gettype($faqs) . ' Raw (first 500 chars): ' . substr($cleanJson, 0, 500));
                    return back()->with('error', 'Tidak ada data valid yang dihasilkan AI.');
                }
            } else {
                \Illuminate\Support\Facades\Cache::put('ai_job_client_' . $clientId, 'failed', 3600);
                return back()->with('error', 'Koneksi ke server AI Ollama gagal.');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Cache::put('ai_job_client_' . $clientId, 'failed', 3600);
            \Log::error('Ollama Error: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memproses data AI.');
        }
    }

    public function jobStatus(Request $request)
    {
        $client = $request->has('license') ? \App\Models\Client::where('license_key', $request->license)->first() : \App\Models\Client::first();
        $clientId = $client ? $client->id : 1;
        $status = \Illuminate\Support\Facades\Cache::get('ai_job_client_' . $clientId);
        return response()->json(['status' => $status]);
    }

    public function jobCancel(Request $request)
    {
        $client = $request->has('license') ? \App\Models\Client::where('license_key', $request->license)->first() : \App\Models\Client::first();
        $clientId = $client ? $client->id : 1;
        \Illuminate\Support\Facades\Cache::put('ai_job_client_' . $clientId, 'cancelled', 3600);
        return response()->json(['success' => true]);
    }
}

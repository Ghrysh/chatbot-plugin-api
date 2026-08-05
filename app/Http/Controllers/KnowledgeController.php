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

        $ollamaUrl = env('OLLAMA_URL', 'http://ollama:11434/api/chat');
        $model = env('OLLAMA_MODEL', 'gemma2:2b');

        try {
            $response = Http::timeout(900)->post($ollamaUrl, [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Return ONLY valid JSON array.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'stream' => false,
            ]);

            if ($response->successful()) {
                $content = $response->json('message.content');
                
                // Clean up possible markdown code blocks
                $content = preg_replace('/```json/i', '', $content);
                $content = preg_replace('/```/i', '', $content);
                $content = trim($content);
                
                $faqs = json_decode($content, true);

                if (is_array($faqs) && count($faqs) > 0) {
                    $client = $request->has('license') ? \App\Models\Client::where('license_key', $request->license)->first() : \App\Models\Client::first();
                    $clientId = $client ? $client->id : 1;

                    $added = 0;
                    foreach ($faqs as $faq) {
                        if (isset($faq['topic'], $faq['keywords'], $faq['response'])) {
                            // In this API version, intent_name is not in the DB, it uses 'topic'. Let's check DB schema.
                            // Actually, earlier we saw ChatbotKnowledge has topic, keywords (array), response.
                            ChatbotKnowledge::create([
                                'client_id' => $clientId,
                                'topic' => $faq['topic'] ?? 'Umum',
                                'keywords' => is_array($faq['keywords']) ? $faq['keywords'] : explode(',', $faq['keywords']),
                                'response' => $faq['response']
                            ]);
                            $added++;
                        }
                    }
                    
                    $url = url()->previous();
                    if (!str_contains($url, 'tab=')) {
                        $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'tab=knowledge';
                    }
                    return redirect($url)->with('success', "Berhasil menambahkan $added pengetahuan baru dari dokumen.");
                } else {
                    return back()->with('error', 'AI gagal menghasilkan format data yang valid.');
                }
            } else {
                return back()->with('error', 'Koneksi ke server AI Ollama gagal.');
            }
        } catch (\Exception $e) {
            Log::error('Ollama Error: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memproses data AI.');
        }
    }
}

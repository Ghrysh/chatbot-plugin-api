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

    public function destroyAll(Request $request)
    {
        $client = $request->has('license') ? \App\Models\Client::where('license_key', $request->license)->first() : \App\Models\Client::first();
        $clientId = $client ? $client->id : 1;
        
        $count = ChatbotKnowledge::where('client_id', $clientId)->count();
        ChatbotKnowledge::where('client_id', $clientId)->delete();

        $url = url()->previous();
        if (!str_contains($url, 'tab=')) {
            $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'tab=knowledge';
        }
        return redirect($url)->with('success', "Berhasil menghapus {$count} pengetahuan bot.");
    }

    public function generate(Request $request)
    {
        set_time_limit(900);

        // Deteksi jika PHP sudah menolak upload sebelum Laravel sempat memproses
        if ($request->isMethod('post') && empty($_FILES) && empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
            $maxSize = ini_get('upload_max_filesize');
            return back()->with('error', "File terlalu besar! Hanya mengizinkan upload maksimal {$maxSize}. Silakan kompres file Anda terlebih dahulu.");
        }

        try {
            $request->validate([
                'document' => 'nullable|file|mimes:pdf,docx|max:20480',
                'raw_text' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', 'Validasi gagal: ' . implode(', ', $e->validator->errors()->all()));
        }

        $text = '';
        $totalPages = 1;

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $ext = $file->getClientOriginalExtension();
            \Log::info('Upload file: ' . $file->getClientOriginalName() . ', size: ' . round($file->getSize() / 1024 / 1024, 2) . 'MB, ext: ' . $ext);

            if ($ext === 'pdf') {
                $parser = new PdfParser();
                $pdf = $parser->parseFile($file->getPathname());
                $text = $pdf->getText();
                $totalPages = count($pdf->getPages());
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
        if ($totalPages === 1 && strlen($text) > 2000) {
            $totalPages = ceil(strlen($text) / 2000); // Estimasi kasar halaman untuk DOCX/Teks
        }

        $text = trim($text);
        \Log::info('Extracted text length: ' . strlen($text) . ' chars from document');

        if (empty($text)) {
            return back()->with('error', 'Teks atau dokumen kosong, tidak dapat digenerate. Pastikan file PDF Anda mengandung teks (bukan scan/gambar).');
        }

        // Membagi teks menjadi potongan-potongan (chunks) agar dokumen besar bisa diproses seluruhnya
        // Gunakan mb_ (multibyte) string functions agar karakter UTF-8 tidak terpotong di tengah jalan
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $chunkSize = 6000; // ~3-4 halaman per chunk
        $chunks = [];
        $textLength = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $textLength; $i += $chunkSize) {
            $chunks[] = mb_substr($text, $i, $chunkSize, 'UTF-8');
        }
        $totalChunks = count($chunks);
        \Log::info("Document split into {$totalChunks} chunks (text length: {$textLength} chars)");

        $ollamaUrl = env('OLLAMA_URL', 'http://127.0.0.1:11434/api/chat');
        $model = env('OLLAMA_MODEL', 'gemma2:2b');

        $client = $request->has('license') ? \App\Models\Client::where('license_key', $request->license)->first() : \App\Models\Client::first();
        $clientId = $client ? $client->id : 1;
        
        // Mencegah penumpukan proses AI yang akan membuat VPS crash (OOM / 100% CPU)
        if (\Illuminate\Support\Facades\Cache::get('ai_job_client_' . $clientId) === 'processing') {
            return back()->with('error', 'Sistem AI saat ini sedang memproses dokumen Anda. Harap tunggu hingga selesai sebelum memulai tugas baru.');
        }
        
        \Illuminate\Support\Facades\Cache::put('ai_job_client_' . $clientId, 'processing', 7200);

        // Mencegah PHP menghentikan script di tengah jalan
        set_time_limit(0);
        ignore_user_abort(true);

        // Jalankan ekstraksi AI secara asinkron di belakang layar setelah response dikembalikan ke user
        app()->terminating(function () use ($chunks, $totalChunks, $totalPages, $clientId, $ollamaUrl, $model) {
            $totalAdded = 0;
            $batchIds = [];

            try {
            foreach ($chunks as $chunkIndex => $chunk) {
                $chunkNum = $chunkIndex + 1;
                // Simpan progress aktual ke cache agar frontend bisa menampilkannya dengan akurat
                $currentPage = round(($chunkNum / $totalChunks) * $totalPages);
                $progressPercentage = round(($chunkNum / $totalChunks) * 100);
                \Illuminate\Support\Facades\Cache::put('ai_job_progress_' . $clientId, [
                    'percentage' => $progressPercentage,
                    'current_page' => $currentPage,
                    'total_pages' => $totalPages
                ], 7200);

                \Log::info("Processing chunk {$chunkNum}/{$totalChunks} (length: " . strlen($chunk) . " chars)");

                // Cek apakah user sudah membatalkan
                if (\Illuminate\Support\Facades\Cache::get('ai_job_client_' . $clientId) === 'cancelled') {
                    \Log::info("AI job cancelled by user at chunk {$chunkNum}/{$totalChunks}.");
                    return;
                }

                $prompt = "Tugas Anda adalah merangkum teks berikut HANYA DALAM FORMAT JSON ARRAY. JANGAN berikan teks pengantar atau penutup apa pun.

STRUKTUR WAJIB JSON (Hasilkan sebanyak mungkin object dalam array):
[
  {
    \"topic\": \"Judul/Kategori Topik\",
    \"keywords\": [\"kata kunci 1\", \"kata kunci 2\", \"pertanyaan terkait\"],
    \"response\": \"Jawaban atau penjelasan rinci yang ramah dan solutif (maks 3 kalimat).\"
  }
]

ATURAN:
1. Ekstrak sebanyak mungkin topik yang relevan dari teks.
2. Seluruh teks harus dalam Bahasa Indonesia.
3. OUTPUT HARUS VALID JSON ARRAY! Jangan berikan markdown ```json.

Teks (bagian {$chunkNum} dari {$totalChunks}): " . $chunk;

                $response = Http::timeout(1800)->post($ollamaUrl, [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a Knowledge Base Extraction AI. You MUST reply ONLY with a valid JSON array of objects. Do not wrap in markdown tags.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'options' => [
                        'num_thread' => 1
                    ],
                    'stream' => false,
                ]);

                \Log::info("Ollama chunk {$chunkNum}/{$totalChunks} responded with status: " . $response->status());

                if ($response->successful()) {
                    $content = $response->json('message.content');
                    \Log::info("Ollama chunk {$chunkNum} content length: " . strlen($content));
                    
                    $cleanJson = preg_replace('/```json\s*(.*?)\s*```/is', '$1', $content);
                    $cleanJson = trim($cleanJson);
                    
                    $faqs = json_decode($cleanJson, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        \Log::warning("Ollama chunk {$chunkNum} JSON Error: " . json_last_error_msg() . ' — skipping chunk');
                        continue; // Lewati chunk ini, lanjutkan ke chunk berikutnya
                    }

                    if (is_array($faqs) && count($faqs) > 0) {
                        foreach ($faqs as $idx => $faq) {
                            if (isset($faq['topic'], $faq['keywords'], $faq['response'])) {
                                $item = ChatbotKnowledge::create([
                                    'client_id' => $clientId,
                                    'topic' => $faq['topic'] ?? 'Umum',
                                    'keywords' => is_array($faq['keywords']) ? $faq['keywords'] : explode(',', $faq['keywords']),
                                    'response' => $faq['response']
                                ]);
                                $batchIds[] = $item->id;
                                $totalAdded++;
                            } else {
                                \Log::warning("Chunk {$chunkNum} FAQ item #{$idx} missing keys: " . implode(', ', array_keys($faq)));
                            }
                        }
                        \Log::info("Chunk {$chunkNum} done. Added " . count($faqs) . " items. Running total: {$totalAdded}");
                    } else {
                        \Log::warning("Chunk {$chunkNum} returned empty/non-array data.");
                    }
                } else {
                    \Log::error("Ollama chunk {$chunkNum} HTTP error: " . $response->status());
                    // Lanjutkan ke chunk berikutnya, jangan hentikan seluruh proses
                    continue;
                }
            }

            if ($totalAdded > 0) {
                \Log::info("All chunks processed. Total added: {$totalAdded} knowledge items for client {$clientId}.");
                \Illuminate\Support\Facades\Cache::put('ai_job_client_' . $clientId, 'completed', 3600);
                \Illuminate\Support\Facades\Cache::put('ai_job_batch_' . $clientId, $batchIds, 3600);
                \Illuminate\Support\Facades\Cache::put('ai_job_count_' . $clientId, $totalAdded, 3600);
            } else {
                \Log::error("All chunks processed but no valid data extracted.");
                \Illuminate\Support\Facades\Cache::put('ai_job_client_' . $clientId, 'failed', 3600);
            }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Cache::put('ai_job_client_' . $clientId, 'failed', 3600);
                \Log::error('Ollama Error: ' . $e->getMessage());
            }
        });

        // Kembalikan response redirect ke pengguna seketika, agar notifikasi sukses muncul di layar
        return back()->with('success', "Sistem AI sedang mengekstrak ~{$totalPages} halaman dokumen Anda di latar belakang. Dokumen besar memakan waktu lebih lama. Anda dapat menutup halaman ini dan kembali nanti.");
    }

    public function jobStatus(Request $request)
    {
        $client = $request->has('license') ? \App\Models\Client::where('license_key', $request->license)->first() : \App\Models\Client::first();
        $clientId = $client ? $client->id : 1;
        $status = \Illuminate\Support\Facades\Cache::get('ai_job_client_' . $clientId);
        $count = \Illuminate\Support\Facades\Cache::get('ai_job_count_' . $clientId, 0);
        $progressCache = \Illuminate\Support\Facades\Cache::get('ai_job_progress_' . $clientId);
        
        $progress = 0;
        $currentPage = 0;
        $totalPages = 0;
        if (is_array($progressCache)) {
            $progress = $progressCache['percentage'] ?? 0;
            $currentPage = $progressCache['current_page'] ?? 0;
            $totalPages = $progressCache['total_pages'] ?? 0;
        } elseif (is_numeric($progressCache)) {
            $progress = $progressCache;
        }
        
        // Hapus cache hanya jika failed (completed tetap disimpan untuk validasi terima/tolak)
        if ($status === 'failed') {
            \Illuminate\Support\Facades\Cache::forget('ai_job_client_' . $clientId);
            \Illuminate\Support\Facades\Cache::forget('ai_job_progress_' . $clientId);
        }
        
        return response()->json([
            'status' => $status, 
            'count' => $count, 
            'progress' => $progress,
            'current_page' => $currentPage,
            'total_pages' => $totalPages
        ]);
    }

    public function jobCancel(Request $request)
    {
        $client = $request->has('license') ? \App\Models\Client::where('license_key', $request->license)->first() : \App\Models\Client::first();
        $clientId = $client ? $client->id : 1;
        \Illuminate\Support\Facades\Cache::put('ai_job_client_' . $clientId, 'cancelled', 3600);
        \Illuminate\Support\Facades\Cache::forget('ai_job_progress_' . $clientId);
        return response()->json(['success' => true]);
    }

    public function jobAccept(Request $request)
    {
        $client = $request->has('license') ? \App\Models\Client::where('license_key', $request->license)->first() : \App\Models\Client::first();
        $clientId = $client ? $client->id : 1;
        $count = \Illuminate\Support\Facades\Cache::get('ai_job_count_' . $clientId, 0);
        
        // Bersihkan semua cache terkait job ini
        \Illuminate\Support\Facades\Cache::forget('ai_job_client_' . $clientId);
        \Illuminate\Support\Facades\Cache::forget('ai_job_batch_' . $clientId);
        \Illuminate\Support\Facades\Cache::forget('ai_job_count_' . $clientId);
        \Illuminate\Support\Facades\Cache::forget('ai_job_progress_' . $clientId);
        
        return response()->json(['success' => true, 'message' => "Berhasil menambahkan {$count} pengetahuan baru."]);
    }

    public function jobReject(Request $request)
    {
        $client = $request->has('license') ? \App\Models\Client::where('license_key', $request->license)->first() : \App\Models\Client::first();
        $clientId = $client ? $client->id : 1;
        $batchIds = \Illuminate\Support\Facades\Cache::get('ai_job_batch_' . $clientId, []);
        $count = count($batchIds);
        
        if (!empty($batchIds)) {
            ChatbotKnowledge::whereIn('id', $batchIds)->delete();
            \Log::info("Rejected and deleted {$count} batch items for client {$clientId}");
        }
        
        // Bersihkan semua cache terkait job ini
        \Illuminate\Support\Facades\Cache::forget('ai_job_client_' . $clientId);
        \Illuminate\Support\Facades\Cache::forget('ai_job_batch_' . $clientId);
        \Illuminate\Support\Facades\Cache::forget('ai_job_count_' . $clientId);
        \Illuminate\Support\Facades\Cache::forget('ai_job_progress_' . $clientId);
        
        return response()->json(['success' => true, 'message' => "Hasil generate ({$count} item) telah ditolak dan dihapus."]);
    }
}

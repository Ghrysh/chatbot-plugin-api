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

        // Simpan parameter yang dibutuhkan ke file JSON sementara untuk dibaca oleh perintah Artisan
        $tmpFileName = 'ai_job_' . $clientId . '_' . time() . '.json';
        $tmpFilePath = storage_path('app/' . $tmpFileName);
        
        $jobData = [
            'chunks' => $chunks,
            'totalChunks' => $totalChunks,
            'totalPages' => $totalPages,
            'ollamaUrl' => $ollamaUrl,
            'model' => $model,
        ];
        
        file_put_contents($tmpFilePath, json_encode($jobData));

        // Eksekusi proses AI sebagai *Command Line Process* yang sepenuhnya terpisah (detached)
        // Ini mencegah PHP-FPM pool dari kelaparan (exhaustion) yang menyebabkan server/web hang
        $artisanPath = base_path('artisan');
        exec("nohup php {$artisanPath} ai:process-knowledge {$clientId} \"{$tmpFilePath}\" > /dev/null 2>&1 &");

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

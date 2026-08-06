<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChatbotKnowledge;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ProcessKnowledgeDocument extends Command
{
    protected $signature = 'ai:process-knowledge {clientId} {tmpFile}';
    protected $description = 'Process extracted document chunks using Ollama AI in the background';

    public function handle()
    {
        $clientId = $this->argument('clientId');
        $tmpFile = $this->argument('tmpFile');

        if (!file_exists($tmpFile)) {
            $this->error('File not found: ' . $tmpFile);
            return;
        }

        $data = json_decode(file_get_contents($tmpFile), true);
        unlink($tmpFile); // Hapus file temporary agar tidak menuhin storage

        $chunks = $data['chunks'];
        $totalChunks = $data['totalChunks'];
        $totalPages = $data['totalPages'];
        $ollamaUrl = $data['ollamaUrl'];
        $model = $data['model'];

        $totalAdded = 0;
        $batchIds = [];

        try {
            foreach ($chunks as $chunkIndex => $chunk) {
                $chunkNum = $chunkIndex + 1;
                
                $currentPage = round(($chunkNum / $totalChunks) * $totalPages);
                $progressPercentage = round(($chunkNum / $totalChunks) * 100);
                Cache::put('ai_job_progress_' . $clientId, [
                    'percentage' => $progressPercentage,
                    'current_page' => $currentPage,
                    'total_pages' => $totalPages
                ], 7200);

                \Log::info("Processing chunk {$chunkNum}/{$totalChunks} (length: " . mb_strlen($chunk, 'UTF-8') . " chars)");

                if (Cache::get('ai_job_client_' . $clientId) === 'cancelled') {
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
                        continue;
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
                                \Log::warning("Chunk {$chunkNum} FAQ item #{$idx} missing keys");
                            }
                        }
                        \Log::info("Chunk {$chunkNum} done. Added " . count($faqs) . " items. Running total: {$totalAdded}");
                    } else {
                        \Log::warning("Chunk {$chunkNum} returned empty/non-array data.");
                    }
                } else {
                    \Log::error("Ollama chunk {$chunkNum} HTTP error: " . $response->status());
                    continue;
                }
            }

            if ($totalAdded > 0) {
                \Log::info("All chunks processed. Total added: {$totalAdded} knowledge items for client {$clientId}.");
                Cache::put('ai_job_client_' . $clientId, 'completed', 3600);
                Cache::put('ai_job_batch_' . $clientId, $batchIds, 3600);
                Cache::put('ai_job_count_' . $clientId, $totalAdded, 3600);
            } else {
                \Log::error("All chunks processed but no valid data extracted.");
                Cache::put('ai_job_client_' . $clientId, 'failed', 3600);
            }
        } catch (\Exception $e) {
            Cache::put('ai_job_client_' . $clientId, 'failed', 3600);
            \Log::error('Ollama Error: ' . $e->getMessage());
        }
    }
}

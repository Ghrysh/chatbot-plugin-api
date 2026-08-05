<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ollamaUrl = env('OLLAMA_URL', 'http://ollama:11434/api/chat');
$model = env('OLLAMA_MODEL', 'gemma2:2b');

echo "Testing Ollama at $ollamaUrl using model $model...\n";

$text = "Berikut adalah layanan yang kami sediakan: Web Hosting dengan cPanel, Cloud VPS dengan SSD NVMe, dan Pendaftaran Nama Domain.";

$prompt = "Anda adalah AI Asisten Pembuat Standar Operasional (SOP) dan Knowledge Base untuk Chatbot Customer Service.
Tugas Anda adalah merangkum teks berikut menjadi JSON murni yang terstruktur.

Ekstrak HANYA informasi terpenting dan kembalikan array JSON berisi object dengan struktur berikut:
- \"topic\": (string, Kategori/Topik. Contoh: 'Akun & Login', 'Layanan')
- \"keywords\": (array of string, hasilkan 5-8 kata kunci atau pertanyaan terkait. Contoh: ['lupa password', 'sandi', 'tidak bisa masuk'])
- \"response\": (string, BALASAN BOT. Rangkai ulang intisari teks menjadi jawaban yang jelas, ramah, dan solutif. Maksimal 3-4 kalimat padat.)

Hasilkan 3-5 topik utama yang paling relevan.
PENTING: Hanya kembalikan array JSON valid, tanpa markdown, tanpa teks awalan/akhiran.
Teks: " . $text;

$startTime = microtime(true);
try {
    $response = Illuminate\Support\Facades\Http::timeout(120)->post($ollamaUrl, [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => 'Return ONLY valid JSON array.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'stream' => false,
    ]);

    $endTime = microtime(true);
    echo "Time taken: " . round($endTime - $startTime, 2) . " seconds\n";
    
    if ($response->successful()) {
        echo "Success!\n";
        echo "Response: " . substr($response->body(), 0, 500) . "...\n";
    } else {
        echo "Failed with status: " . $response->status() . "\n";
        echo "Error: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

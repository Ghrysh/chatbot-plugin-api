<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\ChatbotKnowledge;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\ChatbotController;

class TestChatbotAi extends Command
{
    protected $signature = 'test:chat';
    protected $description = 'Test Chatbot AI with Dummy Knowledge & Dummy Database';

    public function handle()
    {
        $this->info("Menyiapkan Data Dummy untuk Testing...");
        
        // 1. Buat Client Dummy jika belum ada
        $client = Client::firstOrCreate(
            ['license_key' => 'DUMMY-LICENSE-123'],
            [
                'name' => 'Toko Dummy',
                'email' => 'dummy@example.com',
                'status' => 'active'
            ]
        );

        if (!Schema::hasTable('dummy_products')) {
            Schema::create('dummy_products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->integer('price');
                $table->integer('stock');
            });

            DB::table('dummy_products')->insert([
                ['name' => 'Kopi Arabica', 'price' => 50000, 'stock' => 10],
                ['name' => 'Gula Pasir', 'price' => 15000, 'stock' => 50],
                ['name' => 'Teh Melati', 'price' => 20000, 'stock' => 5],
            ]);
        }

        // 3. Konfigurasi Client untuk membaca database (kita arahkan ke database laravel saat ini sendiri agar mudah)
        // Kita bypass koneksi DB di ChatbotController nanti dengan menggunakan koneksi default jika host-nya DUMMY
        $client->db_allow_read = true;
        $client->db_host = env('DB_HOST');
        $client->db_port = env('DB_PORT');
        $client->db_database = env('DB_DATABASE');
        $client->db_username = env('DB_USERNAME');
        $client->db_password = env('DB_PASSWORD');
        $client->db_allowed_tables = ['dummy_products'];
        $client->save();

        // 4. Buat Knowledge Dummy
        ChatbotKnowledge::firstOrCreate(
            ['client_id' => $client->id, 'topic' => 'Jam Buka'],
            ['keywords' => json_encode(['jam buka', 'buka jam', 'tutup jam']), 'response' => 'Toko kami buka dari jam 08:00 hingga 20:00 setiap hari.']
        );

        $this->info("\n=== CHATBOT SIMULATOR ===");
        $this->info("Ketik 'exit' untuk keluar.");
        $this->info("Contoh pertanyaan Knowledge: 'jam berapa buka?'");
        $this->info("Contoh pertanyaan Database: 'ada berapa produk?', 'berapa harga kopi arabica?'\n");

        $controller = new ChatbotController();
        $sessionId = uniqid();

        while (true) {
            $message = $this->ask('Anda');
            if (strtolower($message) == 'exit') break;

            // Simulasi Request API
            $request = Request::create('/api/v1/chat/send', 'POST', [
                'message' => $message,
                'session_id' => $sessionId,
            ]);
            $request->headers->set('X-FutureCloud-License', 'DUMMY-LICENSE-123');

            $this->info('Memproses...');
            $response = $controller->send($request);
            
            $rawContent = $response->getContent();
            $data = json_decode($rawContent, true);
            
            if ($data && isset($data['reply'])) {
                $cleanReply = str_replace(['<br />', '<br>', '<br/>'], "\n", $data['reply']);
                $this->line("<fg=green>Bot:</> " . $cleanReply);
            } else if ($data && isset($data['error'])) {
                $this->error("Error: " . $data['error']);
            } else {
                $this->error("RAW RESPONSE: " . $rawContent);
            }
        }
    }
}

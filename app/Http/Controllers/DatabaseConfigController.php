<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DatabaseConfigController extends Controller
{
    private function getClient(Request $request = null)
    {
        if ($request && $request->has('license')) {
            $client = \App\Models\Client::where('license_key', $request->query('license'))
                ->where('status', 'active')
                ->first();
            if ($client) return $client;
        }
        return \App\Models\Client::first();
    }

    public function testAndSave(Request $request)
    {
        $request->validate([
            'db_host' => 'required|string',
            'db_port' => 'required|string',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        $client = $this->getClient($request);
        if (!$client) {
            return back()->with('error', 'Klien tidak ditemukan.');
        }

        // Simpan sementara konfigurasi
        $client->db_host = $request->db_host;
        $client->db_port = $request->db_port;
        $client->db_database = $request->db_database;
        $client->db_username = $request->db_username;
        $client->db_password = $request->db_password;
        $client->db_allow_read = $request->has('db_allow_read');
        
        // Tes koneksi
        config([
            'database.connections.client_db' => [
                'driver' => $driver,
                'port' => $client->db_port,
                'database' => $client->db_database,
                'username' => $client->db_username,
                'password' => $client->db_password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ],
        ]);

        try {
            DB::purge('client_db');
            
            // Gunakan fitur Schema builder bawaan Laravel agar mendukung semua database (MySQL, PgSQL, SQL Server, dll)
            }, $tables);

            $allowed = $client->db_allowed_tables ?? [];
            $allowed = array_intersect($allowed, $tablesList);
            $client->db_allowed_tables = $allowed;
            $client->save();

            // Simpan list table ke session agar bisa ditampilkan di UI
            session()->flash('db_tables_list', $tablesList);
            session()->flash('success', 'Koneksi database berhasil! Silakan centang tabel yang diizinkan untuk dibaca AI.');
        } catch (\Exception $e) {
            $client->save(); // Save anyway so they don't have to re-type, but it fails
            return back()->with('error', 'Gagal terhubung ke database klien: ' . $e->getMessage());
        }

        $url = url()->previous();
        if (!str_contains($url, 'botTab=')) {
            $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'botTab=database';
        }
        return redirect($url);
    }

    public function saveTables(Request $request)
    {
        $client = $this->getClient($request);
        if (!$client) {
            return back()->with('error', 'Klien tidak ditemukan.');
        }

        $allowedTables = $request->input('allowed_tables', []);
        $client->db_allowed_tables = $allowedTables;
        $client->save();

        $url = url()->previous();
        if (!str_contains($url, 'botTab=')) {
            $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'botTab=database';
        }
        return redirect($url)->with('success', 'Pengaturan tabel berhasil disimpan.');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;

class LicenseController extends Controller
{
    public function verify(Request $request)
    {
        $licenseKey = $request->header('X-FutureCloud-License');

        if (!$licenseKey) {
            return response()->json([
                'valid' => false,
                'message' => 'License key is missing in header.'
            ], 200);
        }

        $client = Client::where('license_key', $licenseKey)->first();

        if (!$client) {
            return response()->json([
                'valid' => false,
                'message' => 'Lisensi tidak valid atau tidak terdaftar di sistem FutureCloud.'
            ], 200);
        }

        if ($client->is_installed) {
            return response()->json([
                'valid' => false,
                'message' => 'Lisensi ini sudah terinstall dan telah digunakan sebelumnya.'
            ], 200);
        }

        if ($client->status !== 'active') {
            return response()->json([
                'valid' => false,
                'message' => 'Lisensi ini tidak aktif atau sedang ditangguhkan.'
            ], 200);
        }

        return response()->json([
            'valid' => true,
            'client_name' => $client->name
        ]);
    }

    public function sync(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'license_key' => 'required|string',
        ]);

        $client = Client::updateOrCreate(
            ['license_key' => $request->license_key],
            [
                'name' => $request->name,
                'email' => $request->email,
                'status' => 'active',
                'subscription_expires_at' => now()->addYear(),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'License synced successfully'
        ]);
    }

    public function config(Request $request)
    {
        $licenseKey = $request->query('license');
        $client = Client::where('license_key', $licenseKey)->first();

        return response()->json([
            'bot_name' => $client ? $client->bot_name : 'Chatbot Ai',
            'bot_color' => $client ? $client->bot_color : '#2563eb',
            'whatsapp_number' => $client ? $client->whatsapp_number : null,
            'is_active' => $client ? ($client->status === 'active') : false,
            // Database Config fields
            'db_allow_read' => $client ? (bool)$client->db_allow_read : false,
            'integration_type' => $client ? $client->integration_type : 'mysql',
            'spreadsheet_id' => $client ? $client->spreadsheet_id : null,
            'sheet_name_range' => $client ? $client->sheet_name_range : null,
            'db_host' => $client ? $client->db_host : null,
            'db_port' => $client ? $client->db_port : null,
            'db_database' => $client ? $client->db_database : null,
            'db_username' => $client ? $client->db_username : null,
            'db_password' => $client ? $client->db_password : null,
            'db_allowed_tables' => $client ? $client->db_allowed_tables : [],
        ]);
    }

    public function install(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string'
        ]);

        $client = Client::where('license_key', $request->license_key)->first();
        
        if (!$client) {
            return response()->json([
                'status' => 'error',
                'message' => 'License not found'
            ], 404);
        }

        $client->update(['is_installed' => true]);

        // Beritahu FutureCloud bahwa plugin sudah diinstal
        try {
            $response = \Illuminate\Support\Facades\Http::post(env('MAIN_APP_URL', 'https://www.futurecloud.id') . '/webhook/plugin/installed', [
                'license_key' => $client->license_key
            ]);
            \Illuminate\Support\Facades\Log::info("Webhook sent, response: " . $response->body());
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gagal webhook is_installed ke futurecloud: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'License marked as installed successfully'
        ]);
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string',
            'status' => 'required|string',
        ]);
        
        $client = Client::where('license_key', $request->license_key)->first();
        if ($client) {
            $client->update(['status' => $request->status]);
            return response()->json(['message' => 'Status updated']);
        }
        return response()->json(['message' => 'Not found'], 404);
    }

    public function updateConfig(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string',
            'bot_name' => 'nullable|string',
            'bot_color' => 'nullable|string',
            'whatsapp_number' => 'nullable|string|max:25',
            // Validate new db fields
            'db_allow_read' => 'nullable|boolean',
            'integration_type' => 'nullable|string',
            'spreadsheet_id' => 'nullable|string',
            'sheet_name_range' => 'nullable|string',
            'db_host' => 'nullable|string',
            'db_port' => 'nullable|string',
            'db_database' => 'nullable|string',
            'db_username' => 'nullable|string',
            'db_password' => 'nullable|string',
            'db_allowed_tables' => 'nullable|array',
        ]);
        
        $client = Client::where('license_key', $request->license_key)->first();
        if ($client) {
            $client->update([
                'bot_name' => $request->bot_name,
                'bot_color' => $request->bot_color,
                'whatsapp_number' => $request->whatsapp_number,
                'db_allow_read' => $request->boolean('db_allow_read'),
                'integration_type' => $request->integration_type,
                'spreadsheet_id' => $request->spreadsheet_id,
                'sheet_name_range' => $request->sheet_name_range,
                'db_host' => $request->db_host,
                'db_port' => $request->db_port,
                'db_database' => $request->db_database,
                'db_username' => $request->db_username,
                'db_password' => $request->db_password,
                'db_allowed_tables' => $request->db_allowed_tables ?? [],
            ]);
            return response()->json(['message' => 'Config updated']);
        }
        return response()->json(['message' => 'Not found'], 404);
    }

    public function resetData(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string',
        ]);
        
        $client = Client::where('license_key', $request->license_key)->first();
        if ($client) {
            \App\Models\ChatbotLead::where('client_id', $client->id)->delete();
            return response()->json(['message' => 'Data reset successful']);
        }
        return response()->json(['message' => 'Not found'], 404);
    }

    public function destroy($licenseKey)
    {
        $client = Client::where('license_key', $licenseKey)->first();
        if ($client) {
            $client->delete();
            return response()->json(['message' => 'License deleted']);
        }
        return response()->json(['message' => 'Not found'], 404);
    }
}
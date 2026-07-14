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
            ], 400);
        }

        $client = Client::where('license_key', $licenseKey)->first();

        if (!$client) {
            return response()->json([
                'valid' => false,
                'message' => 'License key not found.'
            ], 404);
        }

        if ($client->status !== 'active') {
            return response()->json([
                'valid' => false,
                'message' => 'License is inactive or suspended.'
            ], 403);
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
            'is_active' => $client ? ($client->status === 'active') : false
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
        ]);
        
        $client = Client::where('license_key', $request->license_key)->first();
        if ($client) {
            $client->update([
                'bot_name' => $request->bot_name,
                'bot_color' => $request->bot_color,
            ]);
            return response()->json(['message' => 'Config updated']);
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
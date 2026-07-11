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
}

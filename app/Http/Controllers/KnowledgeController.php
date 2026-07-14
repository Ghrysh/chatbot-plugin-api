<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatbotKnowledge;

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
}

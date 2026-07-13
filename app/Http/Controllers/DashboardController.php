<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatbotKnowledge;
use App\Models\ChatbotLead;

class DashboardController extends Controller
{
    private function getClient(Request $request = null)
    {
        if ($request && $request->has('license')) {
            $client = \App\Models\Client::where('license_key', $request->query('license'))
                ->where('status', 'active')
                ->first();
            if ($client) return $client;
        }
        
        // Fallback for direct dashboard access (if any)
        return \App\Models\Client::first();
    }

    public function index(Request $request)
    {
        $client = $this->getClient($request);
        $clientId = $client ? $client->id : null;

        $chatbotLeads = ChatbotLead::where('client_id', $clientId)
                                   ->orderBy('created_at', 'desc')
                                   ->paginate(10, ['*'], 'leads_page');

        $chatbotKnowledges = ChatbotKnowledge::where('client_id', $clientId)
                                             ->orderBy('created_at', 'desc')
                                             ->get();

        return view('dashboard', compact('chatbotLeads', 'chatbotKnowledges'));
    }

    public function embedChatbot(Request $request)
    {
        $client = $this->getClient($request);
        $clientId = $client ? $client->id : null;

        $chatbotLeads = ChatbotLead::where('client_id', $clientId)
                                   ->orderBy('created_at', 'desc')
                                   ->paginate(10, ['*'], 'leads_page');

        $chatbotKnowledges = ChatbotKnowledge::where('client_id', $clientId)
                                             ->orderBy('created_at', 'desc')
                                             ->get();

        return view('embed.chatbot', compact('chatbotLeads', 'chatbotKnowledges', 'client'));
    }

    public function embedLivechat(Request $request)
    {
        $client = $this->getClient($request);
        $clientId = $client ? $client->id : null;
        return view('embed.livechat', compact('clientId', 'client'));
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatbotKnowledge;
use App\Models\ChatbotLead;

class DashboardController extends Controller
{
    private function getClientId(Request $request = null)
    {
        if ($request && $request->has('license')) {
            $client = \App\Models\Client::where('license_key', $request->query('license'))
                ->where('status', 'active')
                ->first();
            if ($client) return $client->id;
        }
        
        // Fallback for direct dashboard access (if any)
        $client = \App\Models\Client::first();
        return $client ? $client->id : null;
    }

    public function index(Request $request)
    {
        $clientId = $this->getClientId($request);

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
        $clientId = $this->getClientId($request);

        $chatbotLeads = ChatbotLead::where('client_id', $clientId)
                                   ->orderBy('created_at', 'desc')
                                   ->paginate(10, ['*'], 'leads_page');

        $chatbotKnowledges = ChatbotKnowledge::where('client_id', $clientId)
                                             ->orderBy('created_at', 'desc')
                                             ->get();

        return view('embed.chatbot', compact('chatbotLeads', 'chatbotKnowledges'));
    }

    public function embedLivechat(Request $request)
    {
        $clientId = $this->getClientId($request);
        return view('embed.livechat', compact('clientId'));
    }
}

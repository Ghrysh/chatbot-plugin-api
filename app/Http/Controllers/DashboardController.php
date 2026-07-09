<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatbotKnowledge;
use App\Models\ChatbotLead;

class DashboardController extends Controller
{
    private function getClientId()
    {
        // Dummy logic: Get the first client from DB.
        $client = \App\Models\Client::first();
        return $client ? $client->id : null;
    }

    public function index(Request $request)
    {
        $clientId = $this->getClientId();

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
        $clientId = $this->getClientId();

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
        return view('embed.livechat');
    }
}

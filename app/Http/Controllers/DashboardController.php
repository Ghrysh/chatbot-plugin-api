<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatbotKnowledge;
use App\Models\ChatbotLead;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $knowledges = ChatbotKnowledge::latest()->get();
        $leads = ChatbotLead::latest()->paginate(20);
        
        return view('dashboard', compact('knowledges', 'leads'));
    }
}

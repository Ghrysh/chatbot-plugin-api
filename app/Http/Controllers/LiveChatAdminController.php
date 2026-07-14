<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatbotLead;
use Carbon\Carbon;

class LiveChatAdminController extends Controller
{
    public function poll()
    {
        // Get leads for the current client. For demo, we get all.
        $leads = ChatbotLead::orderBy('updated_at', 'desc')->get();
        
        return response()->json([
            'pending' => $leads->where('live_chat_status', 'pending')->values(),
            'active' => $leads->where('live_chat_status', 'active')->values(),
            'ended' => $leads->where('live_chat_status', 'ended')->values(),
        ]);
    }

    public function getHistory($id)
    {
        $lead = ChatbotLead::findOrFail($id);
        return response()->json(json_decode($lead->chat_history, true) ?? []);
    }

    public function updateStatus(Request $request, $id)
    {
        $lead = ChatbotLead::findOrFail($id);
        $lead->status = $lead->status === 'contacted' ? 'pending' : 'contacted';
        $lead->save();
        
        return redirect()->back()->with('success', 'Status lead berhasil diperbarui!');
    }

    public function action(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|integer',
            'action' => 'required|in:accept,reject,end'
        ]);

        $lead = ChatbotLead::findOrFail($request->lead_id);
        $history = json_decode($lead->chat_history, true) ?? [];

        if ($request->action === 'accept') {
            $lead->live_chat_status = 'active';
            $history[] = [
                'sender' => 'system',
                'text' => 'Admin telah bergabung dalam obrolan.',
                'time' => now()->format('d M, H:i')
            ];
        } elseif ($request->action === 'reject') {
            $lead->live_chat_status = 'ended';
            $history[] = [
                'sender' => 'system',
                'text' => 'Admin menolak permintaan chat.',
                'time' => now()->format('d M, H:i')
            ];
        } elseif ($request->action === 'end') {
            $lead->live_chat_status = 'ended';
            $history[] = [
                'sender' => 'system',
                'text' => 'Sesi Live Chat telah ditutup oleh Admin.',
                'time' => now()->format('d M, H:i')
            ];
        }

        $lead->chat_history = json_encode($history);
        $lead->admin_id = auth()->id() ?? 1;
        $lead->save();

        return response()->json(['success' => true]);
    }

    public function replyMessage(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|integer',
            'message' => 'required|string'
        ]);
        
        $lead = ChatbotLead::findOrFail($request->lead_id);
        $history = json_decode($lead->chat_history, true) ?? [];
        
        $history[] = [
            'sender' => 'admin',
            'text' => $request->message,
            'time' => now()->format('d M, H:i')
        ];
        
        $lead->update([
            'chat_history' => json_encode($history),
            'live_chat_status' => 'active',
            'admin_id' => auth()->id() ?? 1 // link to the logged in admin
        ]);

        return response()->json(['success' => true]);
    }
}

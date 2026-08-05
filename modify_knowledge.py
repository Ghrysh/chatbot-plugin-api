import re

def modify_file(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    # 1. Add jobStatus and jobCancel methods to KnowledgeController
    new_methods = """
    public function jobStatus(Request $request)
    {
        $client = $request->has('license') ? \App\Models\Client::where('license_key', $request->license)->first() : \App\Models\Client::first();
        $clientId = $client ? $client->id : 1;
        $status = \Illuminate\Support\Facades\Cache::get('ai_job_client_' . $clientId);
        return response()->json(['status' => $status]);
    }

    public function jobCancel(Request $request)
    {
        $client = $request->has('license') ? \App\Models\Client::where('license_key', $request->license)->first() : \App\Models\Client::first();
        $clientId = $client ? $client->id : 1;
        \Illuminate\Support\Facades\Cache::put('ai_job_client_' . $clientId, 'cancelled', 3600);
        return response()->json(['success' => true]);
    }
"""
    # Insert before the last closing brace of the class
    content = re.sub(r'}\s*$', new_methods + '\n}', content)

    # 2. Modify generate() to set Cache state
    # Find: if (function_exists('fastcgi_finish_request')) {
    cache_set_code = """
        $client = $request->has('license') ? \App\Models\Client::where('license_key', $request->license)->first() : \App\Models\Client::first();
        $clientId = $client ? $client->id : 1;
        \Illuminate\Support\Facades\Cache::put('ai_job_client_' . $clientId, 'processing', 3600);

        // Mencegah Nginx 504 Timeout"""
    content = content.replace('// Mencegah Nginx 504 Timeout', cache_set_code)

    # 3. Modify generate() after success to check for cancellation
    # Find: if (is_array($faqs) && count($faqs) > 0) {
    cancellation_check = """
                if (\Illuminate\Support\Facades\Cache::get('ai_job_client_' . $clientId) === 'cancelled') {
                    \Log::info("Ollama AI job cancelled by user.");
                    return response()->json(['status' => 'cancelled']);
                }

                if (is_array($faqs) && count($faqs) > 0) {"""
    
    # We already have $clientId defined in the original code, but we define it earlier now. 
    # Let's clean up the existing $clientId in the foreach loop block.
    # Find: $client = $request->has('license') ? \App\Models\Client::where('license_key', $request->license)->first() : \App\Models\Client::first();
    # Find: $clientId = $client ? $client->id : 1;
    # Replace those inside the `if (is_array($faqs))` block with just the cancellation check
    old_client_code = """                    $client = $request->has('license') ? \App\Models\Client::where('license_key', $request->license)->first() : \App\Models\Client::first();
                    $clientId = $client ? $client->id : 1;"""
    content = content.replace(old_client_code, "")
    content = content.replace('if (is_array($faqs) && count($faqs) > 0) {', cancellation_check)

    # 4. Modify generate() at the very end to mark as completed
    # Find: return back()->with('success', $added . ' pengetahuan berhasil ditambahkan dari dokumen.');
    mark_completed = """\Illuminate\Support\Facades\Cache::put('ai_job_client_' . $clientId, 'completed', 3600);
                    return back()->with('success', $added . ' pengetahuan berhasil ditambahkan dari dokumen.');"""
    content = content.replace("return back()->with('success', $added . ' pengetahuan berhasil ditambahkan dari dokumen.');", mark_completed)

    # 5. Modify generate() in catch block to mark as failed
    # Find: return back()->with('error', 'Terjadi kesalahan saat memproses data AI.');
    mark_failed = """\Illuminate\Support\Facades\Cache::put('ai_job_client_' . $clientId, 'failed', 3600);
            return back()->with('error', 'Terjadi kesalahan saat memproses data AI.');"""
    content = content.replace("return back()->with('error', 'Terjadi kesalahan saat memproses data AI.');", mark_failed)

    with open(filepath, 'w') as f:
        f.write(content)
    print("Modified", filepath)

modify_file('app/Http/Controllers/KnowledgeController.php')

import re

def fix_toast_placement(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    toast_start_marker = "\n<style>\n@keyframes custom-ai-spin {"
    toast_end_marker = "</div>\n\n            <!-- Modal Konfirmasi Hapus -->"

    # Find the toast block
    style_idx = content.find(toast_start_marker)
    if style_idx == -1:
        print(f"Style block not found in {filepath}")
        return
    
    end_idx = content.find(toast_end_marker, style_idx)
    if end_idx == -1:
        print(f"Toast end not found in {filepath}")
        return
    
    # Extract the toast (style + toast div)
    toast_block = content[style_idx:end_idx + len("</div>")]
    
    # Remove toast from its current location
    content = content[:style_idx] + "\n\n            <!-- Modal Konfirmasi Hapus -->" + content[end_idx + len(toast_end_marker):]
    
    # Now find the closing </div> before </x-embed-layout> or </x-app-layout>
    # The structure is:
    #     </div>  <!-- closes the p-6 w-full div -->
    # </x-embed-layout>
    
    # Find the embed layout tag
    embed_close = "</x-embed-layout>"
    app_close = "</x-app-layout>"
    
    close_tag = embed_close if embed_close in content else app_close
    close_idx = content.rfind(close_tag)
    
    if close_idx == -1:
        print(f"Layout close tag not found in {filepath}")
        return
    
    # Find the </div> just before the close tag (the main content wrapper close)
    # We want to insert the toast RIGHT BEFORE the last </div> before the layout close
    # Let's find the position
    
    # The new toast should be placed outside both tab divs but inside the main x-data div
    # We need to modify it so that:
    # 1. It's a standalone component with its own x-data
    # 2. It auto-switches to knowledge tab when processing
    
    # Build the new toast with auto-tab-switch
    new_toast = """
<style>
@keyframes custom-ai-spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
<!-- Floating AI Progress Toast (Global - Outside Tabs) -->
<div x-data="{
        aiJob: { status: null, progress: 0 },
        pollInterval: null,
        showCancelModal: false,
        checkStatus() {
            fetch('""" + "{{ route('knowledge.job-status') }}?license={{ request('license') }}" + """')
                .then(res => res.json())
                .then(data => {
                    if (data.status) {
                        this.aiJob.status = data.status;
                        if (data.status === 'processing') {
                            if (this.aiJob.progress < 99) { this.aiJob.progress += (99 - this.aiJob.progress) * 0.03; }
                        } else if (data.status === 'completed') {
                            this.aiJob.progress = 100;
                            clearInterval(this.pollInterval);
                            this.showCancelModal = false;
                            setTimeout(() => {
                                let url = new URL(window.location.href);
                                url.searchParams.set('tab', 'knowledge');
                                window.location.href = url.toString();
                            }, 1500);
                        } else if (data.status === 'failed' || data.status === 'cancelled') {
                            this.aiJob.status = null;
                            clearInterval(this.pollInterval);
                        }
                    } else {
                        this.aiJob.status = null;
                    }
                })
                .catch(err => console.error(err));
        },
        initPoll() {
            this.checkStatus();
            this.pollInterval = setInterval(() => {
                if (this.aiJob.status === 'processing' || this.aiJob.status === null) {
                    this.checkStatus();
                }
            }, 3000);
        },
        confirmCancel() {
            fetch('""" + "{{ route('knowledge.job-cancel') }}?license={{ request('license') }}" + """', {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': '""" + "{{ csrf_token() }}" + """',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            }).then(() => { 
                this.aiJob.status = null; 
                this.showCancelModal = false;
                if(this.pollInterval) clearInterval(this.pollInterval);
            });
        }
    }"
    x-init="initPoll()"
    x-show="aiJob.status === 'processing' || aiJob.status === 'completed'"
    style="display: none; margin: 20px 0; padding: 20px 24px; width: 100%; max-width: 400px; background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; overflow: hidden;"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-10"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-10">
    
    <!-- Cancel Confirmation -->
    <template x-if="showCancelModal">
        <div style="display: flex; flex-direction: column; justify-content: center;">
            <p style="font-size: 14px; font-weight: bold; color: #1e293b; margin-bottom: 4px;">Batalkan proses AI?</p>
            <p style="font-size: 12px; color: #64748b; margin-bottom: 12px;">Sistem akan berhenti mengekstrak dokumen Anda dan data tidak akan disimpan ke tabel.</p>
            <div style="display: flex; gap: 8px;">
                <button @click="confirmCancel()" style="flex: 1; padding: 6px 0; background: #fee2e2; color: #dc2626; font-size: 12px; font-weight: 600; border-radius: 6px; border: 1px solid #fecaca; cursor: pointer;">Ya, Batalkan</button>
                <button @click="showCancelModal = false" style="flex: 1; padding: 6px 0; background: #f1f5f9; color: #475569; font-size: 12px; font-weight: 600; border-radius: 6px; border: 1px solid #e2e8f0; cursor: pointer;">Lanjutkan</button>
            </div>
        </div>
    </template>

    <!-- Normal Progress UI -->
    <template x-if="!showCancelModal">
        <div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <template x-if="aiJob.status === 'processing'">
                        <div style="position: relative; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;">
                            <svg style="animation: custom-ai-spin 1s linear infinite; color: #4f46e5; width: 20px; height: 20px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </template>
                    <template x-if="aiJob.status === 'completed'">
                        <div style="position: relative; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; border-radius: 9999px; background: #dcfce7;">
                            <svg style="width: 14px; height: 14px; color: #16a34a;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                    </template>
                    <span style="font-size: 14px; font-weight: 600; color: #334155;" x-text="aiJob.status === 'completed' ? 'Proses Selesai!' : 'AI mengekstrak data...'"></span>
                </div>
                <template x-if="aiJob.status === 'processing'">
                    <button @click="showCancelModal = true" type="button" style="font-size: 11px; color: #dc2626; font-weight: 500; padding: 4px 8px; border-radius: 6px; background: #fef2f2; border: 1px solid #fee2e2; cursor: pointer;">
                        Batal
                    </button>
                </template>
            </div>
            
            <div style="width: 100%; background: #f1f5f9; border-radius: 9999px; height: 8px; margin-bottom: 8px; overflow: hidden; box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);">
                <div style="height: 8px; border-radius: 9999px; transition: all 700ms ease-out;"
                     :style="{ backgroundColor: aiJob.status === 'completed' ? '#22c55e' : '#4f46e5', width: aiJob.progress + '%' }">
                </div>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #64748b;">
                <span x-text="aiJob.status === 'completed' ? 'Menyegarkan halaman...' : 'Berjalan di latar belakang'"></span>
                <span style="font-weight: 700; color: #334155;" x-text="Math.floor(aiJob.progress) + '%'"></span>
            </div>
        </div>
    </template>
</div>
"""

    # Insert the toast RIGHT BEFORE the last </div> before the layout close tag
    # Walk backwards from close_idx to find the insertion point
    insert_before = content.rfind("</div>", 0, close_idx)
    if insert_before == -1:
        print("Could not find insertion point")
        return
    
    content = content[:insert_before] + new_toast + "\n" + content[insert_before:]
    
    with open(filepath, 'w') as f:
        f.write(content)
    print(f"Fixed toast placement in {filepath}")

fix_toast_placement('resources/views/embed/chatbot.blade.php')
fix_toast_placement('resources/views/dashboard.blade.php')

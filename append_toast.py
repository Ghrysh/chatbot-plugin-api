import re

def append_to_file(filepath):
    toast_html = """
<!-- Floating AI Progress Toast -->
<div x-data="{
        aiJob: { status: null, progress: 0 },
        pollInterval: null,
        checkStatus() {
            fetch('{{ route('knowledge.job-status') }}')
                .then(res => res.json())
                .then(data => {
                    if (data.status) {
                        this.aiJob.status = data.status;
                        if (data.status === 'processing') {
                            if (this.aiJob.progress < 99) this.aiJob.progress += 2;
                        } else if (data.status === 'completed') {
                            this.aiJob.progress = 100;
                            clearInterval(this.pollInterval);
                            setTimeout(() => window.location.reload(), 1500);
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
        cancelJob() {
            if(!confirm('Yakin ingin membatalkan proses AI? Data yang sedang diproses akan diabaikan.')) return;
            fetch('{{ route('knowledge.job-cancel') }}', {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            }).then(() => { 
                this.aiJob.status = null; 
                if(this.pollInterval) clearInterval(this.pollInterval);
            });
        }
    }"
    x-init="initPoll()"
    x-show="aiJob.status === 'processing' || aiJob.status === 'completed'"
    style="display: none;"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-10"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-10"
    class="fixed bottom-6 right-6 bg-white rounded-xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.15)] border border-slate-200 p-5 w-80 z-[100] overflow-hidden">
    
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <template x-if="aiJob.status === 'processing'">
                <div class="relative w-5 h-5 flex items-center justify-center">
                    <svg class="animate-spin text-indigo-600 w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </template>
            <template x-if="aiJob.status === 'completed'">
                <div class="relative w-5 h-5 flex items-center justify-center rounded-full bg-green-100">
                    <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </div>
            </template>
            <span class="text-sm font-semibold text-slate-700" x-text="aiJob.status === 'completed' ? 'Proses Selesai!' : 'AI mengekstrak data...'"></span>
        </div>
        <template x-if="aiJob.status === 'processing'">
            <button @click="cancelJob()" type="button" class="text-[11px] text-red-600 hover:text-white font-medium px-2 py-1 rounded-md bg-red-50 hover:bg-red-500 transition-colors border border-red-100 hover:border-red-500">
                Batal
            </button>
        </template>
    </div>
    
    <div class="w-full bg-slate-100 rounded-full h-2 mb-2 overflow-hidden shadow-inner">
        <div class="h-2 rounded-full transition-all duration-700 ease-out"
             :class="aiJob.status === 'completed' ? 'bg-green-500' : 'bg-indigo-600'"
             :style="'width: ' + aiJob.progress + '%'">
        </div>
    </div>
    
    <div class="flex justify-between items-center text-xs text-slate-500">
        <span x-text="aiJob.status === 'completed' ? 'Menyegarkan halaman...' : 'Berjalan di latar belakang'"></span>
        <span class="font-bold text-slate-700" x-text="aiJob.progress + '%'"></span>
    </div>
</div>
"""
    with open(filepath, 'r') as f:
        content = f.read()
    
    if "Floating AI Progress Toast" not in content:
        # Append before closing layout tag
        if "</x-app-layout>" in content:
            content = content.replace("</x-app-layout>", toast_html + "\n</x-app-layout>")
        else:
            content += "\n" + toast_html
        with open(filepath, 'w') as f:
            f.write(content)
        print("Appended toast to", filepath)

append_to_file('resources/views/dashboard.blade.php')
append_to_file('resources/views/embed/chatbot.blade.php')

<x-embed-layout>
    <div class="p-6 w-full h-full bg-slate-50" x-data="{ botTab: new URLSearchParams(location.search).get('tab') || 'leads' }">
        
        <!-- Flash Messages -->
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex justify-between items-center shadow-sm">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="font-bold text-sm">{{ session('success') }}</p>
                </div>
                <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 font-bold px-2">&times;</button>
            </div>
        @endif

        @if(session('error'))
            <div x-data="{ show: true }" x-show="show" class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl flex justify-between items-center shadow-sm">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <p class="font-bold text-sm">{{ session('error') }}</p>
                </div>
                <button @click="show = false" class="text-red-500 hover:text-red-700 font-bold px-2">&times;</button>
            </div>
        @endif

        <div class="mb-6 flex flex-col md:flex-row justify-between md:items-end gap-4">
            <h2 class="text-xl font-bold text-slate-800">Manajemen Chatbot</h2>
            <div class="flex flex-col sm:flex-row bg-white shadow-sm p-1 rounded-xl w-full md:w-fit gap-1 border border-slate-200">
                <button @click="botTab = 'leads'" class="w-full sm:w-auto px-4 py-2 rounded-lg text-sm font-semibold transition-all" :class="botTab === 'leads' ? 'bg-blue-50 text-blue-700' : 'text-slate-500 hover:text-slate-700'">Inbox Follow Up</button>
                <button @click="botTab = 'knowledge'" class="w-full sm:w-auto px-4 py-2 rounded-lg text-sm font-semibold transition-all" :class="botTab === 'knowledge' ? 'bg-blue-50 text-blue-700' : 'text-slate-500 hover:text-slate-700'">Latih Otak Bot</button>
            </div>
        </div>

        <!-- CHATBOT: LEADS SUBTAB -->
        <div x-show="botTab === 'leads'" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" 
             x-data="{ showChatModal: false, activeChat: [], activeLeadId: null, pollInterval: null,
                       openModal(id, history) {
                           this.activeLeadId = id;
                           this.activeChat = history || [];
                           this.showChatModal = true;
                           this.pollInterval = setInterval(async () => {
                               let res = await fetch(`/livechat/${id}/history`);
                               this.activeChat = await res.json();
                           }, 3000);
                       },
                       closeModal() {
                           this.showChatModal = false;
                           clearInterval(this.pollInterval);
                       }
             }">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 whitespace-nowrap">Pengguna</th>
                            <th class="px-6 py-4 whitespace-nowrap">Topik</th>
                            <th class="px-6 py-4">Status & Kontak Diberikan</th>
                            <th class="px-6 py-4 whitespace-nowrap">Waktu</th>
                            <th class="px-6 py-4 text-center whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($chatbotLeads as $lead)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-700 flex items-center gap-1">👤 Guest / Visitor</div>
                                <div class="text-xs text-slate-400">IP: {{ $lead->ip_address }}</div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-800">{{ $lead->topic_context ?? 'Umum' }}</td>
                            <td class="px-6 py-4">
                                @if($lead->live_chat_status !== 'ended' && ($lead->contact_info === '-' || empty($lead->contact_info)))
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 text-blue-600 font-bold text-xs rounded-lg border border-blue-200">
                                        <span class="w-2 h-2 rounded-full bg-blue-500 animate-ping"></span> Chat Masih Aktif
                                    </span>
                                @else
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 text-emerald-700 font-bold text-xs rounded-lg border border-emerald-200 mb-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg> Chat Diakhiri
                                    </div><br>
                                    @if($lead->contact_info !== '-' && !empty($lead->contact_info))
                                        <span class="text-xs font-bold text-slate-700">Follow up via: <span class="text-blue-600">{{ $lead->contact_info }}</span></span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">{{ $lead->created_at->format('d M Y, H:i') }}</td>
                            <td class="px-6 py-4 text-center space-y-2">
                                <form action="{{ route('livechat.status', $lead->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-[10px] font-bold px-2 py-1.5 rounded-lg border w-full transition-colors {{ $lead->status === 'contacted' ? 'bg-green-50 text-green-600 border-green-200 hover:bg-green-100' : 'bg-amber-50 text-amber-600 border-amber-200 hover:bg-amber-100' }}">
                                        {{ $lead->status === 'contacted' ? '✅ Selesai Dihubungi' : '⚠️ Belum Dihubungi' }}
                                    </button>
                                </form>
                                <button @click="openModal({{ $lead->id }}, {{ $lead->chat_history ?? '[]' }})" class="text-xs text-white bg-slate-800 hover:bg-slate-900 px-3 py-1.5 rounded-lg w-full font-semibold transition-colors flex items-center justify-center gap-1 shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg> Pantau Chat
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-6 py-8 text-center text-slate-400">Belum ada user yang berinteraksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

<div class="p-4 border-t border-slate-100 flex overflow-x-auto">
                {{ $chatbotLeads->appends(['leads_page' => request('leads_page')])->links() }}
            </div>

            <div x-show="showChatModal" style="display: none;" class="fixed inset-0 z-[200] flex items-center justify-center p-4">
                <div x-show="showChatModal" x-transition.opacity class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeModal()"></div>
                <div x-show="showChatModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden flex flex-col h-[600px] max-h-[90vh]">
                    <div class="bg-gradient-to-r from-slate-800 to-slate-900 p-4 flex items-center justify-between text-white flex-shrink-0 shadow-md">
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-sm">📡 Pantau Chat Langsung</h3>
                            <span class="flex h-2 w-2 relative"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span></span>
                        </div>
                        <button @click="closeModal()" class="hover:text-red-400 bg-white/10 p-1.5 rounded-lg transition-colors"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                    </div>
                    <div id="admin-chat-scroll" class="flex-1 overflow-y-auto p-5 bg-slate-50 space-y-4" x-init="$watch('activeChat', () => { setTimeout(() => { $el.scrollTop = $el.scrollHeight }, 50) })">
                        <template x-for="(msg, i) in activeChat" :key="i">
                            <div class="flex flex-col" :class="msg.sender === 'user' ? 'items-end' : 'items-start'">
                                <span class="text-[9px] text-slate-400 mb-1 px-1 font-bold" x-text="msg.sender === 'user' ? 'User' : 'Bot AI / CS'"></span>
                                <div class="max-w-[85%] px-4 py-2.5 rounded-2xl text-sm shadow-sm" :class="msg.sender === 'user' ? 'bg-blue-500 text-white rounded-tr-sm' : 'bg-white border border-slate-200 text-slate-700 rounded-tl-sm'" x-html="msg.text"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- CHATBOT: KNOWLEDGE BASE SUBTAB -->
        <div x-show="botTab === 'knowledge'" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" x-data="{ showKnowModal: false, showAutoGenerateModal: false, showDeleteModal: false, deleteUrl: '', autoGenTab: 'file', isGenerating: false, isEdit: false, form: { id: '', topic: '', keywords: '', response: '' } }">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 class="text-sm font-bold text-slate-700">Daftar Pengetahuan Bot</h3>
                <div class="flex gap-2">
                    <button @click="showAutoGenerateModal = true" class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg text-xs font-bold shadow-sm transition-colors">Auto Generate (AI)</button>
                    <button @click="isEdit = false; form = {id:'', topic:'Umum', keywords:'', response:''}; showKnowModal = true" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-xs font-bold shadow-sm transition-colors">+ Tambah Respon</button>
                </div>
            </div>
            
            <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-100 text-slate-500 font-semibold sticky top-0 shadow-sm">
                        <tr>
                            <th class="px-6 py-3 whitespace-nowrap">Kategori / Topik</th>
                            <th class="px-6 py-3 whitespace-nowrap">Kata Kunci (Keywords)</th>
                            <th class="px-6 py-3 w-[40%]">Balasan Bot</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($chatbotKnowledges as $know)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-bold whitespace-nowrap">{{ $know->topic ?? 'Umum' }}</span><br>
                            </td>
                            <td class="px-6 py-4">
                                @php $kwArr = is_string($know->keywords) ? json_decode($know->keywords, true) : $know->keywords; $kwArr = $kwArr ?? []; @endphp
                                <div class="flex flex-wrap gap-1">
                                    @foreach($kwArr as $kw)
                                        <span class="px-1.5 py-0.5 bg-slate-200 text-slate-700 rounded text-[10px] font-medium">{{ $kw }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs leading-relaxed text-slate-700">{{ Str::limit($know->response, 100) }}</td>
                            <td class="px-6 py-4 text-right space-y-1">
                                <button @click="isEdit = true; form = { id: '{{$know->id}}', topic: '{{$know->topic}}', keywords: '{{ implode(', ', $kwArr) }}', response: `{{$know->response}}` }; showKnowModal = true" class="text-blue-600 hover:text-blue-800 text-xs font-bold px-2 w-full text-right">Edit</button>
                                <button type="button" @click="deleteUrl = '{{ route('knowledge.destroy', $know->id) }}'; showDeleteModal = true" class="text-red-500 hover:text-red-700 text-xs font-bold px-2 w-full text-right">Hapus</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            
            

            <!-- Modal Konfirmasi Hapus -->
            <div x-show="showDeleteModal" style="display: none;" class="fixed inset-0 z-[250] flex items-center justify-center p-4">
                <div x-show="showDeleteModal" x-transition.opacity class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                <div x-show="showDeleteModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" class="relative bg-white rounded-2xl shadow-2xl w-full overflow-hidden p-6 text-center" style="max-width: 400px;">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                        <svg class="h-10 w-10 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-black text-slate-800 mb-2">Hapus Pengetahuan?</h3>
                    <p class="text-sm text-slate-500 mb-6">Tindakan ini tidak dapat dibatalkan. Data pengetahuan ini akan dihapus secara permanen dari sistem.</p>
                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <button @click="showDeleteModal = false" type="button" class="w-full sm:w-auto px-4 py-2 border border-slate-300 rounded-lg text-slate-700 font-bold hover:bg-slate-50 transition-colors">Batal</button>
                        <form :action="deleteUrl" method="POST" class="inline-block m-0 w-full sm:w-auto">
                            @csrf @method('DELETE')
                            <input type="hidden" name="license" value="{{ request('license') }}">
                            <input type="hidden" name="redirect_to" value="/embed/chatbot">
                            <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg font-bold hover:bg-red-700 transition-colors shadow-sm">Ya, Hapus!</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Knowledge -->
            <div x-show="showKnowModal" style="display: none;" class="fixed inset-0 z-[200] flex items-center justify-center p-4">
                <div x-show="showKnowModal" x-transition.opacity class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showKnowModal = false"></div>
                <div x-show="showKnowModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">
                    <form :action="isEdit ? `/knowledge/${form.id}` : '/knowledge'" method="POST" class="flex flex-col h-full max-h-[90vh]">
                        @csrf
                        <input type="hidden" name="_method" :value="isEdit ? 'PUT' : 'POST'">
                        <!-- If we embed, we might want to pass a redirect query param -->
                        <input type="hidden" name="license" value="{{ request('license') }}">
                        <input type="hidden" name="redirect_to" value="/embed/chatbot">
                        
                        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="font-bold text-lg text-slate-800" x-text="isEdit ? '✏️ Edit Pengetahuan' : '✨ Tambah Pengetahuan Baru'"></h3>
                            <button type="button" @click="showKnowModal = false" class="text-slate-400 hover:text-slate-600"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg></button>
                        </div>
                        
                        <div class="p-6 overflow-y-auto space-y-4 bg-slate-50">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Keywords (Koma Dipisahkan)</label>
                                <input type="text" name="keywords" x-model="form.keywords" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="harga, paket, cicilan" required>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Balasan / Respon AI</label>
                                <textarea name="response" x-model="form.response" rows="5" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Untuk informasi harga, kamu bisa cek di menu..." required></textarea>
                            </div>
                        </div>

                        <div class="p-4 border-t border-slate-100 flex justify-end gap-3 bg-white">
                            <button type="button" @click="showKnowModal = false" class="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-lg hover:bg-slate-50 transition-colors">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-blue-500 text-white font-bold rounded-lg hover:bg-blue-600 transition-colors" x-text="isEdit ? 'Simpan Perubahan' : 'Tambahkan'"></button>
                        </div>
                    </form>
                </div>
                </div>


            <!-- Modal Auto Generate -->
            <div x-show="showAutoGenerateModal" style="display: none;" class="fixed inset-0 z-[200] flex items-center justify-center p-4">
                <div x-show="showAutoGenerateModal" x-transition.opacity class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="if(!isGenerating) showAutoGenerateModal = false"></div>
                <div x-show="showAutoGenerateModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">
                    <form action="/knowledge/generate" method="POST" enctype="multipart/form-data" class="flex flex-col h-full max-h-[90vh]" 
                          x-data="{ progress: 0, progressInterval: null }"
                          @submit="
                              isGenerating = true; 
                              progress = 0; 
                              progressInterval = setInterval(() => { 
                                  if(progress < 90) progress += Math.floor(Math.random() * 2) + 1; 
                                  else if(progress < 99) progress += 1; 
                              }, 1500); 
                          ">
                        @csrf
                        <input type="hidden" name="license" value="{{ request('license') }}">
                        <input type="hidden" name="redirect_to" value="/embed/chatbot">

                        <!-- VIEW 1: Form Input -->
                        <div x-show="!isGenerating" class="flex flex-col h-full">
                            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                                <h3 class="font-bold text-lg text-slate-800">Auto Generate Pengetahuan</h3>
                                <button type="button" @click="showAutoGenerateModal = false" class="text-slate-400 hover:text-slate-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </div>
                            
                            <div class="p-5 overflow-y-auto space-y-4 bg-slate-50">
                                <div class="flex space-x-2 border-b border-slate-200 mb-4">
                                    <button type="button" @click="autoGenTab = 'file'" :class="autoGenTab === 'file' ? 'border-b-2 border-indigo-500 text-indigo-600 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'" class="px-4 py-2 text-sm transition-colors">Upload File</button>
                                    <button type="button" @click="autoGenTab = 'text'" :class="autoGenTab === 'text' ? 'border-b-2 border-indigo-500 text-indigo-600 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'" class="px-4 py-2 text-sm transition-colors">Teks Bebas</button>
                                </div>
                                
                                <div x-show="autoGenTab === 'file'" class="space-y-3">
                                    <label class="block text-sm font-bold text-slate-700">Pilih Dokumen PDF / Word</label>
                                    <label 
                                        x-data="{ isDropping: false, fileName: '' }"
                                        @dragover.prevent="isDropping = true"
                                        @dragleave.prevent="isDropping = false"
                                        @drop.prevent="isDropping = false; $refs.fileInput.files = $event.dataTransfer.files; fileName = $event.dataTransfer.files[0]?.name"
                                        :class="isDropping ? 'border-indigo-500 bg-indigo-50' : 'border-slate-300 bg-white'"
                                        class="mt-1 flex justify-center px-4 py-3 border-2 border-dashed rounded-xl cursor-pointer hover:border-indigo-400 hover:bg-slate-50 transition-colors">
                                        <div class="w-full text-center pointer-events-none flex flex-col items-center">
                                            <template x-if="!fileName">
                                                <div class="flex flex-col items-center w-full py-2">
                                                    <svg class="mx-auto h-8 w-8 text-slate-400 flex-shrink-0" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                    <p class="text-sm font-bold text-indigo-600 mt-2">Pilih File / Drag & Drop</p>
                                                    <p class="text-xs text-slate-500 mt-1">PDF / DOCX Max 5MB</p>
                                                </div>
                                            </template>

                                            <template x-if="fileName">
                                                <div class="flex flex-col items-center bg-emerald-50 rounded-xl p-3 border border-emerald-200 w-full">
                                                    <svg class="w-8 h-8 text-emerald-600 flex-shrink-0 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    <p class="text-sm font-bold text-emerald-800 truncate w-full max-w-[200px]" x-text="fileName"></p>
                                                    <p class="text-[10px] text-emerald-600 mt-1">File siap diproses (Klik untuk ganti)</p>
                                                </div>
                                            </template>
                                            <input x-ref="fileInput" name="document" type="file" class="sr-only pointer-events-auto" accept=".pdf,.docx" @change="fileName = $event.target.files[0]?.name">
                                        </div>
                                    </label>
                                </div>
                                
                                <div x-show="autoGenTab === 'text'" class="space-y-3">
                                    <label class="block text-sm font-bold text-slate-700">Masukkan Teks Pengetahuan</label>
                                    <textarea name="raw_text" rows="6" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm bg-white" placeholder="Paste deskripsi atau artikel di sini..."></textarea>
                                </div>
                            </div>

                            <div class="p-4 border-t border-slate-100 flex justify-end gap-3 bg-white">
                                <button type="button" @click="showAutoGenerateModal = false" class="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-lg hover:bg-slate-50 transition-colors">Batal</button>
                                <button type="submit" class="px-5 py-2 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                                    Generate Sekarang
                                </button>
                            </div>
                        </div>

                        <!-- VIEW 2: Progress Modal -->
                        <div x-show="isGenerating" style="display: none;" class="flex flex-col items-center justify-center p-8 text-center min-h-[350px] bg-white rounded-2xl">
                            
                            <h3 class="text-xl font-black text-slate-800 mb-6">Sistem AI Sedang Bekerja</h3>
                            
                            <!-- Stepper UI -->
                            <div class="flex flex-col gap-5 text-left w-full max-w-sm mx-auto">
                                <!-- Step 1 -->
                                <div class="flex items-start gap-4">
                                    <div class="mt-0.5">
                                        <svg x-show="progress >= 10" class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <svg x-show="progress < 10" class="w-6 h-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="currentColor"><circle cx="12" cy="2.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin="0s" repeatCount="indefinite"/></circle><circle cx="16.75" cy="3.77" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".083s" repeatCount="indefinite"/></circle><circle cx="20.23" cy="8.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".166s" repeatCount="indefinite"/></circle><circle cx="21.5" cy="12" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".25s" repeatCount="indefinite"/></circle><circle cx="20.23" cy="15.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".333s" repeatCount="indefinite"/></circle><circle cx="16.75" cy="20.23" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".416s" repeatCount="indefinite"/></circle><circle cx="12" cy="21.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".5s" repeatCount="indefinite"/></circle><circle cx="7.25" cy="20.23" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".583s" repeatCount="indefinite"/></circle><circle cx="3.77" cy="15.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".666s" repeatCount="indefinite"/></circle><circle cx="2.5" cy="12" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".75s" repeatCount="indefinite"/></circle><circle cx="3.77" cy="8.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".833s" repeatCount="indefinite"/></circle><circle cx="7.25" cy="3.77" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".916s" repeatCount="indefinite"/></circle></g></svg>
                                    </div>
                                    <div class="flex-1">
                                        <h4 :class="progress >= 10 ? 'text-green-600 font-bold' : 'text-indigo-600 font-bold'">Mengunggah & Membaca Dokumen</h4>
                                    </div>
                                </div>
                                
                                <!-- Step 2 -->
                                <div class="flex items-start gap-4" :class="progress < 10 ? 'opacity-40' : ''">
                                    <div class="mt-0.5">
                                        <svg x-show="progress >= 40" class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <svg x-show="progress >= 10 && progress < 40" class="w-6 h-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="currentColor"><circle cx="12" cy="2.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin="0s" repeatCount="indefinite"/></circle><circle cx="16.75" cy="3.77" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".083s" repeatCount="indefinite"/></circle><circle cx="20.23" cy="8.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".166s" repeatCount="indefinite"/></circle><circle cx="21.5" cy="12" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".25s" repeatCount="indefinite"/></circle><circle cx="20.23" cy="15.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".333s" repeatCount="indefinite"/></circle><circle cx="16.75" cy="20.23" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".416s" repeatCount="indefinite"/></circle><circle cx="12" cy="21.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".5s" repeatCount="indefinite"/></circle><circle cx="7.25" cy="20.23" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".583s" repeatCount="indefinite"/></circle><circle cx="3.77" cy="15.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".666s" repeatCount="indefinite"/></circle><circle cx="2.5" cy="12" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".75s" repeatCount="indefinite"/></circle><circle cx="3.77" cy="8.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".833s" repeatCount="indefinite"/></circle><circle cx="7.25" cy="3.77" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".916s" repeatCount="indefinite"/></circle></g></svg>
                                        <div x-show="progress < 10" class="w-6 h-6 rounded-full border-2 border-slate-200"></div>
                                    </div>
                                    <div class="flex-1">
                                        <h4 :class="progress >= 40 ? 'text-green-600 font-bold' : (progress >= 10 ? 'text-indigo-600 font-bold' : 'text-slate-500 font-medium')">Menganalisis Struktur Pengetahuan</h4>
                                    </div>
                                </div>

                                <!-- Step 3 -->
                                <div class="flex items-start gap-4" :class="progress < 40 ? 'opacity-40' : ''">
                                    <div class="mt-0.5">
                                        <svg x-show="progress >= 99" class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <svg x-show="progress >= 40 && progress < 99" class="w-6 h-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="currentColor"><circle cx="12" cy="2.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin="0s" repeatCount="indefinite"/></circle><circle cx="16.75" cy="3.77" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".083s" repeatCount="indefinite"/></circle><circle cx="20.23" cy="8.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".166s" repeatCount="indefinite"/></circle><circle cx="21.5" cy="12" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".25s" repeatCount="indefinite"/></circle><circle cx="20.23" cy="15.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".333s" repeatCount="indefinite"/></circle><circle cx="16.75" cy="20.23" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".416s" repeatCount="indefinite"/></circle><circle cx="12" cy="21.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".5s" repeatCount="indefinite"/></circle><circle cx="7.25" cy="20.23" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".583s" repeatCount="indefinite"/></circle><circle cx="3.77" cy="15.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".666s" repeatCount="indefinite"/></circle><circle cx="2.5" cy="12" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".75s" repeatCount="indefinite"/></circle><circle cx="3.77" cy="8.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".833s" repeatCount="indefinite"/></circle><circle cx="7.25" cy="3.77" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".916s" repeatCount="indefinite"/></circle></g></svg>
                                        <div x-show="progress < 40" class="w-6 h-6 rounded-full border-2 border-slate-200"></div>
                                    </div>
                                    <div class="flex-1">
                                        <h4 :class="progress >= 99 ? 'text-green-600 font-bold' : (progress >= 40 ? 'text-indigo-600 font-bold' : 'text-slate-500 font-medium')">Mengekstrak Keyword & Menyusun SOP</h4>
                                        <p x-show="progress >= 40" x-transition class="text-xs text-slate-500 mt-1">Proses intensif AI memakan waktu 3 - 10 menit. Mohon tidak menutup halaman ini.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
</form>
                </div>
            </div>
            </div>
        

        <style>
        @keyframes custom-ai-spin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
        </style>
        <!-- Floating AI Progress Toast (Global - Outside Tabs) -->
        <div x-data="{
                aiJob: { status: null, progress: 0, count: 0 },
                pollInterval: null,
                showCancelModal: false,
                isSubmitting: false,
                checkStatus() {
                    fetch('{{ route('knowledge.job-status') }}?license={{ request('license') }}')
                        .then(res => res.json())
                        .then(data => {
                            if (data.status) {
                                this.aiJob.status = data.status;
                                this.aiJob.count = data.count || 0;
                                if (data.status === 'processing') {
                                    if (this.aiJob.progress < 99) { this.aiJob.progress += (99 - this.aiJob.progress) * 0.03; }
                                } else if (data.status === 'completed') {
                                    this.aiJob.progress = 100;
                                    clearInterval(this.pollInterval);
                                    this.showCancelModal = false;
                                    // Batal auto reload, tunggu user konfirmasi
                                } else if (data.status === 'failed') {
                                    this.aiJob.status = 'failed';
                                    this.aiJob.progress = 0;
                                    clearInterval(this.pollInterval);
                                } else if (data.status === 'cancelled') {
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
                    fetch('{{ route('knowledge.job-cancel') }}?license={{ request('license') }}', {
                        method: 'POST',
                        headers: { 
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    }).then(() => { 
                        this.aiJob.status = null; 
                        this.showCancelModal = false;
                        if(this.pollInterval) clearInterval(this.pollInterval);
                    });
                },
                acceptJob() {
                    if (this.isSubmitting) return;
                    this.isSubmitting = true;
                    fetch('{{ route('knowledge.job-accept') }}?license={{ request('license') }}', {
                        method: 'POST',
                        headers: { 
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    }).then(() => {
                        let url = new URL(window.location.href);
                        url.searchParams.set('tab', 'knowledge');
                        window.location.href = url.toString();
                    });
                },
                rejectJob() {
                    if (this.isSubmitting) return;
                    this.isSubmitting = true;
                    fetch('{{ route('knowledge.job-reject') }}?license={{ request('license') }}', {
                        method: 'POST',
                        headers: { 
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    }).then(() => {
                        let url = new URL(window.location.href);
                        url.searchParams.set('tab', 'knowledge');
                        window.location.href = url.toString();
                    });
                }
            }"
            x-init="initPoll()"
            x-show="aiJob.status === 'processing' || aiJob.status === 'completed' || aiJob.status === 'failed'"
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
                            <template x-if="aiJob.status === 'failed'">
                                <div style="position: relative; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; border-radius: 9999px; background: #fee2e2;">
                                    <svg style="width: 14px; height: 14px; color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </div>
                            </template>
                            <span style="font-size: 14px; font-weight: 600; color: #334155;" x-text="aiJob.status === 'completed' ? 'Proses Selesai!' : (aiJob.status === 'failed' ? 'Proses Gagal!' : 'AI mengekstrak data...')"></span>
                        </div>
                        <template x-if="aiJob.status === 'processing'">
                            <button @click="showCancelModal = true" type="button" style="font-size: 11px; color: #dc2626; font-weight: 500; padding: 4px 8px; border-radius: 6px; background: #fef2f2; border: 1px solid #fee2e2; cursor: pointer;">
                                Batal
                            </button>
                        </template>
                        <template x-if="aiJob.status === 'failed'">
                            <button @click="aiJob.status = null" type="button" style="font-size: 11px; color: #475569; font-weight: 500; padding: 4px 8px; border-radius: 6px; background: #f1f5f9; border: 1px solid #e2e8f0; cursor: pointer;">
                                Tutup
                            </button>
                        </template>
                    </div>
                    
                    <template x-if="aiJob.status === 'completed'">
                        <div style="margin-top: 16px; margin-bottom: 8px;">
                            <p style="font-size: 13px; color: #475569; margin-bottom: 12px; line-height: 1.4;">Berhasil menghasilkan <strong style="color: #0f172a;" x-text="aiJob.count + ' pengetahuan bot'"></strong>. Apakah Anda ingin menyimpan hasilnya?</p>
                            <div style="display: flex; gap: 8px;">
                                <button @click="acceptJob()" :disabled="isSubmitting" style="flex: 1; padding: 8px 0; background: #10b981; color: white; font-size: 13px; font-weight: 600; border-radius: 8px; border: none; cursor: pointer; transition: opacity 0.2s;" :style="isSubmitting ? 'opacity: 0.6;' : ''">Terima & Simpan</button>
                                <button @click="rejectJob()" :disabled="isSubmitting" style="flex: 1; padding: 8px 0; background: #f1f5f9; color: #dc2626; font-size: 13px; font-weight: 600; border-radius: 8px; border: 1px solid #e2e8f0; cursor: pointer; transition: opacity 0.2s;" :style="isSubmitting ? 'opacity: 0.6;' : ''">Tolak & Hapus</button>
                            </div>
                        </div>
                    </template>
                    
                    <template x-if="aiJob.status !== 'completed'">
                        <div>
                            <div style="width: 100%; background: #f1f5f9; border-radius: 9999px; height: 8px; margin-bottom: 8px; overflow: hidden; box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);">
                                <div style="height: 8px; border-radius: 9999px; transition: all 700ms ease-out;"
                                     :style="{ backgroundColor: aiJob.status === 'failed' ? '#ef4444' : '#4f46e5', width: aiJob.progress + '%' }">
                                </div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #64748b;">
                                <span x-text="aiJob.status === 'failed' ? 'Terjadi kesalahan saat memproses.' : 'Berjalan di latar belakang'"></span>
                                <span style="font-weight: 700; color: #334155;" x-text="Math.floor(aiJob.progress) + '%'"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
        
    </div>

</x-embed-layout>
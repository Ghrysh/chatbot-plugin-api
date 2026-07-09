<x-embed-layout>
    <div class="p-6 w-full h-full bg-slate-50" x-data="{ botTab: 'leads' }">
        <div class="mb-6 flex flex-col md:flex-row justify-between md:items-end gap-4">
            <h2 class="text-xl font-bold text-slate-800">Manajemen Chatbot</h2>
            <div class="flex flex-col sm:flex-row bg-white shadow-sm p-1 rounded-xl w-full md:w-fit gap-1 border border-slate-200">
                <button @click="botTab = 'leads'" class="w-full sm:w-auto px-4 py-2 rounded-lg text-sm font-semibold transition-all" :class="botTab === 'leads' ? 'bg-teal-50 text-teal-700' : 'text-slate-500 hover:text-slate-700'">Inbox Follow Up</button>
                <button @click="botTab = 'knowledge'" class="w-full sm:w-auto px-4 py-2 rounded-lg text-sm font-semibold transition-all" :class="botTab === 'knowledge' ? 'bg-teal-50 text-teal-700' : 'text-slate-500 hover:text-slate-700'">Latih Otak Bot</button>
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
                                @if($lead->contact_info === '-' || empty($lead->contact_info))
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 text-blue-600 font-bold text-xs rounded-lg border border-blue-200">
                                        <span class="w-2 h-2 rounded-full bg-blue-500 animate-ping"></span> Chat Masih Aktif
                                    </span>
                                @else
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 text-emerald-700 font-bold text-xs rounded-lg border border-emerald-200 mb-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg> Chat Diakhiri
                                    </div><br>
                                    <span class="text-xs font-bold text-slate-700">Follow up via: <span class="text-indigo-600">{{ $lead->contact_info }}</span></span>
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
                                <div class="max-w-[85%] px-4 py-2.5 rounded-2xl text-sm shadow-sm" :class="msg.sender === 'user' ? 'bg-indigo-500 text-white rounded-tr-sm' : 'bg-white border border-slate-200 text-slate-700 rounded-tl-sm'" x-html="msg.text"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- CHATBOT: KNOWLEDGE BASE SUBTAB -->
        <div x-show="botTab === 'knowledge'" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" x-data="{ showKnowModal: false, isEdit: false, form: { id: '', topic: '', keywords: '', response: '' } }">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 class="text-sm font-bold text-slate-700">Daftar Pengetahuan Bot</h3>
                <button @click="isEdit = false; form = {id:'', topic:'Umum', keywords:'', response:''}; showKnowModal = true" class="bg-teal-500 hover:bg-teal-600 text-white px-4 py-2 rounded-lg text-xs font-bold shadow-sm transition-colors">+ Tambah Respon</button>
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
                                <span class="px-2 py-1 bg-indigo-50 text-indigo-700 rounded text-xs font-bold whitespace-nowrap">{{ $know->topic ?? 'Umum' }}</span><br>
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
                                <button @click="isEdit = true; form = { id: '{{$know->id}}', topic: '{{$know->topic}}', keywords: '{{ implode(', ', $kwArr) }}', response: `{{$know->response}}` }; showKnowModal = true" class="text-teal-600 hover:text-teal-800 text-xs font-bold px-2 w-full text-right">Edit</button>
                                <form action="{{ route('knowledge.destroy', $know->id) }}" method="POST" onsubmit="return confirm('Hapus respon bot ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-bold px-2 w-full text-right">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Modal Knowledge -->
            <div x-show="showKnowModal" style="display: none;" class="fixed inset-0 z-[200] flex items-center justify-center p-4">
                <div x-show="showKnowModal" x-transition.opacity class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showKnowModal = false"></div>
                <div x-show="showKnowModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">
                    <form :action="isEdit ? `/knowledge/${form.id}` : '/knowledge'" method="POST" class="flex flex-col h-full max-h-[90vh]">
                        @csrf
                        <input type="hidden" name="_method" :value="isEdit ? 'PUT' : 'POST'">
                        <!-- If we embed, we might want to pass a redirect query param -->
                        <input type="hidden" name="redirect_to" value="/embed/chatbot">
                        
                        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="font-bold text-lg text-slate-800" x-text="isEdit ? '✏️ Edit Pengetahuan' : '✨ Tambah Pengetahuan Baru'"></h3>
                            <button type="button" @click="showKnowModal = false" class="text-slate-400 hover:text-slate-600"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg></button>
                        </div>
                        
                        <div class="p-6 overflow-y-auto space-y-4 bg-slate-50">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Keywords (Koma Dipisahkan)</label>
                                <input type="text" name="keywords" x-model="form.keywords" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm" placeholder="harga, paket, cicilan" required>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Balasan / Respon AI</label>
                                <textarea name="response" x-model="form.response" rows="5" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm" placeholder="Untuk informasi harga, kamu bisa cek di menu..." required></textarea>
                            </div>
                        </div>

                        <div class="p-4 border-t border-slate-100 flex justify-end gap-3 bg-white">
                            <button type="button" @click="showKnowModal = false" class="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-lg hover:bg-slate-50 transition-colors">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-teal-500 text-white font-bold rounded-lg hover:bg-teal-600 transition-colors" x-text="isEdit ? 'Simpan Perubahan' : 'Tambahkan'"></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-embed-layout>

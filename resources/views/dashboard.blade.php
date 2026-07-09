<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    @php
        $initialTab = request('active_tab', session('active_tab', 'chatbot'));
    @endphp

    <div class="max-w-[100rem] mx-auto w-full px-4 sm:px-6 lg:px-8 py-8" 
         x-data="{ 
            activeTab: '{{ $initialTab }}',
            init() {
                let savedTab = localStorage.getItem('adminActiveTab');
                if (savedTab && !window.location.search.includes('active_tab')) {
                    this.activeTab = savedTab;
                }
                this.$watch('activeTab', value => {
                    localStorage.setItem('adminActiveTab', value);
                });
            }
         }">

        @if(session('success'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-init="setTimeout(() => show = false, 5000)"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-4"
             class="mb-6 flex items-center justify-between p-4 bg-teal-50 border border-teal-200 text-teal-700 rounded-2xl shadow-sm shadow-teal-100">
            
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-teal-500 text-white flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </div>
                <p class="font-bold text-sm md:text-base">{{ session('success') }}</p>
            </div>

            <button @click="show = false" class="text-teal-400 hover:text-teal-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        @endif
        
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-900 mb-6">Admin Dashboard</h1>
            
            <div class="flex space-x-2 border-b border-slate-200 pb-2 overflow-x-auto no-scrollbar">
                <button @click="activeTab = 'chatbot'" 
                    :class="activeTab === 'chatbot' ? 'bg-teal-500 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-100'"
                    class="px-5 py-2 rounded-full text-sm font-semibold transition-all duration-200 whitespace-nowrap">
                    Chatbot
                </button>
                <button @click="activeTab = 'livechat'" :class="activeTab === 'livechat' ? 'bg-teal-500 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100'" class="px-5 py-2 rounded-full text-sm font-semibold transition-all">
                    Live Chat (CS)
                </button>
            </div>
        </div>

        <!-- ==================== CHATBOT TAB ==================== -->
        <div x-show="activeTab === 'chatbot'" style="display: none;" x-transition.opacity.duration.300ms x-data="{ botTab: 'leads' }">
            
            <div class="mb-6 flex flex-col md:flex-row justify-between md:items-end gap-4">
                <div class="flex flex-col sm:flex-row bg-slate-100 p-1 rounded-xl w-full md:w-fit gap-1">
                    <button @click="botTab = 'leads'" class="w-full sm:w-auto px-4 py-2 rounded-lg text-sm font-semibold transition-all" :class="botTab === 'leads' ? 'bg-white shadow-sm text-teal-600' : 'text-slate-500 hover:text-slate-700'">Inbox Follow Up</button>
                    <button @click="botTab = 'knowledge'" class="w-full sm:w-auto px-4 py-2 rounded-lg text-sm font-semibold transition-all" :class="botTab === 'knowledge' ? 'bg-white shadow-sm text-teal-600' : 'text-slate-500 hover:text-slate-700'">Latih Otak Bot</button>
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
                    {{ $chatbotLeads->appends(['active_tab' => 'chatbot', 'leads_page' => request('leads_page')])->links() }}
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

        <!-- ==================== LIVE CHAT TAB ==================== -->
        <div x-show="activeTab === 'livechat'" style="display: none;" 
             x-data="{ 
                pendingChats: [], activeChats: [], endedChats: [], currentChat: null, inputText: '',
                notifEnabled: false, unreadChats: {},
                showHistory: false,
                
                initLive() {
                    if (Notification.permission === 'granted') this.notifEnabled = true;
                    setInterval(() => this.pollData(), 3000);
                    this.pollData();
                },
                
                enableNotif() {
                    Notification.requestPermission().then(perm => { if (perm === 'granted') this.notifEnabled = true; });
                    this.playPing();
                },

                getLastMsg(historyStr, defaultTopic) {
                    let h = JSON.parse(historyStr || '[]');
                    if(h.length > 0) {
                        let text = h[h.length - 1].text;
                        return text ? text.replace(/(<([^>]+)>)/gi, '') : defaultTopic;
                    }
                    return defaultTopic;
                },
                
                async pollData() {
                    try {
                        let res = await fetch('/livechat/poll');
                        let data = await res.json();
                        
                        if (data.pending.length > this.pendingChats.length) {
                            this.playPing();
                            if (this.notifEnabled && Notification.permission === 'granted') {
                                let notif = new Notification('💬 Live Chat Baru!', { body: 'Ada user yang menunggu.', icon: '/favicon.ico' });
                                notif.onclick = function() { window.focus(); };
                            }
                        }
                        
                        data.active.forEach(act => {
                            let oldActive = this.activeChats.find(c => c.id === act.id);
                            let oldLen = oldActive ? JSON.parse(oldActive.chat_history || '[]').length : 0;
                            let newLen = JSON.parse(act.chat_history || '[]').length;
                            
                            if(newLen > oldLen) {
                                if(this.currentChat?.id === act.id) {
                                    this.playPing(); setTimeout(() => { this.scrollDown() }, 100);
                                } else {
                                    this.unreadChats[act.id] = true;
                                    this.playPing();
                                }
                            }
                        });
                        
                        this.pendingChats = data.pending;
                        this.activeChats = data.active;
                        this.endedChats = data.ended || [];
                        
                        if(this.currentChat) {
                            this.currentChat = this.activeChats.find(c => c.id === this.currentChat.id) || 
                                               this.endedChats.find(c => c.id === this.currentChat.id) || null;
                        }
                    } catch(e) {}
                },

                openChat(chat) {
                    this.unreadChats[chat.id] = false;
                    this.currentChat = chat;
                    setTimeout(()=>this.scrollDown(), 100);
                },

                async actionChat(id, action) {
                    try {
                        await fetch('/livechat/action', {
                            method: 'POST', 
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                            body: JSON.stringify({ lead_id: id, action: action })
                        });
                        if(action === 'end' && this.currentChat) {
                            this.currentChat.live_chat_status = 'ended'; 
                        }
                        this.pollData();
                    } catch(e) {}
                },

                async sendMessage() {
                    if(!this.inputText.trim() || !this.currentChat) return;
                    let msgText = this.inputText; this.inputText = '';
                    
                    let history = JSON.parse(this.currentChat.chat_history || '[]');
                    history.push({ sender: 'admin', text: msgText, time: new Date().toLocaleTimeString('id-ID', {day: 'numeric', month: 'short', hour: '2-digit', minute:'2-digit'}) });
                    this.currentChat.chat_history = JSON.stringify(history);
                    this.scrollDown();
                    
                    try {
                        await fetch('/livechat/send', {
                            method: 'POST', 
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                            body: JSON.stringify({ lead_id: this.currentChat.id, message: msgText })
                        });
                        this.pollData();
                    } catch(e) {}
                },

                playPing() { try { let ctx = new (window.AudioContext || window.webkitAudioContext)(); let osc = ctx.createOscillator(); let gain = ctx.createGain(); osc.connect(gain); gain.connect(ctx.destination); osc.type = 'sine'; osc.frequency.setValueAtTime(800, ctx.currentTime); osc.frequency.exponentialRampToValueAtTime(1200, ctx.currentTime + 0.1); gain.gain.setValueAtTime(0, ctx.currentTime); gain.gain.linearRampToValueAtTime(0.3, ctx.currentTime + 0.02); gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5); osc.start(ctx.currentTime); osc.stop(ctx.currentTime + 0.5); } catch(e){} },
                scrollDown() { setTimeout(() => { let el = document.getElementById('live-chat-box'); if(el) el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' }); }, 100); }
             }" 
             x-init="initLive()">
            
            <div x-show="!notifEnabled" class="bg-indigo-50 border border-indigo-200 p-4 rounded-xl mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-indigo-100 rounded-full text-indigo-500"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg></div>
                    <div><h4 class="font-bold text-slate-800">Aktifkan Notifikasi</h4><p class="text-xs text-slate-500">Izinkan browser untuk memunculkan suara saat pesan masuk.</p></div>
                </div>
                <button @click="enableNotif()" class="whitespace-nowrap bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg text-xs font-bold transition-all">Izinkan Sekarang</button>
            </div>

            <div class="flex flex-col md:flex-row gap-6 h-[70vh]">
                <div class="w-full md:w-1/3 bg-white rounded-xl border border-slate-200 shadow-sm flex flex-col overflow-hidden">
                    
                    <div class="shrink-0 border-b border-slate-100">
                        <button @click="showHistory = !showHistory" class="w-full p-4 bg-slate-100/50 hover:bg-slate-100 flex justify-between items-center transition-colors">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <h3 class="font-bold text-slate-600 text-xs uppercase tracking-wider">Riwayat Obrolan</h3>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 transition-transform duration-300" :class="showHistory ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        
                        <div x-show="showHistory" x-transition.opacity style="display: none;" class="overflow-y-auto max-h-[30vh] bg-slate-50/30 border-t border-slate-100">
                            <template x-for="chat in endedChats" :key="chat.id">
                                <div @click="openChat(chat)" class="p-3 border-b border-slate-100 cursor-pointer hover:bg-white transition-colors" :class="currentChat?.id === chat.id ? 'bg-white border-l-4 border-slate-400' : ''">
                                    <p class="text-sm font-bold text-slate-500 truncate">
                                        <span x-show="chat.user" x-text="chat.user?.name"></span>
                                        <span x-show="!chat.user">Guest (<span x-text="chat.ip_address"></span>)</span>
                                    </p>
                                    <p class="text-[10px] text-slate-400 truncate mt-0.5" x-text="getLastMsg(chat.chat_history, 'Selesai')"></p>
                                </div>
                            </template>
                            <div x-show="endedChats.length === 0" class="p-4 text-center text-[10px] text-slate-400 italic">Belum ada riwayat.</div>
                        </div>
                    </div>

                    <div class="p-4 bg-slate-50 border-b border-slate-100 flex justify-between items-center shrink-0">
                        <h3 class="font-bold text-slate-800">Menunggu (Pending)</h3>
                        <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full" x-show="pendingChats.length > 0" x-text="pendingChats.length"></span>
                    </div>
                    <div class="overflow-y-auto max-h-[25vh] shrink-0">
                        <template x-for="chat in pendingChats" :key="chat.id">
                            <div class="p-3 border-b border-slate-100 bg-amber-50/30">
                                <p class="text-xs font-bold text-slate-800 mb-1">
                                    <span x-show="chat.user" x-text="chat.user?.name"></span>
                                    <span x-show="!chat.user">Guest (<span x-text="chat.ip_address"></span>)</span>
                                </p>
                                <p class="text-[10px] text-slate-500 mb-2 truncate italic" x-text="getLastMsg(chat.chat_history, chat.topic_context)"></p>
                                <div class="flex gap-2">
                                    <button @click="actionChat(chat.id, 'accept')" class="flex-1 bg-teal-500 hover:bg-teal-600 text-white text-[10px] font-bold py-1.5 rounded transition-colors">Terima</button>
                                    <button @click="actionChat(chat.id, 'reject')" class="flex-1 bg-slate-200 hover:bg-red-500 hover:text-white text-slate-600 text-[10px] font-bold py-1.5 rounded transition-colors">Tolak</button>
                                </div>
                            </div>
                        </template>
                    </div>
                    
                    <div class="p-4 bg-slate-50 border-y border-slate-100 shrink-0">
                        <h3 class="font-bold text-slate-800">Obrolan Aktif</h3>
                    </div>
                    <div class="overflow-y-auto flex-1 min-h-[100px]">
                        <template x-for="chat in activeChats" :key="chat.id">
                            <div @click="openChat(chat)" class="p-3 border-b border-slate-100 cursor-pointer hover:bg-slate-50 transition-colors relative" :class="currentChat?.id === chat.id ? 'bg-indigo-50 border-l-4 border-indigo-500' : ''">
                                <div x-show="unreadChats[chat.id] && currentChat?.id !== chat.id" class="absolute right-3 top-3 w-2.5 h-2.5 bg-red-500 rounded-full animate-ping shadow-[0_0_8px_rgba(239,68,68,0.8)]"></div>
                                <div x-show="unreadChats[chat.id] && currentChat?.id !== chat.id" class="absolute right-3 top-3 w-2.5 h-2.5 bg-red-500 rounded-full"></div>
                                
                                <p class="text-sm font-bold text-slate-800 pr-4 truncate">
                                    <span x-show="chat.user" x-text="chat.user?.name"></span>
                                    <span x-show="!chat.user">Guest (<span x-text="chat.ip_address"></span>)</span>
                                </p>
                                <p class="text-xs text-slate-500 truncate mt-0.5" x-text="getLastMsg(chat.chat_history, chat.topic_context)"></p>
                            </div>
                        </template>
                    </div>
                    
                </div>
                
                <div class="w-full md:w-2/3 bg-white rounded-xl border border-slate-200 shadow-sm flex flex-col">
                    <template x-if="!currentChat">
                        <div class="flex-1 flex flex-col items-center justify-center text-slate-400">
                            <svg class="w-16 h-16 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                            <p>Pilih obrolan dari daftar untuk mulai membalas.</p>
                        </div>
                    </template>
                    
                    <template x-if="currentChat">
                        <div class="flex flex-col h-full">
                            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                                <div>
                                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                        <span x-show="currentChat.user" x-text="currentChat.user?.name"></span>
                                        <span x-show="!currentChat.user">Guest (<span x-text="currentChat.ip_address"></span>)</span>
                                        <span x-show="currentChat.live_chat_status === 'active'" class="px-2 py-0.5 bg-green-100 text-green-700 text-[9px] uppercase rounded-full">Online</span>
                                    </h3>
                                    <p class="text-[10px] text-slate-500 font-mono mt-0.5">Session ID: <span x-text="currentChat.id"></span></p>
                                </div>
                                <button x-show="currentChat.live_chat_status === 'active'" @click="actionChat(currentChat.id, 'end')" class="text-xs bg-red-100 text-red-600 font-bold px-3 py-1.5 rounded-lg hover:bg-red-200 transition-colors">Akhiri Sesi</button>
                            </div>
                            
                            <div id="live-chat-box" class="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50/50">
                                <template x-for="(msg, i) in JSON.parse(currentChat.chat_history || '[]')" :key="i">
                                    <div class="flex flex-col" :class="(msg.sender === 'admin' || msg.sender === 'bot') ? 'items-end' : 'items-start'">
                                        <div class="flex items-baseline gap-1.5 mb-0.5 px-1" :class="(msg.sender === 'admin' || msg.sender === 'bot') ? 'flex-row-reverse' : ''">
                                            <span class="text-[9px] text-slate-500 font-bold" x-text="msg.sender.toUpperCase()"></span>
                                            <span class="text-[8px] text-slate-400" x-show="msg.time" x-text="msg.time"></span>
                                        </div>
                                        <div class="max-w-[80%] px-3 py-2 rounded-xl text-sm shadow-sm" :class="(msg.sender === 'admin' || msg.sender === 'bot') ? 'bg-indigo-500 text-white rounded-tr-sm' : 'bg-white border border-slate-200 text-slate-700 rounded-tl-sm'" x-html="msg.text"></div>
                                    </div>
                                </template>
                            </div>
                            
                            <form x-show="currentChat.live_chat_status === 'active'" @submit.prevent="sendMessage()" class="p-3 bg-white border-t border-slate-100 flex gap-2">
                                <input type="text" x-model="inputText" placeholder="Ketik balasan CS di sini..." class="flex-1 px-4 py-2.5 bg-slate-100 border-transparent rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm transition-all">
                                <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg font-bold text-sm hover:bg-indigo-700 transition-colors">Kirim</button>
                            </form>
    
                            <div x-show="currentChat.live_chat_status === 'ended'" class="p-4 bg-slate-100 text-center text-xs font-bold text-slate-500">
                                Sesi obrolan ini telah berakhir.
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>

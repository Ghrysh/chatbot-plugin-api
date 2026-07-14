<x-embed-layout>
    <div class="h-[calc(100vh)] w-full flex flex-col md:flex-row bg-slate-50" x-data="liveChatAdmin()">
        
        <!-- Kolom Kiri: Sidebar Daftar Chat (Lebar tetap di desktop) -->
        <div class="w-full md:w-80 lg:w-96 bg-white border-r border-slate-200 flex flex-col h-[50vh] md:h-full shrink-0 shadow-[4px_0_24px_rgba(0,0,0,0.02)] z-10 relative">
            <div class="p-5 border-b border-slate-100 bg-white/80 backdrop-blur-md sticky top-0 z-20">
                <h2 class="text-xl font-black text-slate-800 tracking-tight flex items-center gap-2">
                    Live Chat CS 
                    <span class="flex h-2.5 w-2.5 relative ml-1">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                    </span>
                </h2>
                <div class="flex gap-2 mt-4 bg-slate-100/80 p-1 rounded-xl border border-slate-200/60">
                    <button @click="activeTab = 'pending'" class="flex-1 py-1.5 px-3 text-xs font-bold rounded-lg transition-all" :class="activeTab === 'pending' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                        Menunggu <span x-show="pendingLeads.length" class="ml-1 px-1.5 py-0.5 bg-red-100 text-red-600 rounded-full text-[10px]" x-text="pendingLeads.length"></span>
                    </button>
                    <button @click="activeTab = 'active'" class="flex-1 py-1.5 px-3 text-xs font-bold rounded-lg transition-all" :class="activeTab === 'active' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                        Aktif <span x-show="activeLeads.length" class="ml-1 px-1.5 py-0.5 bg-blue-100 text-blue-600 rounded-full text-[10px]" x-text="activeLeads.length"></span>
                    </button>
                    <button @click="activeTab = 'history'" class="flex-1 py-1.5 px-3 text-xs font-bold rounded-lg transition-all" :class="activeTab === 'history' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                        Riwayat
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-3 space-y-2 bg-slate-50/50">
                <!-- PENDING LEADS -->
                <template x-if="activeTab === 'pending'">
                    <div>
                        <div x-show="pendingLeads.length === 0" class="text-center p-8">
                            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3"><span class="text-2xl">☕</span></div>
                            <p class="text-slate-400 text-sm font-medium">Belum ada chat menunggu.</p>
                        </div>
                        <template x-for="lead in pendingLeads" :key="lead.id">
                            <div class="bg-white p-4 rounded-2xl border border-slate-200 mb-2 shadow-sm hover:border-blue-300 hover:shadow-md transition-all group">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-500 font-bold text-xs">U</div>
                                        <div>
                                            <h4 class="font-bold text-sm text-slate-800" x-text="lead.user ? lead.user.name : 'Guest'"></h4>
                                            <p class="text-[10px] text-slate-400" x-text="'IP: ' + lead.ip_address"></p>
                                        </div>
                                    </div>
                                    <span class="text-[9px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-100 animate-pulse">Waiting</span>
                                </div>
                                <p class="text-xs text-slate-600 bg-slate-50 p-2 rounded-lg mb-3 line-clamp-2 border border-slate-100" x-text="lead.topic_context"></p>
                                <button @click="actionChat(lead.id, 'accept')" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 rounded-xl transition-colors shadow-sm flex items-center justify-center gap-1.5">
                                    Terima Chat
                                </button>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- ACTIVE LEADS -->
                <template x-if="activeTab === 'active'">
                    <div>
                        <div x-show="activeLeads.length === 0" class="text-center p-8 text-slate-400 text-sm font-medium">Tidak ada chat aktif.</div>
                        <template x-for="lead in activeLeads" :key="lead.id">
                            <div @click="selectChat(lead)" class="bg-white p-3 rounded-2xl border mb-2 cursor-pointer transition-all flex items-center gap-3 hover:bg-blue-50" :class="currentChat && currentChat.id === lead.id ? 'border-blue-400 shadow-sm bg-blue-50/30' : 'border-slate-200 hover:border-blue-300'">
                                <div class="relative">
                                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">U</div>
                                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-baseline mb-0.5">
                                        <h4 class="font-bold text-sm text-slate-800 truncate" x-text="lead.user ? lead.user.name : 'Guest'"></h4>
                                        <span class="text-[9px] text-slate-400 font-mono" x-text="'#'+lead.id"></span>
                                    </div>
                                    <p class="text-xs text-slate-500 truncate" x-text="lead.topic_context"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- HISTORY LEADS -->
                <template x-if="activeTab === 'history'">
                    <div>
                        <div x-show="endedLeads.length === 0" class="text-center p-8 text-slate-400 text-sm font-medium">Belum ada riwayat.</div>
                        <template x-for="lead in endedLeads" :key="lead.id">
                            <div @click="selectChat(lead)" class="bg-white p-3 rounded-2xl border border-slate-200 mb-2 cursor-pointer transition-all flex items-center gap-3 opacity-75 hover:opacity-100" :class="currentChat && currentChat.id === lead.id ? 'border-slate-400 shadow-sm bg-slate-50' : 'hover:border-slate-300'">
                                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-sm">U</div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-baseline mb-0.5">
                                        <h4 class="font-bold text-sm text-slate-700 truncate" x-text="lead.user ? lead.user.name : 'Guest'"></h4>
                                        <span class="text-[9px] font-bold text-slate-500 bg-slate-200 px-1.5 rounded" x-text="'Ended'"></span>
                                    </div>
                                    <p class="text-[10px] text-slate-400" x-text="lead.updated_at ? new Date(lead.updated_at).toLocaleString() : ''"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <!-- Kolom Kanan: Area Chat -->
        <div class="flex-1 flex flex-col h-[50vh] md:h-full bg-white relative">
            <template x-if="!currentChat">
                <div class="flex-1 flex flex-col items-center justify-center p-8 text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-4 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                    <p class="text-lg font-medium text-slate-500">Pilih obrolan aktif untuk mulai membalas</p>
                    <p class="text-sm mt-1">Pilih dari daftar di sebelah kiri.</p>
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
                                <div class="max-w-[80%] px-3 py-2 rounded-xl text-sm shadow-sm" :class="(msg.sender === 'admin' || msg.sender === 'bot') ? 'bg-blue-500 text-white rounded-tr-sm' : 'bg-white border border-slate-200 text-slate-700 rounded-tl-sm'" x-html="msg.text"></div>
                            </div>
                        </template>
                    </div>
                    
                    <form x-show="currentChat.live_chat_status === 'active'" @submit.prevent="sendMessage()" class="p-3 bg-white border-t border-slate-100 flex gap-2">
                        <input type="text" x-model="inputText" placeholder="Ketik balasan CS di sini..." class="flex-1 px-4 py-2.5 bg-slate-100 border-transparent rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition-all">
                        <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-lg font-bold text-sm hover:bg-blue-700 transition-colors">Kirim</button>
                    </form>
                </div>
            </template>
        </div>

    </div>

    <!-- Script for Live Chat Admin -->
    <script>
        function liveChatAdmin() {
            return {
                activeTab: 'pending', // pending, active, history
                pendingLeads: [],
                activeLeads: [],
                endedLeads: [],
                currentChat: null,
                inputText: '',
                pollTimer: null,
                lastPingTime: 0,
                
                init() {
                    this.fetchLeads();
                    this.startPolling();
                },
                
                async fetchLeads() {
                    try {
                        let res = await fetch('/livechat/poll');
                        let data = await res.json();
                        
                        // Cek apakah ada pending leads baru untuk dibunyikan ping
                        if (data.pending.length > this.pendingLeads.length) {
                            this.playPingSound();
                        }

                        this.pendingLeads = data.pending;
                        this.activeLeads = data.active;
                        this.endedLeads = data.ended;

                        if (this.currentChat) {
                            let updated = [...this.pendingLeads, ...this.activeLeads, ...this.endedLeads].find(l => l.id === this.currentChat.id);
                            if (updated && updated.chat_history !== this.currentChat.chat_history) {
                                this.currentChat = updated;
                                this.scrollToBottom();
                                if(updated.live_chat_status === 'active') this.playPingSound(); // Ping if new message in active chat
                            } else if (updated) {
                                this.currentChat.live_chat_status = updated.live_chat_status;
                            }
                        }
                    } catch(e) {}
                },
                
                startPolling() {
                    this.pollTimer = setInterval(() => { this.fetchLeads(); }, 3000);
                },

                playPingSound() {
                    let now = Date.now();
                    if (now - this.lastPingTime < 2000) return; // limit 1 ping per 2 detik
                    this.lastPingTime = now;

                    try {
                        let ctx = new (window.AudioContext || window.webkitAudioContext)();
                        let osc = ctx.createOscillator();
                        let gain = ctx.createGain();
                        osc.connect(gain); gain.connect(ctx.destination);
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(600, ctx.currentTime);
                        osc.frequency.exponentialRampToValueAtTime(1000, ctx.currentTime + 0.1);
                        gain.gain.setValueAtTime(0, ctx.currentTime);
                        gain.gain.linearRampToValueAtTime(0.3, ctx.currentTime + 0.05);
                        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
                        osc.start(ctx.currentTime); osc.stop(ctx.currentTime + 0.3);
                    } catch(e){}
                },
                
                selectChat(lead) {
                    this.currentChat = lead;
                    this.scrollToBottom();
                },
                
                async actionChat(id, action) {
                    try {
                        let res = await fetch('/livechat/action', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ lead_id: id, action: action })
                        });
                        let data = await res.json();
                        if (data.success) {
                            this.currentChat = null;
                            if (action === 'accept') this.activeTab = 'active';
                            this.fetchLeads();
                        }
                    } catch(e) { alert('Terjadi kesalahan jaringan.'); }
                },
                
                async sendMessage() {
                    if (!this.inputText.trim() || !this.currentChat) return;
                    let msg = this.inputText;
                    this.inputText = '';
                    
                    try {
                        await fetch('/livechat/send', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ lead_id: this.currentChat.id, message: msg })
                        });
                        this.fetchLeads(); // refresh immediately
                    } catch(e) { alert('Gagal mengirim pesan.'); }
                },
                
                scrollToBottom() {
                    setTimeout(() => {
                        let box = document.getElementById('live-chat-box');
                        if (box) box.scrollTop = box.scrollHeight;
                    }, 100);
                }
            };
        }
    </script>
</x-embed-layout>

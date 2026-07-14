(function() {
    // 1. Inject Tailwind & AlpineJS if not present
    if (!window.tailwind) {
        let script = document.createElement('script');
        script.src = 'https://cdn.tailwindcss.com';
        document.head.appendChild(script);
    }
    if (!window.Alpine) {
        let script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js';
        script.defer = true;
        document.head.appendChild(script);
    }

    // 2. Base URL of our SaaS Backend
    // In production, this would be dynamically set or hardcoded to the actual SaaS domain
    const SAAS_URL = 'http://localhost:8081/api';

    // 3. Inject HTML
    const widgetHtml = `
    <div x-data="chatbotWidget()" class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-[999] font-sans">
        <button @click="toggleChat()" :class="isOpen ? 'scale-0 opacity-0' : 'scale-100 opacity-100'" 
            class="w-14 h-14 rounded-full shadow-2xl flex items-center justify-center text-white hover:scale-110 hover:shadow-indigo-300 transition-all duration-300 absolute bottom-0 right-0"
            :style="{ backgroundColor: botColor }">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
            <span x-show="unread > 0" class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white shadow-sm" x-text="unread" style="display: none;"></span>
        </button>

        <div x-show="isOpen" 
             x-transition:enter="transition ease-out duration-300 origin-bottom-right"
             x-transition:enter-start="opacity-0 scale-50 translate-y-10"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200 origin-bottom-right"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-50 translate-y-10"
             style="display: none;" 
             class="absolute bottom-0 right-0 w-[calc(100vw-2rem)] sm:w-[380px] h-[550px] max-h-[85vh] bg-white rounded-3xl shadow-[0_10px_40px_rgba(0,0,0,0.2)] border border-slate-100 flex flex-col overflow-hidden">
            
            <div class="p-3 sm:p-4 flex items-center justify-between shadow-md z-10 shrink-0" :style="{ backgroundColor: botColor }">
                <div class="flex items-center gap-2 sm:gap-3">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-base sm:text-xl shadow-inner border border-white/30">🤖</div>
                    <div>
                        <h3 class="text-white font-bold text-sm sm:text-base leading-tight" x-text="botName"></h3>
                        <p class="text-teal-100 text-[9px] sm:text-[10px] flex items-center gap-1 mt-0.5"><span class="w-1.5 h-1.5 bg-green-400 rounded-full animate-pulse"></span> Siap Membantu</p>
                    </div>
                </div>
                <div class="flex items-center gap-1 sm:gap-2">
                    <button @click="resetChat()" title="Mulai Chat Baru" class="text-white hover:bg-white/20 px-2.5 py-1.5 rounded-lg transition-colors flex items-center gap-1.5 bg-white/10 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider">New Chat</span>
                    </button>
                    <button @click="toggleChat()" title="Tutup Chat" class="text-white hover:bg-white/20 p-1.5 rounded-lg transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                </div>
            </div>

            <div id="chat-container-api" class="flex-1 overflow-y-auto p-4 space-y-4 bg-slate-50 scroll-smooth">
                <template x-for="(msg, index) in messages" :key="index">
                    <div class="flex flex-col" :class="msg.sender === 'user' ? 'items-end' : 'items-start'">
                        <div class="flex items-baseline gap-1 mb-1 px-1">
                            <span class="text-[9px] text-slate-400 font-bold" x-text="msg.sender === 'user' ? 'Anda' : (msg.sender === 'admin' ? liveAdminName : 'Bot')"></span>
                            <span class="text-[8px] text-slate-400/70 font-medium" x-show="msg.time" x-text="msg.time"></span>
                        </div>
                        <div class="max-w-[85%] px-4 py-2.5 rounded-2xl text-xs sm:text-sm shadow-sm whitespace-pre-line leading-relaxed"
                             :class="msg.sender === 'user' ? 'text-white rounded-tr-sm' : 'bg-white text-slate-700 border border-slate-200 rounded-tl-sm'"
                             :style="msg.sender === 'user' ? 'background-color: ' + botColor : ''"
                             x-html="msg.text"></div>
                    </div>
                </template>

                <div x-show="isTyping" class="flex items-start" style="display: none;">
                    <div class="bg-white border border-slate-200 px-4 py-3 rounded-2xl rounded-tl-sm shadow-sm flex items-center gap-1.5">
                        <div class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce"></div>
                        <div class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                </div>
            </div>

            <div x-show="showLiveChatBtn && liveChatStatus !== 'pending' && liveChatStatus !== 'active' && !isFinished" class="shrink-0 bg-slate-100/90 backdrop-blur-sm p-2.5 border-t border-slate-200 flex flex-wrap justify-center gap-2 z-10" style="display: none;">
                <button @click="requestLiveChat()" class="px-4 py-2 text-white text-[10px] sm:text-xs font-bold rounded-full transition-all shadow-md flex items-center gap-1.5 transform hover:scale-105" :style="{ backgroundColor: botColor }">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2h2v4l.586-.586z" /></svg>
                    Live Chat CS
                </button>

                <button x-show="!followUpMode" @click="triggerFollowUp()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-[10px] sm:text-xs font-bold rounded-full transition-colors shadow-sm flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    Akhiri Sesi
                </button>
            </div>

            <div class="shrink-0 p-2 sm:p-3 bg-white border-t border-slate-200 shadow-[0_-4px_15px_rgba(0,0,0,0.03)] z-20">
                <form @submit.prevent="sendMessage()" class="relative flex items-end">
                    <textarea 
                        x-ref="chatInput"
                        x-model="inputText" 
                        @input="resizeInput()"
                        @keydown.enter="if(!$event.shiftKey) { $event.preventDefault(); if(inputText.trim() && !isFinished && !isTyping) sendMessage(); }"
                        :placeholder="followUpMode ? 'Ketik Email / No WA Anda...' : 'Ketik pertanyaan Anda di sini...'" 
                        :disabled="isFinished || isTyping"
                        rows="1"
                        class="w-full bg-slate-100 text-slate-800 text-xs sm:text-sm px-3.5 py-2.5 pr-14 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-shadow disabled:opacity-50 border border-transparent resize-none overflow-y-auto max-h-[120px] leading-normal shadow-inner [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]"
                        style="min-height: 40px; height: 40px;"
                    ></textarea>
                    
                    <button type="submit" :disabled="!inputText.trim() || isFinished || isTyping" 
                            class="absolute bottom-1 right-1 sm:bottom-1.5 sm:right-1.5 w-8 h-8 text-white rounded-lg disabled:opacity-50 transition-all shadow-sm flex items-center justify-center transform active:scale-95"
                            :style="{ backgroundColor: (!inputText.trim() || isFinished || isTyping) ? '#cbd5e1' : botColor }">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transform rotate-0 ml-0.5" viewBox="0 0 20 20" fill="currentColor"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" /></svg>
                    </button>
                </form>
                <div class="text-center mt-1.5 flex justify-center items-center gap-1" x-show="!isFinished">
                    <span class="text-[8px] sm:text-[9px] text-slate-400 font-medium">Powered by CS Bot</span>
                    <span class="hidden sm:inline text-[8px] text-slate-300">|</span>
                    <span class="hidden sm:inline text-[8px] text-slate-400">Enter ↵ kirim, Shift+Enter baris baru</span>
                </div>
            </div>

        </div> 
    </div>
    `;

    // 4. Append to body
    const div = document.createElement('div');
    div.innerHTML = widgetHtml;
    document.body.appendChild(div);

    // 5. Provide AlpineJS Data Logic
    window.chatbotWidget = function() {
        return {
            isOpen: false,
            unread: 0,
            inputText: '',
            isTyping: false,
            followUpMode: false,
            isFinished: false,
            selectedTopic: 'Umum',
            lastUserMessage: '',
            leadId: null,
            
            lastActivity: Date.now(),
            hasNudged: false,
            activityTimer: null,

            showLiveChatBtn: false,
            isLiveChat: false,
            liveChatStatus: 'none',
            liveAdminName: 'CS',
            livePollInterval: null,

            messages: [],
            
            botName: 'Live Chat Support',
            botColor: '#2563eb',

            init() {
                this.fetchConfig();
                this.loadState();
            },
            
            async fetchConfig() {
                try {
                    let license = window.ChatbotLicense || '';
                    let res = await fetch(`${SAAS_URL}/v1/license/config?license=${license}`);
                    let data = await res.json();
                    if (data.bot_name) this.botName = data.bot_name;
                    if (data.bot_color) this.botColor = data.bot_color;
                } catch(e) {
                    console.error('Failed to load bot config');
                }
            },

            resizeInput() {
                let el = this.$refs.chatInput;
                if (el) {
                    el.style.height = 'auto'; 
                    el.style.height = Math.min(el.scrollHeight, 120) + 'px'; 
                }
            },

            loadState() {
                let saved = localStorage.getItem('csbot_state');
                if (saved) {
                    let data = JSON.parse(saved);
                    this.isOpen = data.isOpen || false;
                    this.unread = data.unread || 0;
                    this.messages = data.messages || [];
                    this.selectedTopic = data.selectedTopic || 'Umum';
                    this.followUpMode = data.followUpMode || false;
                    this.isFinished = data.isFinished || false;
                    this.leadId = data.leadId || null;
                    this.lastActivity = data.lastActivity || Date.now();
                    this.hasNudged = data.hasNudged || false;
                    this.liveChatStatus = data.liveChatStatus || 'none';
                    
                    if (this.isOpen) this.scrollToBottom();
                    if (this.liveChatStatus === 'pending' || this.liveChatStatus === 'active') this.startLivePolling();
                } else {
                    this.unread = 1;
                    this.lastActivity = Date.now();
                    this.sendWelcome();
                }
                
                this.startActivityMonitor();
            },

            saveState() {
                localStorage.setItem('csbot_state', JSON.stringify({
                    isOpen: this.isOpen,
                    unread: this.unread,
                    messages: this.messages,
                    selectedTopic: this.selectedTopic,
                    followUpMode: this.followUpMode,
                    isFinished: this.isFinished,
                    leadId: this.leadId,
                    lastActivity: this.lastActivity,
                    hasNudged: this.hasNudged,
                    liveChatStatus: this.liveChatStatus
                }));
            },

            startActivityMonitor() {
                if(this.activityTimer) clearInterval(this.activityTimer);
                
                const checkTimeout = () => {
                    if (this.isFinished) return;

                    if (this.liveChatStatus === 'pending' || this.liveChatStatus === 'active') {
                        this.updateActivity(); 
                        return; 
                    }
                    
                    let elapsed = Date.now() - this.lastActivity;
                    
                    if (elapsed >= 900000) {
                        this.triggerAutoClose();
                    } 
                    else if (elapsed >= 600000 && !this.hasNudged) {
                        this.hasNudged = true;
                        this.messages.push({ sender: 'bot', text: 'Bot perhatikan tidak ada balasan cukup lama. Apakah Anda masih di sana? Sesi ini akan otomatis diakhiri dalam 5 menit.' });
                        this.playNotification();
                        if (!this.isOpen) this.unread++;
                        this.saveState();
                        this.scrollToBottom();
                    }
                };
                
                checkTimeout();
                this.activityTimer = setInterval(checkTimeout, 60000);
            },

            updateActivity() {
                this.lastActivity = Date.now();
                this.hasNudged = false;
            },

            async triggerAutoClose() {
                this.isFinished = true;
                this.followUpMode = false;
                this.messages.push({ sender: 'bot', text: 'Sesi chat telah diakhiri otomatis oleh sistem karena tidak ada aktivitas.' });
                this.saveState();
                this.scrollToBottom();
                
                if (this.leadId) {
                    try {
                        await fetch(`${SAAS_URL}/chatbot/send`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ 
                                is_autoclose: true,
                                chat_history: this.messages,
                                lead_id: this.leadId
                            })
                        });
                    } catch(e) {}
                }
            },

            playNotification() {
                try {
                    let AudioContext = window.AudioContext || window.webkitAudioContext;
                    if (!AudioContext) return;
                    let ctx = new AudioContext();
                    let osc = ctx.createOscillator();
                    let gain = ctx.createGain();
                    osc.connect(gain); gain.connect(ctx.destination);
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(800, ctx.currentTime); 
                    osc.frequency.exponentialRampToValueAtTime(1200, ctx.currentTime + 0.1); 
                    gain.gain.setValueAtTime(0, ctx.currentTime);
                    gain.gain.linearRampToValueAtTime(0.3, ctx.currentTime + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
                    osc.start(ctx.currentTime); osc.stop(ctx.currentTime + 0.3);
                } catch(e) {}
            },

            sendWelcome() {
                this.selectedTopic = 'Umum';
                this.messages = [{ sender: 'bot', text: 'Halo 👋! Selamat datang. Ada yang bisa Mimin bantu hari ini?' }];
                this.saveState();
            },

            resetChat() {
                this.selectedTopic = 'Umum';
                this.followUpMode = false;
                this.isFinished = false;
                this.inputText = '';
                this.$nextTick(() => { this.resizeInput(); });
                this.unread = 1;
                this.leadId = null;
                this.isLiveChat = false;
                this.showLiveChatBtn = false;
                this.updateActivity();
                this.sendWelcome();
                if(this.livePollInterval) clearInterval(this.livePollInterval);
                this.liveChatStatus = 'none';
            },

            toggleChat() {
                this.isOpen = !this.isOpen;
                if (this.isOpen) {
                    this.unread = 0;
                    this.updateActivity();
                }
                this.saveState();
                this.scrollToBottom();
            },

            scrollToBottom() {
                setTimeout(() => {
                    const container = document.getElementById('chat-container-api');
                    if (container) container.scrollTop = container.scrollHeight;
                }, 100);
            },

            triggerFollowUp() {
                this.followUpMode = true;
                this.messages.push({ sender: 'bot', text: 'Tentu. Silakan ketikkan <b>Email atau No WA</b> Anda di bawah ini, agar tim teknis/CS kami bisa segera mengecek dan menghubungi Anda.' });
                this.playNotification();
                this.updateActivity();
                this.saveState();
                this.scrollToBottom();
            },

            async sendMessage() {
                if (!this.inputText.trim()) return;
                
                const msgText = this.inputText;
                let timeNow = new Date().toLocaleTimeString('id-ID', {day: 'numeric', month: 'short', hour: '2-digit', minute:'2-digit'});
                this.messages.push({ sender: 'user', text: msgText, time: timeNow });
                this.inputText = '';
                
                this.$nextTick(() => { this.resizeInput(); });
                
                this.updateActivity();
                this.saveState();
                if (!this.followUpMode) this.lastUserMessage = msgText; 
                
                this.scrollToBottom();
                this.isTyping = true;

                try {
                    let isLive = (this.liveChatStatus === 'pending' || this.liveChatStatus === 'active');
                    let endpoint = isLive ? `${SAAS_URL}/chatbot/live/send` : `${SAAS_URL}/chatbot/send`;
                    
                    let res = await fetch(endpoint, {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ 
                            message: msgText, 
                            topic: this.selectedTopic, 
                            is_followup: this.followUpMode,
                            last_chat: this.lastUserMessage,
                            chat_history: this.messages,
                            lead_id: this.leadId
                        })
                    });
                    let data = await res.json();

                    if(data.lead_id) this.leadId = data.lead_id;

                    setTimeout(() => {
                        this.isTyping = false;

                        if(!isLive && data.reply) {
                            this.messages.push({ sender: 'bot', text: data.reply, time: timeNow });
                            this.playNotification();
                        }

                        if (data.is_finished) { this.isFinished = true; this.followUpMode = false; }
                        if (data.show_live_chat_btn) this.showLiveChatBtn = true;
                        if (!this.isOpen) this.unread++;
                        
                        this.saveState(); this.scrollToBottom();
                    }, 400);

                } catch (e) {
                    this.isTyping = false;
                    this.messages.push({ sender: 'bot', text: 'Maaf, sedang gangguan jaringan. Coba lagi ya.' });
                    this.saveState(); this.scrollToBottom();
                }
            },

            async requestLiveChat() {
                this.selectedTopic = 'Live Chat'; 
                this.showLiveChatBtn = false;
                this.liveChatStatus = 'pending';
                
                this.messages.push({ sender: 'bot', text: 'Meneruskan permintaan ke tim CS. Mohon tunggu sebentar...' });
                this.saveState(); 
                this.scrollToBottom();

                try {
                    let res = await fetch(`${SAAS_URL}/chatbot/live/request`, {
                        method: 'POST', 
                        headers: { 
                            'Content-Type': 'application/json', 
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ lead_id: this.leadId })
                    });
                    let data = await res.json();
                    
                    if (data.lead_id) {
                        this.leadId = data.lead_id;
                        this.saveState();
                    }

                    this.startLivePolling();
                } catch(e) {
                    this.messages.push({ sender: 'bot', text: 'Gagal menghubungkan. Pastikan koneksi internet Anda stabil.' });
                }
            },

            startLivePolling() {
                if(this.livePollInterval) clearInterval(this.livePollInterval);
                this.livePollInterval = setInterval(async () => {
                    if(!this.leadId) return;
                    try {
                        let res = await fetch(`${SAAS_URL}/chatbot/live/poll/${this.leadId}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        let data = await res.json();
                        
                        this.liveChatStatus = data.status;
                        if(data.admin_name) this.liveAdminName = data.admin_name;
                        
                        if(data.status === 'active') this.isLiveChat = true;
                        if(data.status === 'ended') {
                            this.isLiveChat = false; 
                            clearInterval(this.livePollInterval);
                            this.messages.push({ sender: 'bot', text: 'Sesi Live Chat telah ditutup.' });
                            this.isFinished = true;
                            this.saveState();
                            this.scrollToBottom();
                        }
                        
                        if(data.history && data.history.length > 0) {
                            if (JSON.stringify(data.history) !== JSON.stringify(this.messages)) {
                                this.messages = data.history;
                                this.playNotification();
                                this.scrollToBottom();
                                this.saveState();
                            }
                        }
                    } catch(e) {}
                }, 3000);
            }
        };
    };
})();

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Chatbot & Live Chat Dashboard') }}
        </h2>
    </x-slot>

    <!-- Import Axios for AJAX -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <div class="py-12" x-data="dashboardData()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Success/Error Alerts -->
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Tabs -->
            <div class="mb-4 border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button @click="tab = 'knowledge'" 
                        :class="tab === 'knowledge' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        Knowledge Base (FAQ)
                    </button>
                    <button @click="tab = 'livechat'" 
                        :class="tab === 'livechat' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        Live Chat
                    </button>
                </nav>
            </div>

            <!-- ==================== KNOWLEDGE BASE TAB ==================== -->
            <div x-show="tab === 'knowledge'" style="display: none;" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold">Daftar Pengetahuan Bot</h3>
                        <button @click="openKnowledgeModal()" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                            + Tambah Data
                        </button>
                    </div>
                    
                    <table class="min-w-full divide-y divide-gray-200 mt-4">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keywords (JSON)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jawaban (Response)</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($knowledges as $k)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        @php
                                            $kwArray = is_string($k->keywords) ? json_decode($k->keywords, true) : $k->keywords;
                                            $kwString = is_array($kwArray) ? implode(', ', $kwArray) : '';
                                        @endphp
                                        {{ $kwString }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ Str::limit($k->response, 100) }}</td>
                                    <td class="px-6 py-4 text-sm text-right">
                                        <button @click="openKnowledgeModal({{ $k->id }}, '{{ addslashes($kwString) }}', '{{ addslashes($k->response) }}')" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                        <form action="{{ route('knowledge.destroy', $k->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus data ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-sm text-center text-gray-500">Belum ada data knowledge base.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Knowledge Modal (AlpineJS) -->
            <div x-show="showKnowledgeModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div x-show="showKnowledgeModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showKnowledgeModal = false"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <form :action="formAction" method="POST">
                            @csrf
                            <input type="hidden" name="_method" :value="formMethod">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" x-text="modalTitle"></h3>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700">Keywords (pisahkan dengan koma)</label>
                                    <input type="text" name="keywords" x-model="formData.keywords" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="harga, pricelist, bayar">
                                </div>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700">Jawaban / Response (SOP)</label>
                                    <textarea name="response" x-model="formData.response" rows="5" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Untuk informasi harga, kami memiliki 3 paket..."></textarea>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                                    Simpan
                                </button>
                                <button type="button" @click="showKnowledgeModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                    Batal
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>


            <!-- ==================== LIVE CHAT TAB ==================== -->
            <div x-show="tab === 'livechat'" style="display: none;" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold mb-4">Sesi Live Chat</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 h-[600px]">
                        <!-- Sidebar: List of Leads -->
                        <div class="col-span-1 border-r pr-4 overflow-y-auto">
                            @forelse($leads as $lead)
                                <div @click="selectLead({{ $lead->id }}, '{{ $lead->name ?? $lead->ip_address }}')" 
                                     :class="selectedLeadId == {{ $lead->id }} ? 'bg-indigo-50 border-indigo-500' : 'border-gray-200 hover:bg-gray-50'"
                                     class="p-3 border rounded-lg mb-2 cursor-pointer transition-colors">
                                    <div class="font-bold flex justify-between">
                                        <span>{{ $lead->name ?? $lead->ip_address }}</span>
                                        @if($lead->live_chat_status === 'pending')
                                            <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full animate-pulse">Waiting</span>
                                        @elseif($lead->live_chat_status === 'active')
                                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Active</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1 truncate">{{ Str::limit($lead->last_message, 40) }}</div>
                                    <div class="text-xs text-gray-400 mt-1">{{ $lead->updated_at->diffForHumans() }}</div>
                                </div>
                            @empty
                                <div class="text-gray-500 text-sm text-center mt-10">Tidak ada riwayat obrolan.</div>
                            @endforelse
                            <div class="mt-4">{{ $leads->links() }}</div>
                        </div>
                        
                        <!-- Main Chat Area -->
                        <div class="col-span-2 flex flex-col bg-gray-50 rounded-lg overflow-hidden border border-gray-200 shadow-inner">
                            
                            <!-- Chat Header -->
                            <div class="bg-white border-b px-4 py-3 flex justify-between items-center shadow-sm" x-show="selectedLeadId">
                                <div>
                                    <h4 class="font-bold" x-text="selectedLeadName"></h4>
                                    <p class="text-xs text-green-500" x-show="chatStatus === 'active' || chatStatus === 'pending'">Live Connection Active</p>
                                    <p class="text-xs text-gray-500" x-show="chatStatus === 'ended'">Chat Session Closed</p>
                                </div>
                                <button x-show="chatStatus !== 'ended'" @click="resolveChat()" class="text-xs bg-red-100 text-red-600 px-3 py-1 rounded-full hover:bg-red-200">
                                    Tutup Sesi (Resolve)
                                </button>
                            </div>

                            <!-- Chat Messages Area -->
                            <div id="chat-container" class="flex-1 p-4 overflow-y-auto" style="scroll-behavior: smooth;">
                                <template x-if="!selectedLeadId">
                                    <div class="h-full flex items-center justify-center text-gray-400">
                                        <div class="text-center">
                                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                            <p class="mt-2 text-sm">Pilih sesi chat di sebelah kiri untuk melihat pesan.</p>
                                        </div>
                                    </div>
                                </template>

                                <template x-for="(msg, index) in chatHistory" :key="index">
                                    <div :class="msg.sender === 'admin' ? 'flex justify-end mb-4' : (msg.sender === 'system' ? 'flex justify-center mb-4' : 'flex justify-start mb-4')">
                                        
                                        <!-- System Message -->
                                        <template x-if="msg.sender === 'system'">
                                            <div class="bg-gray-200 text-gray-600 text-xs px-3 py-1 rounded-full" x-text="msg.text"></div>
                                        </template>

                                        <!-- User / Bot / Admin Message -->
                                        <template x-if="msg.sender !== 'system'">
                                            <div :class="msg.sender === 'admin' ? 'bg-indigo-600 text-white rounded-l-lg rounded-br-lg' : 'bg-white border rounded-r-lg rounded-bl-lg'" 
                                                 class="max-w-[70%] px-4 py-2 shadow-sm relative">
                                                <div class="text-xs font-bold mb-1" 
                                                     :class="msg.sender === 'admin' ? 'text-indigo-200' : 'text-gray-500'" 
                                                     x-text="msg.sender === 'user' ? selectedLeadName : (msg.sender === 'admin' ? 'Anda (Admin)' : 'Bot AI')">
                                                </div>
                                                <div class="text-sm" x-html="formatMessage(msg.text)"></div>
                                                <div class="text-[10px] text-right mt-1 opacity-70" x-text="msg.time"></div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                            
                            <!-- Chat Input -->
                            <div class="bg-white p-3 border-t" x-show="selectedLeadId && chatStatus !== 'ended'">
                                <form @submit.prevent="sendMessage" class="flex gap-2">
                                    <input type="text" x-model="newMessage" required :disabled="isSending"
                                           class="flex-1 rounded-full border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 px-4" 
                                           placeholder="Ketik balasan admin...">
                                    <button type="submit" :disabled="isSending" class="px-5 py-2 bg-indigo-600 text-white rounded-full hover:bg-indigo-700 disabled:opacity-50">
                                        <span x-show="!isSending">Kirim</span>
                                        <span x-show="isSending">...</span>
                                    </button>
                                </form>
                            </div>
                            <div class="bg-gray-100 p-3 border-t text-center text-sm text-gray-500" x-show="selectedLeadId && chatStatus === 'ended'">
                                Sesi ini sudah ditutup. Tidak dapat mengirim pesan baru.
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function dashboardData() {
            return {
                tab: 'livechat', // default tab
                
                // Knowledge Base State
                showKnowledgeModal: false,
                modalTitle: '',
                formAction: '',
                formMethod: 'POST',
                formData: {
                    keywords: '',
                    response: ''
                },
                
                // Live Chat State
                selectedLeadId: null,
                selectedLeadName: '',
                chatHistory: [],
                chatStatus: 'none',
                newMessage: '',
                isSending: false,
                pollInterval: null,

                // Methods Knowledge Base
                openKnowledgeModal(id = null, keywords = '', response = '') {
                    this.formData.keywords = keywords;
                    this.formData.response = response;
                    
                    if(id) {
                        this.modalTitle = 'Edit Knowledge Base';
                        this.formAction = `/knowledge/${id}`;
                        this.formMethod = 'PUT';
                    } else {
                        this.modalTitle = 'Tambah Knowledge Base';
                        this.formAction = '/knowledge';
                        this.formMethod = 'POST';
                    }
                    this.showKnowledgeModal = true;
                },

                // Methods Live Chat
                selectLead(id, name) {
                    this.selectedLeadId = id;
                    this.selectedLeadName = name;
                    this.fetchHistory();
                    
                    // Stop previous polling
                    if(this.pollInterval) clearInterval(this.pollInterval);
                    
                    // Start new polling every 3 seconds
                    this.pollInterval = setInterval(() => {
                        if(this.selectedLeadId && this.chatStatus !== 'ended') {
                            this.fetchHistory(false); // background fetch, no scroll jump unless new msg
                        }
                    }, 3000);
                },

                fetchHistory(forceScroll = true) {
                    axios.get(`/livechat/${this.selectedLeadId}/history`)
                        .then(res => {
                            const oldLength = this.chatHistory.length;
                            this.chatHistory = res.data.history;
                            this.chatStatus = res.data.status;
                            
                            // Scroll to bottom if new messages arrived
                            if(forceScroll || this.chatHistory.length > oldLength) {
                                this.$nextTick(() => {
                                    const container = document.getElementById('chat-container');
                                    container.scrollTop = container.scrollHeight;
                                });
                            }
                        })
                        .catch(err => console.error(err));
                },

                sendMessage() {
                    if(!this.newMessage.trim()) return;
                    this.isSending = true;

                    // Optimistic update
                    this.chatHistory.push({
                        sender: 'admin',
                        text: this.newMessage,
                        time: 'Mengirim...'
                    });
                    
                    this.$nextTick(() => {
                        const container = document.getElementById('chat-container');
                        container.scrollTop = container.scrollHeight;
                    });

                    axios.post(`/livechat/${this.selectedLeadId}/reply`, {
                        message: this.newMessage
                    }).then(res => {
                        this.newMessage = '';
                        this.fetchHistory(true);
                    }).catch(err => {
                        alert('Gagal mengirim pesan');
                        console.error(err);
                    }).finally(() => {
                        this.isSending = false;
                    });
                },

                resolveChat() {
                    if(confirm('Yakin ingin menutup sesi ini?')) {
                        axios.post(`/livechat/${this.selectedLeadId}/resolve`)
                            .then(res => {
                                this.chatStatus = 'ended';
                                this.fetchHistory(true);
                            });
                    }
                },

                formatMessage(text) {
                    // Convert line breaks to <br> for proper HTML rendering
                    return text.replace(/\n/g, '<br>');
                }
            }
        }
    </script>
</x-app-layout>

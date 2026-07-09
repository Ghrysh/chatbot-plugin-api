<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Chatbot & Live Chat Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ tab: 'knowledge' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
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

            <!-- Knowledge Base Tab -->
            <div x-show="tab === 'knowledge'" style="display: none;" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold">Daftar Pengetahuan Bot</h3>
                        <button class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm">Tambah Data</button>
                    </div>
                    
                    <table class="min-w-full divide-y divide-gray-200 mt-4">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keyword</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jawaban (Response)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($knowledges as $k)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $k->keyword }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ Str::limit($k->response, 50) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-6 py-4 text-sm text-center text-gray-500">Belum ada data knowledge base.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Live Chat Tab -->
            <div x-show="tab === 'livechat'" style="display: none;" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold mb-4">Sesi Live Chat Aktif</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Sidebar: List of Leads -->
                        <div class="col-span-1 border-r pr-4">
                            @forelse($leads as $lead)
                                <div class="p-3 border rounded-lg mb-2 cursor-pointer hover:bg-gray-50">
                                    <div class="font-bold">{{ $lead->name }}</div>
                                    <div class="text-xs text-gray-500">Status: {{ $lead->status }}</div>
                                </div>
                            @empty
                                <div class="text-gray-500 text-sm">Tidak ada permintaan live chat.</div>
                            @endforelse
                            <div class="mt-2">{{ $leads->links() }}</div>
                        </div>
                        
                        <!-- Main Chat Area -->
                        <div class="col-span-2 flex flex-col h-96 bg-gray-50 rounded-lg p-4">
                            <div class="flex-1 overflow-y-auto mb-4 border-b">
                                <div class="text-center text-gray-400 text-sm mt-10">Pilih sesi chat di sebelah kiri untuk membalas pesan.</div>
                            </div>
                            <div class="flex gap-2">
                                <input type="text" class="flex-1 rounded-md border-gray-300" placeholder="Ketik balasan admin...">
                                <button class="px-4 py-2 bg-indigo-600 text-white rounded-md">Kirim</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

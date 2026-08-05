import re

filepath = 'resources/views/dashboard.blade.php'
with open(filepath, 'r') as f:
    content = f.read()

start_marker = "                            @csrf"
end_marker = "                        </form>"
start_idx = content.find(start_marker)
end_idx = content.find(end_marker, start_idx)

new_form_content = """                            @csrf
                            
                            <!-- VIEW 1: Form Input -->
                            <div x-show="!isGenerating" class="flex flex-col h-full">
                                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                                    <h3 class="font-bold text-lg text-slate-800">Upload File (Auto Generate AI)</h3>
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

                            <!-- VIEW 2: Progress Modal (Replaces Form Input completely during processing) -->
                            <div x-show="isGenerating" style="display: none;" class="flex flex-col items-center justify-center p-8 text-center min-h-[350px] bg-white rounded-2xl">
                                <svg class="animate-spin h-14 w-14 text-indigo-600 mb-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                
                                <h3 class="text-xl font-black text-slate-800 mb-2">Memproses Pengetahuan...</h3>
                                <p class="text-sm font-medium text-slate-600 mb-8 max-w-sm">Mohon tunggu sebentar, AI sedang membaca dan mengekstrak dokumen Anda menjadi basis pengetahuan.</p>
                                
                                <div class="w-full max-w-xs bg-slate-100 rounded-full h-3 mb-2 overflow-hidden border border-slate-200 shadow-inner">
                                    <div class="bg-indigo-600 h-full rounded-full transition-all duration-500 ease-out" :style="`width: ${progress}%`"></div>
                                </div>
                                <div class="w-full max-w-xs flex justify-between text-xs font-bold text-slate-500 mb-8">
                                    <span>Mengunggah & Memproses</span>
                                    <span x-text="progress + '%'"></span>
                                </div>
                                
                                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl text-xs font-semibold flex gap-3 items-center text-left shadow-sm max-w-sm">
                                    <svg class="w-6 h-6 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    <p class="leading-relaxed">Proses ini memakan waktu 1-3 menit. <br><span class="font-bold text-amber-900">Mohon jangan tutup atau muat ulang (refresh) halaman ini.</span></p>
                                </div>
                            </div>
"""

new_content = content[:start_idx] + new_form_content + content[end_idx:]
with open(filepath, 'w') as f:
    f.write(new_content)

print(f"Successfully replaced dashboard.blade.php")

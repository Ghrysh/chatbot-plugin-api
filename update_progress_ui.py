import re

def modify_file(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    # The block to replace starts with <div x-show="isGenerating"
    # and ends at the end of that div.
    # We will just replace the inner content of isGenerating div.
    
    old_content = """<div x-show="isGenerating" style="display: none;" class="flex flex-col items-center justify-center p-8 text-center min-h-[350px] bg-white rounded-2xl">
                            <div class="relative w-20 h-20 mb-6">
                                <svg class="animate-spin w-full h-full text-indigo-100" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="#4F46E5" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </div>
                            </div>
                            <h3 class="text-xl font-black text-slate-800 mb-2">AI Sedang Bekerja</h3>
                            <p class="text-sm text-slate-500 mb-8 max-w-sm">Sistem sedang mengekstrak informasi penting dari dokumen Anda untuk dijadikan pengetahuan bot.</p>
                            
                            <!-- Progress Bar -->
                            <div class="w-full max-w-xs flex justify-between text-xs font-bold text-slate-500 mb-2">
                                <span>Mengunggah & Memproses</span>
                                <span x-text="progress + '%'"></span>
                            </div>
                            <div class="w-full max-w-xs text-xs text-indigo-600 font-semibold mb-8 h-8 text-left italic">
                                <span x-show="progress < 50" x-transition>Membaca dokumen...</span>
                                <span x-show="progress >= 50 && progress < 90" x-transition>Mengekstrak informasi penting...</span>
                                <span x-show="progress >= 90" x-transition class="text-amber-600">Sabar ya, AI sedang menyusun puluhan variasi keyword dan respon lengkap. Ini memakan waktu...</span>
                            </div>
                            <div class="w-full max-w-xs bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-300 ease-out" :style="`width: ${progress}%`"></div>
                            </div>
                        </div>"""

    new_content = """<div x-show="isGenerating" style="display: none;" class="flex flex-col items-center justify-center p-8 text-center min-h-[350px] bg-white rounded-2xl">
                            <h3 class="text-xl font-black text-slate-800 mb-6">AI Sedang Bekerja</h3>
                            
                            <!-- Stepper UI -->
                            <div class="flex flex-col gap-5 text-left w-full max-w-sm mx-auto">
                                <!-- Step 1 -->
                                <div class="flex items-start gap-4">
                                    <div class="mt-0.5">
                                        <svg x-show="progress >= 10" class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <svg x-show="progress < 10" class="animate-spin w-6 h-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    </div>
                                    <div class="flex-1">
                                        <h4 :class="progress >= 10 ? 'text-slate-800 font-bold' : 'text-indigo-600 font-bold'">Mengunggah & Membaca Dokumen</h4>
                                    </div>
                                </div>
                                
                                <!-- Step 2 -->
                                <div class="flex items-start gap-4" :class="progress < 10 ? 'opacity-40' : ''">
                                    <div class="mt-0.5">
                                        <svg x-show="progress >= 40" class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <svg x-show="progress >= 10 && progress < 40" class="animate-spin w-6 h-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        <div x-show="progress < 10" class="w-6 h-6 rounded-full border-2 border-slate-200"></div>
                                    </div>
                                    <div class="flex-1">
                                        <h4 :class="progress >= 40 ? 'text-slate-800 font-bold' : (progress >= 10 ? 'text-indigo-600 font-bold' : 'text-slate-500 font-medium')">Menganalisis Teks & Struktur</h4>
                                    </div>
                                </div>

                                <!-- Step 3 -->
                                <div class="flex items-start gap-4" :class="progress < 40 ? 'opacity-40' : ''">
                                    <div class="mt-0.5">
                                        <svg x-show="progress >= 99" class="animate-spin w-6 h-6 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        <svg x-show="progress >= 40 && progress < 99" class="animate-spin w-6 h-6 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        <div x-show="progress < 40" class="w-6 h-6 rounded-full border-2 border-slate-200"></div>
                                    </div>
                                    <div class="flex-1">
                                        <h4 :class="progress >= 40 ? 'text-amber-600 font-bold' : 'text-slate-500 font-medium'">Mengekstrak Topik & Menyusun SOP</h4>
                                        <p x-show="progress >= 40" x-transition class="text-xs text-slate-500 mt-1">Proses eksekusi AI ini memakan waktu beberapa menit. Harap jangan menutup halaman ini.</p>
                                    </div>
                                </div>
                            </div>
                        </div>"""
                        
    content = content.replace(old_content, new_content)
    with open(filepath, 'w') as f:
        f.write(content)
    print("Modified", filepath)

modify_file('resources/views/embed/chatbot.blade.php')
modify_file('resources/views/dashboard.blade.php')

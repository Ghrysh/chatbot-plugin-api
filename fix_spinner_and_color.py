import re

def modify_file(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    # Define the old stepper UI block we want to replace
    # We will just replace everything from <h3 class="text-xl font-black text-slate-800 mb-6">Sistem AI Sedang Bekerja</h3> to the end of the flex-col gap-5 text-left div.
    
    old_content = """                            <h3 class="text-xl font-black text-slate-800 mb-6">Sistem AI Sedang Bekerja</h3>
                            
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
                                        <h4 :class="progress >= 40 ? 'text-slate-800 font-bold' : (progress >= 10 ? 'text-indigo-600 font-bold' : 'text-slate-500 font-medium')">Menganalisis Struktur Pengetahuan</h4>
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
                                        <h4 :class="progress >= 40 ? 'text-amber-600 font-bold' : 'text-slate-500 font-medium'">Mengekstrak Keyword & Menyusun SOP</h4>
                                        <p x-show="progress >= 40" x-transition class="text-xs text-slate-500 mt-1">Proses intensif AI memakan waktu 3 - 10 menit. Mohon tidak menutup halaman ini.</p>
                                    </div>
                                </div>
                            </div>"""

    new_content = """                            <style>
                                @keyframes step-spin {
                                    from { transform: rotate(0deg); }
                                    to { transform: rotate(360deg); }
                                }
                                .animate-step-spin {
                                    animation: step-spin 1s linear infinite;
                                }
                            </style>
                            <h3 class="text-xl font-black text-slate-800 mb-6">Sistem AI Sedang Bekerja</h3>
                            
                            <!-- Stepper UI -->
                            <div class="flex flex-col gap-5 text-left w-full max-w-sm mx-auto">
                                <!-- Step 1 -->
                                <div class="flex items-start gap-4">
                                    <div class="mt-0.5">
                                        <svg x-show="progress >= 10" class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <svg x-show="progress < 10" class="animate-step-spin w-6 h-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    </div>
                                    <div class="flex-1">
                                        <h4 :class="progress >= 10 ? 'text-green-600 font-bold' : 'text-indigo-600 font-bold'">Mengunggah & Membaca Dokumen</h4>
                                    </div>
                                </div>
                                
                                <!-- Step 2 -->
                                <div class="flex items-start gap-4" :class="progress < 10 ? 'opacity-40' : ''">
                                    <div class="mt-0.5">
                                        <svg x-show="progress >= 40" class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <svg x-show="progress >= 10 && progress < 40" class="animate-step-spin w-6 h-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        <div x-show="progress < 10" class="w-6 h-6 rounded-full border-2 border-slate-200"></div>
                                    </div>
                                    <div class="flex-1">
                                        <h4 :class="progress >= 40 ? 'text-green-600 font-bold' : (progress >= 10 ? 'text-indigo-600 font-bold' : 'text-slate-500 font-medium')">Menganalisis Struktur Pengetahuan</h4>
                                    </div>
                                </div>

                                <!-- Step 3 -->
                                <div class="flex items-start gap-4" :class="progress < 40 ? 'opacity-40' : ''">
                                    <div class="mt-0.5">
                                        <svg x-show="progress >= 99" class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <svg x-show="progress >= 40 && progress < 99" class="animate-step-spin w-6 h-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        <div x-show="progress < 40" class="w-6 h-6 rounded-full border-2 border-slate-200"></div>
                                    </div>
                                    <div class="flex-1">
                                        <h4 :class="progress >= 99 ? 'text-green-600 font-bold' : (progress >= 40 ? 'text-indigo-600 font-bold' : 'text-slate-500 font-medium')">Mengekstrak Keyword & Menyusun SOP</h4>
                                        <p x-show="progress >= 40" x-transition class="text-xs text-slate-500 mt-1">Proses intensif AI memakan waktu 3 - 10 menit. Mohon tidak menutup halaman ini.</p>
                                    </div>
                                </div>
                            </div>"""
                            
    content = content.replace(old_content, new_content)
    with open(filepath, 'w') as f:
        f.write(content)
    print("Modified", filepath)

modify_file('resources/views/embed/chatbot.blade.php')
modify_file('resources/views/dashboard.blade.php')

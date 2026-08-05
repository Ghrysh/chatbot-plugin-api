import re

def modify_dashboard(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    # 1. Add showDeleteModal and deleteUrl to the main x-data of Knowledge Tab
    # The x-data is at: x-data="{ showKnowModal: false, showAutoGenerateModal: false, autoGenTab: 'file', isGenerating: false, isEdit: false, form: { id: '', topic: '', keywords: '', response: '' } }"
    content = re.sub(
        r'x-data="\{ showKnowModal: false, showAutoGenerateModal: false, autoGenTab: \'file\', isGenerating: false, isEdit: false, form: \{ id: \'\', topic: \'\', keywords: \'\', response: \'\' \} \}"',
        r'x-data="{ showKnowModal: false, showAutoGenerateModal: false, showDeleteModal: false, deleteUrl: \'\', autoGenTab: \'file\', isGenerating: false, isEdit: false, form: { id: \'\', topic: \'\', keywords: \'\', response: \'\' } }"',
        content
    )

    # 2. Replace the confirm form
    old_form = """<form action="{{ route('knowledge.destroy', $know->id) }}" method="POST" onsubmit="return confirm('Hapus respon bot ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-bold px-2 w-full text-right">Hapus</button>
                                    </form>"""
                                
    new_btn = """<button type="button" @click="deleteUrl = '{{ route('knowledge.destroy', $know->id) }}'; showDeleteModal = true" class="text-red-500 hover:text-red-700 text-xs font-bold px-2 w-full text-right">Hapus</button>"""
    
    content = content.replace(old_form, new_btn)

    # 3. Add the delete modal HTML just before the "<!-- Modal Knowledge -->"
    delete_modal = """
                <!-- Modal Konfirmasi Hapus -->
                <div x-show="showDeleteModal" style="display: none;" class="fixed inset-0 z-[250] flex items-center justify-center p-4">
                    <div x-show="showDeleteModal" x-transition.opacity class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                    <div x-show="showDeleteModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" class="relative bg-white rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden p-6 text-center">
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
                                <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg font-bold hover:bg-red-700 transition-colors shadow-sm">Ya, Hapus!</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Knowledge -->"""
    content = content.replace("<!-- Modal Knowledge -->", delete_modal)
    
    # 4. Update the text in progress modal for "Auto Generate"
    progress_html_old = """                            <div class="w-full max-w-xs flex justify-between text-xs font-bold text-slate-500 mb-8">
                                <span>Mengunggah & Memproses</span>
                                <span x-text="progress + '%'"></span>
                            </div>"""
                            
    progress_html_new = """                            <div class="w-full max-w-xs flex justify-between text-xs font-bold text-slate-500 mb-2">
                                <span>Mengunggah & Memproses</span>
                                <span x-text="progress + '%'"></span>
                            </div>
                            <div class="w-full max-w-xs text-xs text-indigo-600 font-semibold mb-8 h-8 text-left italic">
                                <span x-show="progress < 50" x-transition>Membaca dokumen...</span>
                                <span x-show="progress >= 50 && progress < 90" x-transition>Mengekstrak informasi penting...</span>
                                <span x-show="progress >= 90" x-transition class="text-amber-600">Sabar ya, AI sedang menyusun puluhan variasi keyword dan respon lengkap. Ini memakan waktu...</span>
                            </div>"""
    
    content = content.replace(progress_html_old, progress_html_new)

    with open(filepath, 'w') as f:
        f.write(content)
    print("Modified dashboard!")

modify_dashboard('resources/views/dashboard.blade.php')

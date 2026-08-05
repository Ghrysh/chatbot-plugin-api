import re

def fix_failed_state(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    # 1. Fix x-show to also show on 'failed'
    content = content.replace(
        "x-show=\"aiJob.status === 'processing' || aiJob.status === 'completed'\"",
        "x-show=\"aiJob.status === 'processing' || aiJob.status === 'completed' || aiJob.status === 'failed'\""
    )

    # 2. Fix the failed handler - don't hide, show error state
    content = content.replace(
        """} else if (data.status === 'failed' || data.status === 'cancelled') {
                            this.aiJob.status = null;
                            clearInterval(this.pollInterval);
                        }""",
        """} else if (data.status === 'failed') {
                            this.aiJob.status = 'failed';
                            this.aiJob.progress = 0;
                            clearInterval(this.pollInterval);
                        } else if (data.status === 'cancelled') {
                            this.aiJob.status = null;
                            clearInterval(this.pollInterval);
                        }"""
    )

    # 3. Add failed UI template after the completed template
    # Find the completed span and add failed template after it
    completed_span = """<span style="font-size: 14px; font-weight: 600; color: #334155;" x-text="aiJob.status === 'completed' ? 'Proses Selesai!' : 'AI mengekstrak data...'"></span>"""
    
    failed_aware_span = """<template x-if="aiJob.status === 'failed'">
                        <div style="position: relative; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; border-radius: 9999px; background: #fee2e2;">
                            <svg style="width: 14px; height: 14px; color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </div>
                    </template>
                    <span style="font-size: 14px; font-weight: 600; color: #334155;" x-text="aiJob.status === 'completed' ? 'Proses Selesai!' : (aiJob.status === 'failed' ? 'Proses Gagal!' : 'AI mengekstrak data...')"></span>"""
    
    content = content.replace(completed_span, failed_aware_span)

    # 4. Add a "Tutup" button for the failed state and change progress bar color for failed
    # After the Batal button template, add a Tutup button for failed
    batal_template_end = """</template>
            </div>"""
    
    batal_with_failed = """</template>
                <template x-if="aiJob.status === 'failed'">
                    <button @click="aiJob.status = null" type="button" style="font-size: 11px; color: #475569; font-weight: 500; padding: 4px 8px; border-radius: 6px; background: #f1f5f9; border: 1px solid #e2e8f0; cursor: pointer;">
                        Tutup
                    </button>
                </template>
            </div>"""
    
    content = content.replace(batal_template_end, batal_with_failed, 1)

    # 5. Update progress bar color to show red on failed
    content = content.replace(
        "{ backgroundColor: aiJob.status === 'completed' ? '#22c55e' : '#4f46e5', width: aiJob.progress + '%' }",
        "{ backgroundColor: aiJob.status === 'completed' ? '#22c55e' : (aiJob.status === 'failed' ? '#ef4444' : '#4f46e5'), width: aiJob.progress + '%' }"
    )

    # 6. Update the bottom text for failed state
    content = content.replace(
        "x-text=\"aiJob.status === 'completed' ? 'Menyegarkan halaman...' : 'Berjalan di latar belakang'\"",
        "x-text=\"aiJob.status === 'completed' ? 'Menyegarkan halaman...' : (aiJob.status === 'failed' ? 'Terjadi kesalahan saat memproses.' : 'Berjalan di latar belakang')\""
    )

    with open(filepath, 'w') as f:
        f.write(content)
    print(f"Fixed failed state in {filepath}")

fix_failed_state('resources/views/embed/chatbot.blade.php')
fix_failed_state('resources/views/dashboard.blade.php')

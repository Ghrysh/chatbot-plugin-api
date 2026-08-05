import re

def fix_toast(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    toast_start = "<!-- Floating AI Progress Toast -->"
    if toast_start not in content:
        return
        
    start_idx = content.find(toast_start)
    end_tag = "</template>\n</div>"
    end_idx = content.find(end_tag, start_idx) + len(end_tag)
    
    toast_html = content[start_idx:end_idx]
    
    # Remove the toast from its current position
    new_content = content[:start_idx].rstrip() + "\n\n" + content[end_idx:].lstrip()
    
    # We need to insert it at line 286, which is right after the second </table>\n            </div>
    # A robust way is to find <!-- Modal Konfirmasi Hapus --> and insert it right before that.
    target_marker = "<!-- Modal Konfirmasi Hapus -->"
    target_idx = new_content.find(target_marker)
    
    if target_idx != -1:
        new_content = new_content[:target_idx] + toast_html + "\n\n            " + new_content[target_idx:]
        with open(filepath, 'w') as f:
            f.write(new_content)
        print(f"Moved toast successfully in {filepath}")
    else:
        print("Target marker not found")

fix_toast('resources/views/embed/chatbot.blade.php')
fix_toast('resources/views/dashboard.blade.php')

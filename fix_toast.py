import re

def fix_file(filepath, layout_tag):
    with open(filepath, 'r') as f:
        content = f.read()

    # Find the toast HTML block
    toast_start = "<!-- Floating AI Progress Toast -->"
    if toast_start in content:
        toast_idx = content.find(toast_start)
        toast_html = content[toast_idx:]
        
        # Remove the toast from its current position
        content = content[:toast_idx].strip()
        
        # Insert the toast before the layout tag
        if f"</{layout_tag}>" in content:
            content = content.replace(f"</{layout_tag}>", toast_html + f"\n</{layout_tag}>")
        else:
            print(f"Could not find </{layout_tag}> in {filepath}")
            
        with open(filepath, 'w') as f:
            f.write(content)
        print(f"Fixed {filepath}")
    else:
        print(f"Toast not found in {filepath}")

fix_file('resources/views/embed/chatbot.blade.php', 'x-embed-layout')

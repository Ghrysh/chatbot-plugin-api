import re

def fix_position(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    toast_start = "<!-- Floating AI Progress Toast -->"
    if toast_start not in content:
        print(f"Toast not found in {filepath}")
        return

    # Extract the toast
    start_idx = content.find(toast_start)
    end_tag = "</x-embed-layout>"
    if end_tag not in content:
        end_tag = "</x-app-layout>"
        
    # In my previous script, I placed it before </x-embed-layout>
    end_idx = content.find(toast_start, start_idx + 1)
    # Wait, there is only one toast. Where does it end?
    # Let's find the closing tag of the toast's main div.
    # Actually, the toast goes from toast_start up to end_tag.
    
    toast_html = content[start_idx:content.rfind(end_tag)].strip()
    
    # Let's remove the fixed positioning from the toast HTML
    toast_html = toast_html.replace(
        "style=\"display: none; position: fixed; bottom: 24px; right: 24px; width: 320px; z-index: 99999; background: white; border-radius: 12px; box-shadow: 0 10px 40px -10px rgba(0,0,0,0.15); border: 1px solid #e2e8f0; padding: 20px; overflow: hidden;\"",
        "style=\"display: none; margin: 20px 24px; width: 320px; background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; padding: 20px; overflow: hidden;\""
    )

    # Now, let's remove the toast from the bottom of the file
    new_content = content[:start_idx] + content[content.rfind(end_tag):]
    
    # Now, let's inject it right after the table in botTab === 'knowledge'
    # The table is inside <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
    # Which ends with </div> just before the modals.
    # Let's find: </table>\n            </div>
    
    table_end_marker = "</table>\n            </div>"
    if table_end_marker in new_content:
        # there are two tables, we want the second one (botTab === 'knowledge')
        # Let's find botTab === 'knowledge'
        know_idx = new_content.find("botTab === 'knowledge'")
        if know_idx != -1:
            target_idx = new_content.find(table_end_marker, know_idx)
            if target_idx != -1:
                insert_pos = target_idx + len(table_end_marker)
                new_content = new_content[:insert_pos] + "\n\n            " + toast_html + new_content[insert_pos:]
                
                with open(filepath, 'w') as f:
                    f.write(new_content)
                print(f"Successfully moved toast in {filepath}")
                return
    print("Failed to find insertion point")
    
fix_position('resources/views/embed/chatbot.blade.php')


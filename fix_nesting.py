def fix_nesting(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    # Step 1: Extract the toast block (from <style> to </div> before the extra </div>)
    style_start = "\n<style>\n@keyframes custom-ai-spin {"
    toast_end_marker = "</div>\n\n</div>"  # toast close + extra div.p-6 close
    
    style_idx = content.find(style_start)
    if style_idx == -1:
        print(f"Style block not found in {filepath}")
        return
    
    # Find the end of the toast div (the </div> that closes the toast)
    # The toast div starts with <!-- Floating AI Progress Toast
    # and ends before the second-to-last </div> before </x-embed-layout>
    
    # Let's find </x-embed-layout> or </x-app-layout>
    embed_close = "</x-embed-layout>"
    app_close = "</x-app-layout>"
    close_tag = embed_close if embed_close in content else app_close
    close_idx = content.find(close_tag)
    
    # Extract everything from style_start to just before close_tag
    toast_block = content[style_idx:close_idx].strip()
    
    # Remove the toast block from its current position
    content_before = content[:style_idx]
    content_after = content[close_idx:]
    
    # Now content_before ends with:
    #             </div>
    #         </div>
    #     
    # The last </div> closes div.p-6. We need to insert toast BEFORE that last </div>
    
    # Find the last </div> before the close tag in the cleaned content
    # content_before should end with "        </div>\n    \n"
    # We need to insert before "        </div>\n"
    
    # Let's find the position of the last </div> in content_before
    last_div_close = content_before.rstrip().rfind("</div>")
    
    # Insert the toast block before this last </div>
    new_content = (
        content_before[:last_div_close] + 
        "\n\n        " + toast_block.replace("\n", "\n        ") + 
        "\n\n" + content_before[last_div_close:].lstrip("\n") +
        content_after
    )
    
    with open(filepath, 'w') as f:
        f.write(new_content)
    print(f"Fixed nesting in {filepath}")

fix_nesting('resources/views/embed/chatbot.blade.php')

import re

def update_logic(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    # 1. Update the increment logic
    old_logic = "if (this.aiJob.progress < 99) { this.aiJob.progress += (this.aiJob.progress >= 95 ? 0 : 2); }"
    new_logic = "if (this.aiJob.progress < 99) { this.aiJob.progress += (99 - this.aiJob.progress) * 0.03; }"
    content = content.replace(old_logic, new_logic)

    # 2. Update the text display to use Math.floor so it doesn't show long decimals
    old_text = "x-text=\"aiJob.progress + '%'\""
    new_text = "x-text=\"Math.floor(aiJob.progress) + '%'\""
    content = content.replace(old_text, new_text)
    
    # Wait, there's another place where aiJob.progress is used for text?
    # No, only one place: <span style="font-weight: 700; color: #334155;" x-text="aiJob.progress + '%'"></span>
    # Wait, there might be single quotes or double quotes?
    # In my previous code: x-text="aiJob.progress + '%'"

    with open(filepath, 'w') as f:
        f.write(content)
    print(f"Updated progress logic in {filepath}")

update_logic('resources/views/embed/chatbot.blade.php')
update_logic('resources/views/dashboard.blade.php')

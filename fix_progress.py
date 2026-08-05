import re

def fix_progress(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    # 1. Fix the progress bar logic so it caps at 99% until completed
    content = content.replace(
        "if (this.aiJob.progress < 99) this.aiJob.progress += 2;",
        "if (this.aiJob.progress < 99) { this.aiJob.progress += (this.aiJob.progress >= 95 ? 0 : 2); }"
    )

    # 2. Fix the style attribute of the progress bar colored div
    # The previous code was:
    # :style="(aiJob.status === 'completed' ? 'background: #22c55e;' : 'background: #4f46e5;') + ' width: ' + aiJob.progress + '%'"
    # We will replace it with object syntax for safety
    bad_style = ":style=\"(aiJob.status === 'completed' ? 'background: #22c55e;' : 'background: #4f46e5;') + ' width: ' + aiJob.progress + '%'\""
    good_style = ":style=\"{ backgroundColor: aiJob.status === 'completed' ? '#22c55e' : '#4f46e5', width: aiJob.progress + '%' }\""
    content = content.replace(bad_style, good_style)

    with open(filepath, 'w') as f:
        f.write(content)
    print(f"Fixed progress UI in {filepath}")

fix_progress('resources/views/embed/chatbot.blade.php')
fix_progress('resources/views/dashboard.blade.php')

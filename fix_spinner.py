import re

def fix_spinner(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    style_injection = """
<style>
@keyframes custom-ai-spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
"""

    # Ensure we don't inject multiple times
    if "custom-ai-spin" not in content:
        # Find where to inject. Let's put it right before the toast starts
        toast_start = "<!-- Floating AI Progress Toast -->"
        if toast_start in content:
            content = content.replace(toast_start, style_injection + toast_start)
    
    # Replace the animation property in the SVG
    content = content.replace(
        "animation: spin 1s linear infinite;",
        "animation: custom-ai-spin 1s linear infinite;"
    )

    with open(filepath, 'w') as f:
        f.write(content)
    print(f"Fixed spinner in {filepath}")

fix_spinner('resources/views/embed/chatbot.blade.php')
fix_spinner('resources/views/dashboard.blade.php')

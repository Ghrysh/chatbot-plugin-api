import re

def fix_license(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    # Add ?license parameter to fetch URLs
    content = content.replace("fetch('{{ route('knowledge.job-status') }}')", "fetch('{{ route('knowledge.job-status') }}?license={{ request('license') }}')")
    content = content.replace("fetch('{{ route('knowledge.job-cancel') }}'", "fetch('{{ route('knowledge.job-cancel') }}?license={{ request('license') }}'")

    with open(filepath, 'w') as f:
        f.write(content)
    print(f"Fixed {filepath}")

fix_license('resources/views/embed/chatbot.blade.php')
fix_license('resources/views/dashboard.blade.php')

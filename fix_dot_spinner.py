import re

def modify_file(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    # The dot spinner SVG to replace the old circle spinner
    dot_spinner_indigo_1 = '<svg x-show="progress < 10" class="w-6 h-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="currentColor"><circle cx="12" cy="2.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin="0s" repeatCount="indefinite"/></circle><circle cx="16.75" cy="3.77" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".083s" repeatCount="indefinite"/></circle><circle cx="20.23" cy="8.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".166s" repeatCount="indefinite"/></circle><circle cx="21.5" cy="12" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".25s" repeatCount="indefinite"/></circle><circle cx="20.23" cy="15.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".333s" repeatCount="indefinite"/></circle><circle cx="16.75" cy="20.23" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".416s" repeatCount="indefinite"/></circle><circle cx="12" cy="21.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".5s" repeatCount="indefinite"/></circle><circle cx="7.25" cy="20.23" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".583s" repeatCount="indefinite"/></circle><circle cx="3.77" cy="15.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".666s" repeatCount="indefinite"/></circle><circle cx="2.5" cy="12" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".75s" repeatCount="indefinite"/></circle><circle cx="3.77" cy="8.5" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".833s" repeatCount="indefinite"/></circle><circle cx="7.25" cy="3.77" r="1.5" opacity=".14"><animate attributeName="opacity" values="1;.14" dur="1s" begin=".916s" repeatCount="indefinite"/></circle></g></svg>'
    dot_spinner_indigo_2 = dot_spinner_indigo_1.replace('x-show="progress < 10"', 'x-show="progress >= 10 && progress < 40"')
    dot_spinner_indigo_3 = dot_spinner_indigo_1.replace('x-show="progress < 10"', 'x-show="progress >= 40 && progress < 99"')

    old_spinner_1 = '<svg x-show="progress < 10" class="w-6 h-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>'
    old_spinner_2 = '<svg x-show="progress >= 10 && progress < 40" class="w-6 h-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>'
    old_spinner_3 = '<svg x-show="progress >= 40 && progress < 99" class="w-6 h-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>'
    
    content = content.replace(old_spinner_1, dot_spinner_indigo_1)
    content = content.replace(old_spinner_2, dot_spinner_indigo_2)
    content = content.replace(old_spinner_3, dot_spinner_indigo_3)
    
    # 2. Slow down the interval from 500ms to 4000ms (so it takes 99*4=396 seconds = 6.6 minutes to reach 99%)
    content = content.replace('progress += 1; }, 500)', 'progress += 1; }, 4000)')

    with open(filepath, 'w') as f:
        f.write(content)
    print("Modified", filepath)

modify_file('resources/views/embed/chatbot.blade.php')
modify_file('resources/views/dashboard.blade.php')

import os
import re
import sys

# Configuration de l'audit
PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..', '..'))
TARGET_DIRS = ['public', 'src']
SAFE_FUNCTIONS = ['Security::sanitize', 'Security::filter', 'htmlspecialchars', 'intval', 'floatval', '(int)', '(float)']

# Regex de détection
RE_USER_INPUT = re.compile(r'\$_(GET|POST|REQUEST)\[[\'"](\w+)[\'"]\]')
RE_CSRF_CHECK = re.compile(r'Security::checkCsrf')
RE_LFI = re.compile(r'(include|require)(_once)?\s*\(?\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*')

def audit_file(filepath):
    vulnerabilities = []
    with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
        lines = f.readlines()
        has_csrf_check = False
        content = "".join(lines)
        
        if RE_CSRF_CHECK.search(content):
            has_csrf_check = True

        for i, line in enumerate(lines):
            line_no = i + 1
            
            # 1. Detection d'input non filtré
            inputs = RE_USER_INPUT.findall(line)
            for itype, key in inputs:
                is_safe = any(func in line for func in SAFE_FUNCTIONS)
                
                # Check previous line if not safe in current line
                if not is_safe and i > 0:
                    prev_line = lines[i-1]
                    is_safe = any(func in prev_line for func in SAFE_FUNCTIONS)
                
                if not is_safe:
                    vulnerabilities.append({
                        'type': 'UNFILTERED_INPUT',
                        'line': line_no,
                        'detail': f'Input $_({itype})["{key}"] potentially unsafe (missing sanitize)',
                        'content': line.strip()
                    })

            # 2. Check CSRF pour les POST
            if '$_POST' in line and not has_csrf_check:
                # On alerte si un fichier utilise $_POST sans jamais appeler checkCsrf
                vulnerabilities.append({
                    'type': 'MISSING_CSRF_CHECK',
                    'line': line_no,
                    'detail': 'File uses $_POST but no Security::checkCsrf found in scope',
                    'content': line.strip()
                })

            # 3. Detection LFI (Inclusion dynamique)
            lfi_match = RE_LFI.search(line)
            if lfi_match:
                vulnerabilities.append({
                    'type': 'POTENTIAL_LFI',
                    'line': line_no,
                    'detail': 'Dynamic file inclusion detected (potential LFI)',
                    'content': line.strip()
                })

    return vulnerabilities

def main():
    print(f"--- STARTING SECURITY AUDIT FOR {PROJECT_ROOT} ---")
    total_vulns = 0
    
    for folder in TARGET_DIRS:
        abs_path = os.path.join(PROJECT_ROOT, folder)
        for root, dirs, files in os.walk(abs_path):
            for file in files:
                if file.endswith('.php'):
                    filepath = os.path.join(root, file)
                    rel_path = os.path.relpath(filepath, PROJECT_ROOT)
                    vulns = audit_file(filepath)
                    
                    if vulns:
                        print(f"\n[!] FILE: {rel_path} - {len(vulns)} issues found")
                        for v in vulns:
                            print(f"    Line {v['line']}: [{v['type']}] {v['detail']}")
                            print(f"    > {v['content']}")
                        total_vulns += len(vulns)

    print(f"\n--- AUDIT COMPLETE: {total_vulns} potential vulnerabilities identified ---")

if __name__ == "__main__":
    main()

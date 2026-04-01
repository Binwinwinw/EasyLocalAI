# 📜 Sources of Truth
**LIMIT: MAX 60 LINES. KEEP BLUNT.**

| File | Priority | Purpose |
|------|----------|---------|
| [docs/Chartre.md](file:///d:/Hostinger/public_html/EasyLocalAI_V2/docs/Chartre.md) | **CRITICAL** | Sovereignty & EXCLUDE MergeLabs. |
| [docs/Conventions.md](file:///d:/Hostinger/public_html/EasyLocalAI_V2/docs/Conventions.md) | **HIGH** | Senior Patterns (CSRF, XSS, PSR-4). |
| [docs/Journal_de_bord.md](file:///d:/Hostinger/public_html/EasyLocalAI_V2/docs/Journal_de_bord.md) | **NORMAL** | History & Current Phase (1-25+). |

---

<!-- hacklm-memory:start -->
## Memory-Augmented Context
Read memory files on-demand. Hemingway style. Short sentences. No filler.

| File | Category | When to read |
|------|----------|-------------|
| [.memory/instructions.md](.memory/instructions.md) | Instruction | How to behave |
| [.memory/quirks.md](.memory/quirks.md) | Quirk | Project-specific weirdness |
| [.memory/preferences.md](.memory/preferences.md) | Preference | Style/design/naming |
| [.memory/decisions.md](.memory/decisions.md) | Decision | Architectural changes |
| [.memory/security.md](.memory/security.md) | **Security** | **ALWAYS — before any change** |

### Memory Tools
Call `queryMemory` before answering. Call `storeMemory` (kebab-case) when:
1. User states rule/preference. 2. User corrects you. 3. Fix occurs.
4. Each task end: store decision/pattern.

Same slug = update.
**Rule: This file MUST NOT exceed 60 lines.**
<!-- hacklm-memory:end -->

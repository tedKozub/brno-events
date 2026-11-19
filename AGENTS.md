# Engineering & Design Guidelines for AI Agents

These rules and principles are mandatory across all operations, code changes, and UI implementations.

---

## 1. Visual & Aesthetic Standards

### No Emojis
- **Never use emojis in UI:** No emojis in headings, titles, buttons, alert banners, toasts, placeholders, metadata tags, or footer links.
- **Never use emojis in code:** No emojis in source code, variable names, log statements, test descriptions, commit messages, or documentation.
- Rely on professional typography, clear microcopy, and standard punctuation.

### Minimal Iconography & Badges
- **Do not overuse icons:** Icons should clarify ambiguous actions, not decorate every single label or list item. If a text label is self-explanatory, do not prepend an icon.
- **Avoid badge proliferation:** Do not surround every data field with pill badges or colored tags. Badges are reserved exclusively for critical status indicators (e.g., event state, quantitative count >= 2).
- Keep interfaces calm, uncluttered, and content-focused.

---

## 2. Architecture & Code Quality

### Clean Architecture & Normalization
- **Third Normal Form (3NF) by default:** Do not duplicate persistent physical data (such as coordinates, venues, or organization details) across ephemeral records (such as events). Entities inherit coordinates via relational foreign keys.
- **Ingestion-time resolution:** Normalize, sanitize, and resolve relationships when data enters the system (during scraping, harvesting, or form submission). Never evaluate 500-line heuristic regex matching cascades at template render time.
- **Thin models, focused services:** Keep Eloquent/ORM models focused on schema definitions, relationships, and essential accessors. Move domain logic, external API integrations, and parsers into dedicated service classes.

### Robust Fallbacks & Graceful Degradation
- Handle missing data without crashing or inventing arbitrary placeholders.
- When geocoding or external lookups fail, preserve original raw strings for audit, log the failure, and degrade gracefully.

---

## 3. Static Analysis & Verification

### Never Guess — Verify with Tools
- Always run automated static analysis tools before finalizing changes:
  - **PHP:** Run `php -l` on modified files, enforce style via `pint`, and verify types.
  - **Embedded Scripts in Templates (Blade, Django, ERB):** Inline `<script>` tags inside HTML templates are invisible to backend linters. Always run the automated syntax checker (`npm run check:syntax` / `scripts/check-blade-syntax.js`) to guarantee zero syntax crashes.
  - **Python:** Run `unittest discover` and syntax verification before committing.
  - **Test Suite:** Execute the relevant unit/feature test suite (`./vendor/bin/phpunit`) for any affected subsystem.

### Pre-Commit Enforcing
- Use Git pre-commit hooks to automate static verification so broken syntax or malformed code can never be committed.

---

## 4. General Lessons Learned & Best Practices

### A. Timezone Consistency
- Never leave application timezone at default `UTC` when processing localized real-world events.
- Ensure `APP_TIMEZONE`, database session timezone, and OS timezone (`timedatectl`) are synchronized with the target locality (e.g., `Europe/Prague`).
- Ingested timestamps from local sources are local time. Comparing local times against a UTC clock causes silent multi-hour errors (e.g., countdowns displaying positive remaining time for events that ended hours ago).

### B. Geocoding & Rate-Limited APIs
- **Permanent Caching:** Always hash and persist external geocoding queries (`geocoding_cache`). Never query the same address twice.
- **Canonical ID Deduplication:** Geocoders return canonical identifiers (such as OSM `osm_place_id`). Use them to detect that two different text spellings represent the exact same physical place, merging them into aliases rather than duplicating database records.
- **Strict Bounding Boxes:** Constrain external geocoding queries with explicit geographical bounding boxes (e.g., city limits) to avoid matching identical street names in foreign cities.
- **Throttling:** Honor provider terms of service (e.g., 1 request per 1.5s for Nominatim) with enforced process sleeping.

### C. Git Hygiene on Protected Branches
- Production repositories often enforce branch protection on `main` / `master` disallowing force pushes (`git push --force`).
- Keep Git history linear, atomic, and cleanly committed. Never rebase public commits or attempt force-pushes.

# Teacher Room web — Figma V6 alignment

Reference: [ExamRoom Web Teacher Room](https://www.figma.com/design/Ibpq5J5h4VW5PrB49SHrbK?node-id=0-1), V6 SVG reference manifest supplied on 28 October 2026.

## Changes

- Match the V6 sidebar (226 px), topbar (65 px), content inset (28 px), typography, cards, table columns and form spacing.
- Place exam status tabs above the list and search/status/sort/refresh controls inside the table panel. Sorting and searching apply to the loaded page; pagination continues to use the API.
- Keep expired exam rows the same height. Reactivation remains available in the overflow menu, exam detail and mobile card.
- Add the session column to participant rows, align participant search/status controls and provide a working reset-filter button.
- Place private supervisor codes beside the generated entry code on desktop. Keep exit/resume codes distinct and private to the teacher.
- Consolidate mobile layouts: drawer navigation, two-column metrics, exam cards and compact participant cards; no desktop date or horizontal document overflow.
- Preserve all current authentication, school identity, exam, participant and public privacy/deletion behavior. The removed managed-device requirement stays removed.
- Bump the CSS/JS asset version to `20261028-v6`.

## Validation

- Browser inspection at widths 1600, 1440, 1280, 390 and 320 px; 47 screenshots across authentication, dashboard, exam list, creation, generated codes, participants, profile and public pages. No JavaScript errors or horizontal document overflow.
- Additional checks: desktop/mobile password reset, expired exam overflow menu and reactivation dialog, participant filter reset, mobile drawer and Escape.
- Access-flow regression: 14 checks covering separate exit/resume codes, entry-code copy privacy, reactivation, retained participants, invalidated old entry code and resume after exit.
- Laravel: 44 tests / 359 assertions passed. No backend or Android changes in this UI update.

## Apply on hosting

Pull `main`, then run `php artisan optimize:clear`. This UI update adds no dependencies or migrations. If the previous 3.1.0 feature release has not yet been deployed, follow `EXAMROOM-DEPLOY-3.1.0.md` for its existing migration and deployment steps. The website is not deployed automatically; the owner will pull it on hosting.

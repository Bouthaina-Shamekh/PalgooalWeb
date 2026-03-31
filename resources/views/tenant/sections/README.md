# Tenant Sections Structure

This directory uses a simple compatibility layout so section rendering stays stable while the code is easier to navigate.

Structure:

- `base/`
  - shared fallbacks such as `generic`
- `blocks/`
  - normal tenant page sections such as `hero`, `features`, `cta`, `testimonials`, `faq`, `menu`
- `shell/`
  - global site shell sections such as `site_header` and `site_footer`

Important:

- The root files like `hero.blade.php` and `site_header.blade.php` are compatibility shims.
- The real implementations live in the folders above.
- Keep the root shim file when adding a new section type so current includes like `tenant.sections.hero` continue to work.

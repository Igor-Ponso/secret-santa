<p align="center">
  <img 
    src="https://raw.githubusercontent.com/igor-ponso/secret-santa/main/.github/assets/banner.webp" 
    alt="Secret Santa Banner" 
    width="600"
/>
</p>

<p align="center">
  <a href="#"><img src="https://img.shields.io/badge/build-passing-brightgreen" alt="Build Status"></a>
  <a href="#"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="License"></a>
  <a href="#"><img src="https://img.shields.io/badge/stack-Laravel%20%2B%20Vue-red" alt="Stack"></a>
  <a href="#"><img src="https://img.shields.io/badge/tests-PEST%20%7C%20Vitest-yellow" alt="Test Suite"></a>
</p>

# Secret Santa 🎁

A modern and complete solution for organizing Secret Santa groups in a simple, fun, and secure way.

This project was built with a focus on great user experience, security, and solid fullstack development practices using the most up-to-date Laravel and Vue technologies.

---

## 🔧 Tech Stack

- [Laravel 12](https://laravel.com/docs/12.x) with [Inertia.js 2](https://inertiajs.com/)
- [Vue.js 3](https://vuejs.org/) with Composition API + [TypeScript](https://www.typescriptlang.org/)
- [Vite](https://vitejs.dev/)
- [ShadCN Vue](https://vue.shadcn.dev/) (UI components, accessible by default)
- [PEST](https://pestphp.com/) for backend testing
- [Vitest](https://vitest.dev/) for frontend unit/integration testing
- [MySQL](https://www.mysql.com/) as the main database
- [GitHub Actions](https://docs.github.com/en/actions) for CI automation

---

## 🎯 Key Features (Current)

- Authentication (register, login, password reset, email verification)
- Group creation & ownership transfer
- Invitation system with: accept / decline, revoke, resend, expiration handling
- Privacy: invite email hidden unless authenticated matching user
- Invitation onboarding flow (first-time wishlist setup or skip) with redirect logic
- Wishlist management (single + batch add up to 5 items, edit, delete)
- URL normalization (auto adds https:// if missing)
- Participant management (remove, approve join requests, prevent unsafe removals)
- Join code generation & regeneration
- Secret Santa draw with validation (single execution, recipient retrieval, basic metrics)
- Recipient wishlist visibility post-draw
- Basic metrics panel (placeholder / evolving)
- Internationalization (i18n) scaffolding (EN + pt_BR keys for Groups, Wishlist, Onboarding)
- UI polish using ShadCN Vue + Tailwind (Switch, Dialogs, Badges, etc.)

## 🗺️ Upcoming / Planned

- Gift value range per group
- Draw restrictions (exclusions)
- Public group landing page (pre-login)
- Dark mode toggle
- Email / notification events (wishlist changes, draw executed, reminders)
- Participant avatar uploads & display
- Recipient detail enhancements (notes, anonymized hints)
- Activity feed & group history
- Advanced metrics (engagement, completion rate)
- E2E tests (Playwright / Cypress) after feature stabilization

---

## 📸 Interface

> Designed to be responsive, accessible, and user-friendly, with smooth animations and a delightful UX.

---

## 🧪 Testing

Backend: PEST feature tests covering core flows:

- Invitation lifecycle (create, resend, revoke, accept/decline, privacy, invalid/expired, onboarding redirects)
- Group CRUD, join code, participant management, draw execution & recipient fetch
- Wishlist CRUD + batch creation + URL normalization

Added URL normalization tests (single + batch) to prevent regression.

Frontend: Vitest setup placeholder (tests to follow as UI stabilizes). CI (GitHub Actions) planned.

---

## 🚀 Getting Started Locally

1. Clone the repository:  
   `git clone https://github.com/igor-ponso/secret-santa.git`

2. Navigate to the project folder:  
   `cd secret-santa`

3. Install PHP dependencies:  
   `composer install`

4. Install JavaScript dependencies:  
   `npm install` (ou `bun install` se preferir)

5. Copy `.env.example` to `.env`:  
   `cp .env.example .env`

6. Generate the application key:  
   `php artisan key:generate`

7. Prepare database (SQLite example):  
   `touch database/database.sqlite`
   Update `.env` to use `DB_CONNECTION=sqlite`
   Then run migrations:  
   `php artisan migrate`

8. Start the Laravel development server:  
   `php artisan serve`

9. In another terminal, run frontend dev server (Vite):  
   `npm run dev`

10. Run backend test suite:  
    `php artisan test`

11. (Optional) Build assets for production:  
    `npm run build`

## 🔄 Architecture Notes

- Controllers kept thin; invitation, draw, and social auth logic in dedicated services.
- Policies enforce access for group & wishlist actions.
- Inertia shares auth + flash state; i18n loaded via JSON bundles.
- Batch wishlist creation is atomic (DB transaction).
- Draw logic ensures one-time run and prevents reassignment.

## 🌐 Internationalization

Currently supports `en` and `pt_BR` with JSON namespaces (groups, wishlist, onboarding). Adding a new locale is as simple as dropping a new JSON file under `resources/js/languages/<locale>` mirroring existing keys.

## 🛡️ Security Considerations

- Invitation tokens stored hashed; plain token only available at creation.
- Email privacy for invitations (only visible to intended invitee while authenticated).
- CSRF + session hardening via Laravel defaults.
- URL normalization helps reduce malformed external links.

## 🧩 Draw Mechanics (Current State)

- Simple random assignment ensuring no self-draw.
- Future: exclusions + retries + fairness metrics.

## 📈 Metrics & Observability

- Basic metrics panel placeholder (participants, wishlist counts, draw state).
- Future: integrate activity log & audit trail.

## 🗃️ Data Model Highlights

- Groups own invitations, participants (implicit via assignments/wishlists), join requests, assignments, wishlists.
- Invitations lifecycle: pending → (accepted|declined|revoked|expired).

## 🤝 Contributing

PRs welcome. Please include or update tests for any behavior changes. Prefer small, focused commits.

## ✅ Changelog Snapshot (Recent)

- Added onboarding flow after invitation acceptance.
- Added batch wishlist addition + Switch UI toggle.
- Added “My Wishlist” quick access button.
- Added URL normalization (single + batch) + tests.
- Adjusted invitation invalid token handling (renders status instead of 404).
- Added participant recipient wishlist visibility post-draw.
- Improved invitation privacy + status views.
- Added join code regeneration + related tests.
- Refactored i18n keys for groups & wishlist pages.

---

## 🙌 Contributions

Contributions are welcome!  
Feel free to open Issues or Pull Requests with improvements, bug fixes, or suggestions.

---

## 📜 License

This project is licensed under the [MIT License](LICENSE).

# 📍 Secret Santa — Roadmap

Welcome to the Secret Santa project's official development roadmap.  
This file outlines the major development phases, priorities, and best practices that guide the entire project lifecycle.

---

## 🚀 Project Overview

A modern fullstack web app to manage Secret Santa groups with gift budgets, custom draw rules, user-friendly flows, and scalable backend logic.  
Built with Laravel 12, Inertia.js 2, Vue 3, Pinia, TypeScript, and ShadCN Vue.

> Goal: To deliver a clean, secure, and delightful experience for both organizers and participants.

---

## 🧱 Phase 1 — Foundation & Auth (Status: Mostly Complete)

**Delivered:**

- [x] Laravel 12 + Inertia + Vue 3 base
- [x] Auth (login / register / reset / email verification)
- [x] Profile update (name/email/password) & password confirmation
- [x] Feature tests (auth flows)
- [x] Basic layout shell (iterating)

**Pending / Nice to Have:**

- [ ] Sidebar + richer nav structure
- [ ] Avatar upload (profile photo column added; UI pending)
- [ ] Pinia stores (using direct Inertia props for now)
- [ ] Vitest unit tests for auth store (post store introduction)

---

## 🎁 Phase 2 — Group & Draw Core (Status: In Progress)

**Delivered:**

- [x] Group creation (name, description)
- [x] Participant list & counts
- [x] Join code generation & regeneration
- [x] Invitation lifecycle (create, resend, revoke, accept, decline, expire)
- [x] Invitation privacy & status landing (no 404 for invalid)
- [x] Draw service (single-run enforcement + assignments)
- [x] Recipient fetch + wishlist display
- [x] Participant management (remove with constraints, ownership transfer)
- [x] Join requests (approve / deny, code join)
- [x] Policies & access control additions
- [x] Extensive feature test coverage

**Pending / Planned:**

- [ ] Min/max gift value fields
- [x] Draw restrictions (exclusions matrix + preview + sample)
- [x] Secure audit of assignment storage at rest (encrypted receiver assignments)
- [ ] Enhanced metrics (draw readiness, wishlist completion %)
- [ ] Frontend Vitest coverage for critical flows

---

## 🌐 Phase 3 — Public + UX Polish (Status: Early)

**Delivered:**

- [x] i18n scaffolding (en, pt_BR)
- [x] Wishlist quick-access button
- [x] Batch wishlist add mode + Switch UI

**Planned:**

- [ ] Public group landing + countdown
- [ ] Dark Mode toggle
- [ ] Avatar rendering (after upload UI)
- [ ] Leave group (self removal) flow
- [ ] Improved mobile density / navigation
- [ ] Email notifications (draw executed, reminders, wishlist change)
- [ ] Recipient enhancement (anonymized hint, gift guidelines)

---

## 📦 Phase 4 — DevOps & Production Ready (Status: Upcoming)

**Planned:**

- [ ] Query review & indexing pass
- [ ] Formalize service boundaries + Form Request adoption everywhere
- [ ] PHPDocs + type improvements
- [ ] GitHub Actions CI (PEST + future Vitest)
- [ ] Deployment pipeline (Forge / container)
- [ ] Privacy-friendly analytics (Umami / Plausible)
- [ ] Manual QA + accessibility audit

**Partially Done:**

- [x] Service layer started (InvitationService, DrawService, GroupService in progress)
- [x] README badges & install docs

---

## ✅ Current Practices & Guidelines

- Controllers lean; heavy logic in services (continuing refactors)
- Validation currently inline in some controllers; migrating toward Form Requests
- Inertia responses only include needed fields (manual shaping)
- Tests required for new feature merges
- Prefer localization keys over hardcoded strings
- Avoid premature optimization; watch for N+1 in group show/draw flows

---

## 📘 Notes

- SQLite used locally for fast iteration; MySQL planned for staging/prod
- Inertia + Vue approach avoids duplicating routing logic
- E2E (Playwright) will be evaluated after core features freeze

---

Let’s build something solid, fun, and developer-friendly.

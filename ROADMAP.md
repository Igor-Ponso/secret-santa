# 📍 Secret Santa — Roadmap

Welcome to the Secret Santa project's official development roadmap.  
This file outlines the major development phases, priorities, and best practices that guide the entire project lifecycle.

---

## 🚀 Project Overview

A modern fullstack web app to manage Secret Santa groups with gift budgets, custom draw rules, user-friendly flows, and scalable backend logic.  
Built with Laravel 12, Inertia.js 2, Vue 3, Pinia, TypeScript, and ShadCN Vue.

> Goal: To deliver a clean, secure, and delightful experience for both organizers and participants.

---

## 🧱 Phase 1 — Foundation & Auth (Status: Complete)

**Delivered:**

- [x] Laravel 12 + Inertia + Vue 3 base
- [x] Auth (login / register / reset / email verification)
- [x] Profile update (name/email/password) & password confirmation
- [x] Feature tests (auth flows)
- [x] Basic layout shell (iterating)

**Pending / Nice to Have:**

- [ ] Sidebar + richer nav structure
- [ ] Pinia stores (using direct Inertia props for now)
- [ ] Vitest unit tests for auth store (post store introduction)

---

## 🎁 Phase 2 — Group & Draw Core (Status: Complete)

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

**Delivered (Extended):**

- [x] Min draw/date-only scheduling (`draw_at` simplified to YYYY-MM-DD)
- [x] Draw restrictions (exclusions matrix + feasibility preview + sample mapping)
- [x] Encrypted assignment storage (versioned ciphers, rotation & verification commands)
- [x] Auto-draw scheduled command for due groups
- [x] Share links + attributed join requests
- [x] Min/max gift value fields (model, validation, service wiring + UI render)

**Pending / Planned:**

- [ ] Enhanced metrics (readiness %, wishlist coverage panel UI)
- [ ] Frontend Vitest coverage for critical flows

---

## 🌐 Phase 3 — User Experience & Engagement (Status: In Progress)

**Delivered:**

- [x] i18n scaffolding (en, pt_BR)
- [x] Wishlist quick-access button
- [x] Batch wishlist add mode + Switch UI
- [x] Avatar upload backend (storage + removal / profile update)
- [x] Initial notifications (invitation + draw result)
- [x] Dark mode toggle component (`ThemeToggle.vue` + composable persistence)

**Planned / Next:**

- [ ] Public group landing + countdown
- [ ] Avatar rendering in participant & invitation lists (frontend usage in all contexts)
- [ ] Leave group (self-removal) flow
- [ ] Improved mobile density / navigation
- [ ] Email notifications expansion (wishlist change reminders, inactivity nudges)
- [ ] Recipient enhancements (anonymized hint, gift guidelines, notes)
- [ ] Activity feed & group history timeline
- [ ] Metrics panel (expanded readiness / participation)

---

## 📦 Phase 4 — DevOps, Quality & Observability (Status: Upcoming)

**Planned:**

- [ ] Query review & indexing pass
- [ ] Formalize service boundaries + Form Request adoption everywhere
- [ ] PHPDocs + type improvements / PHPStan stricter level
- [ ] GitHub Actions CI (PEST + Vitest + later Playwright)
- [ ] Containerized deploy (Docker/Forge or CI CD pipeline)
- [ ] Privacy-friendly analytics (Umami / Plausible)
- [ ] Error tracking (Sentry / Bugsnag)
- [ ] Structured logging & request correlation IDs
- [ ] Manual QA + accessibility audit (WCAG baseline)
- [ ] Health/metrics endpoint (readiness & liveness probes)

**Partially Done:**

- [x] Service layer started (InvitationService, DrawService, GroupService in progress)
- [x] README badges & install docs

---

## 🔐 Phase 5 — Security & Data Lifecycle (Status: Planned)

**Scope:** Hardening encryption, lifecycle policies, abuse prevention.

**Planned:**

- [ ] Drop legacy nullable `receiver_user_id` column after stability window
- [ ] Scheduled automated cipher verification job
- [ ] Rate limiting / abuse protection on invitation & share link endpoints
- [ ] Data retention policy (e.g., optional purge X days post draw)
- [ ] Secret rotation playbook (APP_KEY & future data keys)
- [ ] Audit logging (sensitive admin / ownership changes)
- [ ] Explore per-group envelope encryption (future `v2` plan)

**Future Research:**

- KMS integration for master key wrapping
- Tamper alerts if cipher verification fails

## 📈 Phase 6 — Scalability & Advanced Features (Status: Backlog)

**Targets:** Performance resilience for larger groups, richer analytics, predictive tooling.

**Backlog Candidates:**

- [ ] Caching / precomputation for readiness metrics
- [ ] Load / stress tests for draw solver at scale
- [ ] Property-based fuzz tests for exclusion graphs
- [ ] Activity & audit event sourcing (append-only log)
- [ ] Graph visualization of exclusions
- [ ] Advanced engagement analytics (conversion, retention)
- [ ] Multi-locale expansion (additional languages beyond en / pt_BR)
- [ ] Envelope encryption proof-of-concept
- [ ] Scheduled rotation orchestration CLI wrapper

## ✅ Current Practices & Guidelines

- Controllers lean; heavy logic in services (continuing refactors)
- Validation currently inline in some controllers; migrating toward Form Requests
- Inertia responses only include needed fields (manual shaping)
- Tests required for new feature merges
- Prefer localization keys over hardcoded strings
- Avoid premature optimization; watch for N+1 in group show/draw flows

---

## 🗂️ Documentation References

- Encryption & rotation: `docs/ENCRYPTION.md`
- Draw mechanics & exclusions: `docs/DRAW_MECHANICS.md`
- Share links & join requests: `docs/SHARE_LINKS.md`
- Invitation payload contract: `docs/INVITATION_PAYLOAD.md`
- Draw date rules: `docs/DRAW_DATE.md`

## 📘 Notes

- SQLite used locally for fast iteration; MySQL planned for staging/prod
- Inertia + Vue approach avoids duplicating routing logic
- E2E (Playwright) will be evaluated after core features freeze

---

## 🧰 Backlog (Unsorted Quick Ideas)

- Skeleton loading & optimistic UI states
- Offline-friendly wishlist edits (queued sync)
- Admin dashboard for support / moderation
- Gamified achievements / badges
- Integration webhook for external gift registries
- Timeboxed ephemeral hints between participants

---

Let’s build something solid, fun, and developer-friendly.

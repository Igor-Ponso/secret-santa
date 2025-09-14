# 📍 Secret Santa — Roadmap

Welcome to the Secret Santa project's official development roadmap.  
This file outlines the major development phases, priorities, and best practices that guide the entire project lifecycle.

---

## 🚀 Project Overview

A modern fullstack web app to manage Secret Santa groups with gift budgets, custom draw rules, user-friendly flows, and scalable backend logic.  
Built with Laravel 12, Inertia.js 2, Vue 3, Pinia, TypeScript, and ShadCN Vue.

> Goal: To deliver a clean, secure, and delightful experience for both organizers and participants.

---

## 🧱 Phase 1 — Foundation & Auth

**Goals:** Setup initial layout, structure, and authentication.

- [x] Setup Laravel 12 + Inertia + Vue 3 Starter Kit
- [x] Ensure authentication works (login, register, reset password)
- [ ] Create layout with sidebar + topbar (ShadCN components)
- [ ] Add avatar upload on profile page
- [ ] Add ability to update name/email/password
- [ ] Ensure all routes are protected by middleware
- [ ] Add Setup Pinia Stores for user/auth state
- [ ] Add PEST feature tests for auth
- [ ] Add Vitest unit test for user store

---

## 🎁 Phase 2 — Group & Draw Core

**Goals:** Allow creating and joining groups, managing participants, and performing the Secret Santa draw.

- [ ] Group creation form: name, description, min/max gift value, draw date
- [ ] Generate and share invite link (`/join/{token}`)
- [ ] Allow users to join via invite
- [ ] Add participant list (including guest entries)
- [ ] Add optional draw restrictions ("X cannot draw Y")
- [ ] Implement draw algorithm (Eloquent service)
- [ ] Store who drew who securely
- [ ] Add Laravel authorization policies for group access
- [ ] Add full PEST coverage for GroupController, DrawService
- [ ] Add frontend tests with Vitest for forms and flow

---

## 🌐 Phase 3 — Public + UX Polish

**Goals:** Make it beautiful, responsive, and user-first.

- [ ] Public group landing page with countdown
- [ ] Display draw result privately to each user
- [ ] Add Dark Mode toggle
- [ ] Mobile-first UI polish
- [ ] Add i18n support
- [ ] Add avatars to participant list
- [ ] Add "Leave group" or "Delete my participation"
- [ ] Add email notifications (draw reminder, results)

---

## 📦 Phase 4 — DevOps & Production Ready

**Goals:** Optimize performance, enforce best practices, and prepare for release.

- [ ] Review queries (N+1, eager loading, indexing)
- [ ] Use Laravel model binding everywhere (no IDs in requests)
- [ ] Enforce clean service layer (`DrawService`, `GroupService`)
- [ ] Add PHPDocs on all methods
- [ ] Setup CI with GitHub Actions for PEST/Vitest on PR
- [ ] Deploy to Forge or Vercel
- [ ] Add analytics (privacy-friendly)
- [ ] Polish README with badge, demo GIF, install instructions
- [ ] Manual QA checklist

---

## ✅ Best Practices

- Use Setup Stores and TypeScript on all Pinia state
- Keep controllers thin, move logic to services
- Validate with Form Requests
- Use Resources for API formatting
- Write PEST + Vitest tests for all new features
- Comments only when something is not obvious
- Prioritize performance — paginate, eager load, index

---

## 📘 Notes

- DB: MySQL is more than enough (only avatars will be stored)
- Frontend is fully Inertia + Vue 3 — no need for SPA routing
- Consider Cypress or Playwright for final E2E flows (optional)

---

Let’s build something solid, fun, and developer-friendly.

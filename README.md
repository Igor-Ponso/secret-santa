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
- [Vite](https://vitejs.dev/)
- [ShadCN Vue](https://vue.shadcn.dev/) (UI components, accessible by default)
- [PEST](https://pestphp.com/) for backend testing
- [Vitest](https://vitest.dev/) for frontend unit/integration testing
- [MySQL](https://www.mysql.com/) as the main database
- [GitHub Actions](https://docs.github.com/en/actions) for CI automation

---

## 🎯 Key Features (Current)

- Authentication (register, login, password reset, email verification)
- Invitation system with: accept / decline, revoke, resend, expiration handling
- Privacy: invite email hidden unless authenticated matching user
- Invitation onboarding flow (first-time wishlist setup or skip) with redirect logic
- Wishlist management (single + batch add up to 5 items, edit, delete)
- URL normalization (auto adds https:// if missing)
- Participant management (remove, approve join requests, prevent unsafe removals)
- Join code generation & regeneration
- Secret Santa draw with validation (single execution, recipient retrieval, basic metrics)
- Recipient wishlist visibility post-draw
- Encrypted at-rest assignments (receiver stored ciphered; plain id not exposed in queries)

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
- Assignment receiver user IDs are stored encrypted (`receiver_cipher`) to mitigate accidental data exposure (only decrypted at access time).

## 🧩 Draw Mechanics (Current State)

- Backtracking + heuristic assignment (most constrained first) ensuring:
    - No self-assignment
    - Exclusion rules respected (user -> cannot gift -> excluded_user)
    - Deterministic feasibility check with guarded search limit
- Preview endpoint (`GET /groups/{group}/exclusions/preview`) returns:
    - `feasible: boolean`
    - `sample: { [giver_user_id]: receiver_user_id } | null`
    - Localized message (`messages.exclusions.preview.*`)
- On impossibility (e.g. user excludes everyone else) create action rolls back and emits toast (`flash.error`).

### Exclusions API

| Action              | Method & Path                                   | Notes                                                             |
| ------------------- | ----------------------------------------------- | ----------------------------------------------------------------- |
| Create exclusion    | `POST /groups/{group}/exclusions`               | Body: `user_id`, `excluded_user_id`, optional `reciprocal` (bool) |
| Delete exclusion    | `DELETE /groups/{group}/exclusions/{exclusion}` | Owner only, blocked after draw                                    |
| Preview feasibility | `GET /groups/{group}/exclusions/preview`        | Returns feasibility + sample mapping                              |

Validation rules:

- Both users must be accepted participants (or owner)
- Not self, distinct ids
- Duplicate or inverse pair rejected with `messages.exclusions.duplicate`
- Locked after draw (`messages.exclusions.locked_after_draw`)
- Impossible state yields toast (not field error) with `messages.exclusions.impossible`

Front-end UX recommendation:

- After each successful create/delete, re-fetch preview to update a badge (e.g. “Viável” / “Inválido”).
- Disable “Adicionar” button if preview reports infeasible to prevent surprise failures.

I18n keys added (`messages.exclusions.*`) across `en`, `pt_BR`, `fr`.

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
- Added exclusions service (creation, deletion, feasibility preview + sample)
- Implemented backtracking draw solver with heuristic ordering
- Added preview endpoint and i18n messages for exclusions

---

## 🔗 Share Links & Join Requests Flow

To broaden group growth beyond direct emailed invitations, the platform now supports persistent share links with automatic join request attribution.

### Generation & Rotation

Owner requests a share link (endpoint: `groups/{group}/invitation-link`). Service returns a fresh plain token every call (rotates previous) and stores only the SHA-256 hash in `group_share_links`.

### Public Landing Behavior

Visiting `/invites/{token}` resolves in this order:

1. Standard invitation (email-based) lookup.
2. Fallback to share link lookup.

If token maps to a share link:

- Status returned: `share_link` (instead of `invalid`).
- Authenticated non-participants see a “request join” button.
- Guests have the token persisted in session as `pending_share_token`.

### Session Persistence & Post-Registration Flow

When a guest registers or logs in later:

1. `pending_share_token` is consumed.
2. If user is neither owner nor participant and no existing join request exists → a `GroupJoinRequest` is created (`status = pending`).
3. Attribution recorded via `share_link_id` (FK) on the join request for conversion metrics.
4. Flash success message (“Pedido de entrada enviado…”) is injected.

### Dashboard & History

- Dashboard panel now lists the user’s pending join requests (quick visibility).
- Dedicated history page: `/join-requests` (filter by status) shows origin (share link vs manual) and resolution timestamps.

### Data Model Additions

| Table                 | Column                        | Purpose                                     |
| --------------------- | ----------------------------- | ------------------------------------------- |
| `group_share_links`   | `token` (hashed)              | Secure storage of share link token (sha256) |
| `group_join_requests` | `share_link_id` (nullable FK) | Attribution of conversion source            |

### Origin Semantics

`origin = share_link` if `share_link_id` present, otherwise treated as manual (code entry or owner-initiated approval flow).

### Security & Privacy

- Same hashing strategy as invitations (plain token never stored).
- Rotation invalidates previous plain token (no reuse after regeneration).
- Join request prevents duplicate spam (unique `group_id` + `user_id`).

### Testing Coverage

- `ShareLinkInvitationTest` ensures no phantom invitation rows are created.
- `ShareLinkRegistrationFlowTest` ensures post-registration attribution and pending request creation.

### Future Enhancements (Ideas)

- Revocation / disable share link endpoint (soft delete + UI indicator).
- Conversion analytics: per share link accepted vs pending vs denied funnel.
- Multi-share links (segmented campaigns per owner).
- Expirable share links (`expires_at`).

---

---

## � Invitation Payload (Viewer Refactor)

The invitation landing endpoint (`/invites/{token}`) now returns a normalized structure:

```
invitation: {
   group: { id, name, description } | null,
   inviter: { id, name } | null,
   email: string|null,            // Only if viewer email matches invite
   status: 'pending'|'accepted'|'declined'|'revoked'|'expired'|'invalid',
   expired: boolean,
   revoked: boolean,
   token: string|null,
   viewer: {
      authenticated: boolean,
      participates: boolean,       // If true user is redirected to group.show
      is_owner: boolean,
      email_mismatch: boolean,
      can_accept: boolean,
      can_request_join: boolean,
      join_requested: boolean
   }
}
```

Behavior:

1. If viewer already participates (owner or accepted) server issues a 302 redirect → `groups.show`.
2. `can_accept` and `can_request_join` are mutually exclusive.
3. `join_requested` disables the join button client-side.
4. Email is omitted unless the authenticated viewer matches the invitation email.

## ⏰ Draw Date (`draw_at`) Rules

`draw_at` agora é somente DATA (formato `YYYY-MM-DD`, sem hora/minuto). Motivações:

1. Simplifica a regra de negócio – o sorteio é um evento do dia, não de horário.
2. Evita discrepâncias de fuso / DST.
3. Permite batch noturno simples (`groups:run-due-draws`) sem preocupações de granularidade.

Validação: `required | date_format:Y-m-d | after_or_equal:today` nas requests de create/update.

Persistência: Cast como `date` no model `Group`. O service normaliza qualquer entrada para `Y-m-d`.

Execução Automática:

- Comando agendado diário (`00:05`) roda sorteios vencidos (grupos com `draw_at <= hoje` e ainda sem draw).
- Dono pode rodar manualmente antes via UI (futuro) ou esperar o batch.

### Sorteio Manual e Banner de Status

O dono pode executar manualmente o sorteio assim que houver pelo menos 2 participantes (dono + 1 aceito), mesmo antes da data configurada. A página do grupo mostra um banner com:

- Dias restantes até a data (`days_until_draw`)
- Mensagem se é hoje ou se a data já passou
- Botão "Executar sorteio manual" (somente dono, enquanto não houver sorteio)

### Imutabilidade Pós-Sorteio

Após `has_draw = true`:

- Política (`GroupPolicy@update`) bloqueia qualquer edição → respostas 403.
- UI remove botão de editar cabeçalho e mostra selo de bloqueio.
- Mantém consistência das regras do jogo para todos os participantes.

Testes:

- `GroupDrawDateValidationTest` (create)
- `GroupDrawDateUpdateValidationTest` (update)
- Ajustes no `GroupTest` para usar `toDateString()`.

Frontend:

- `DateTimePicker` substituído por calendário simples (date-only) nos formulários de criação/edição.
- Seleção inferior à data atual desabilitada visualmente (cells `data-disabled`).

Impacto de Migração:

- Se havia valores com horário previamente, apenas a parte da data é relevante agora.
- Qualquer lógica futura baseada em horário deve ser reprojetada.

## 🤝 Join Requests via Invite Page

If the invitation cannot be accepted by the authenticated user (email mismatch / expired / revoked) but the group is valid and user not a participant, `viewer.can_request_join` becomes true. A join request button appears and afterwards `join_requested` flips to true, disabling the button.

## 🧱 Resource Layer

`InvitationResource` centralizes serialization and viewer flag logic, reducing duplication and making future changes (e.g. adding analytics counters) safer.

---

## �🙌 Contributions

Contributions are welcome!  
Feel free to open Issues or Pull Requests with improvements, bug fixes, or suggestions.

---

## 📜 License

This project is licensed under the [MIT License](LICENSE).

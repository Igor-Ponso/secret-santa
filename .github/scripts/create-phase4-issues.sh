#!/bin/bash

# Script para criar issues da Fase 4 do Roadmap
# Repositório: igor-ponso/secret-santa

echo 'WARNING: This script will create PHASE 4 issues directly in the repository: igor-ponso/secret-santa'
read -p 'Are you sure you want to proceed? (y/N) ' confirm
if [[ $confirm != "y" ]]; then
  echo 'Aborted.'
  exit 1
fi

echo 'Creating PHASE 4 issues on GitHub...'

gh issue create --repo igor-ponso/secret-santa --title "Review and Optimize DB Queries (N+1, Indexes)" --body "## Issue Description

To ensure performance and scalability, we must review all Eloquent queries for possible inefficiencies.

### Fixes & Improvements
- Identify and fix N+1 query problems
- Add indexes where needed (e.g., foreign keys, tokens)
- Use eager loading for common relationships

### Affected Locations
- DrawService
- GroupController
- UserController

### Acceptance Criteria
- [ ] No detectable N+1 issues in logs
- [ ] Indexes present on critical columns
- [ ] Performance is monitored and tested

### Expected Outcome

The app avoids unnecessary DB queries and can scale with larger groups.
"
gh issue create --repo igor-ponso/secret-santa --title "Enforce Laravel Model Binding Across All Routes" --body "## Issue Description

All routes must exclusively use model binding to improve security, clarity, and consistency.

### Fixes & Improvements
- Refactor routes to avoid manual `find()` or ID usage
- Update controllers to type-hint models
- Restrict access via policies where needed

### Affected Locations
- routes/web.php
- GroupController
- DrawController

### Acceptance Criteria
- [ ] No route uses raw IDs
- [ ] All resource access is bound to model instances
- [ ] Policies protect access based on ownership

### Expected Outcome

Safer and more expressive route handling across the application.
"
gh issue create --repo igor-ponso/secret-santa --title "Add PHPDocs Across Services and Controllers" --body "## Issue Description

Maintain full documentation across the codebase to improve maintainability and IDE support.

### Fixes & Improvements
- Add PHPDocs to all public service methods
- Annotate controller actions with types
- Describe complex return types and custom helpers

### Affected Locations
- DrawService
- GroupService
- All controllers

### Acceptance Criteria
- [ ] All methods have return/param PHPDoc annotations
- [ ] No IDE errors for type inference
- [ ] New contributors can understand logic easily

### Expected Outcome

Cleaner, more self-documented code across all business logic.
"
gh issue create --repo igor-ponso/secret-santa --title "Setup GitHub Actions CI for PEST and Vitest" --body "## Issue Description

Ensure code quality with continuous integration for backend and frontend test suites.

### Fixes & Improvements
- Create GitHub Action workflow to run PEST
- Extend workflow to run Vitest on frontend
- Add badge to README for CI status

### Affected Locations
- .github/workflows/ci.yml
- README.md

### Acceptance Criteria
- [ ] CI runs on PRs and pushes to `main`
- [ ] Both PEST and Vitest are executed
- [ ] Failures block merge

### Expected Outcome

Code is continuously tested, and quality is enforced before merging to main.
"
gh issue create --repo igor-ponso/secret-santa --title "Deploy Production Build (Forge or Vercel)" --body "## Issue Description

Prepare a production-ready deployment with secure environment config and optimized build.

### Fixes & Improvements
- Choose deployment provider (Forge, Laravel Vapor, Vercel)
- Set up .env.production securely
- Enable SSR for Inertia (if ready)

### Affected Locations
- .env.production
- vite.config.ts
- deployment target

### Acceptance Criteria
- [ ] Production site is live and accessible
- [ ] Sensitive data is secured
- [ ] SSR works if enabled

### Expected Outcome

The app is deployed to a stable production environment and accessible to users.
"
gh issue create --repo igor-ponso/secret-santa --title "Add Manual QA Checklist" --body "## Issue Description

Before public release, ensure that a human QA checklist is followed for all critical paths.

### Fixes & Improvements
- Create markdown checklist for flows like join, draw, edit profile
- Test on desktop and mobile
- Log any visual or logical errors

### Affected Locations
- QA.md
- Docs/
- DevOps

### Acceptance Criteria
- [ ] Checklist exists and is reviewed on every release
- [ ] All flows tested and signed off
- [ ] Checklist is linkable in PRs

### Expected Outcome

Each release is QA-verified and avoids regressions in essential flows.
"
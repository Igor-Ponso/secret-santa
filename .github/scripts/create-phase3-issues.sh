#!/bin/bash

# Script para criar issues da Fase 3 do Roadmap
# Repositório: igor-ponso/secret-santa

echo 'WARNING: This script will create PHASE 3 issues directly in the repository: igor-ponso/secret-santa'
read -p 'Are you sure you want to proceed? (y/N) ' confirm
if [[ $confirm != "y" ]]; then
  echo 'Aborted.'
  exit 1
fi

echo 'Creating PHASE 3 issues on GitHub...'

gh issue create --repo igor-ponso/secret-santa --title "Create Public Group Landing Page" --body "## Issue Description

A public-facing page is needed to show basic info about the Secret Santa group for invited users.

### Fixes & Improvements
- Display group name, description, draw date, and countdown
- Optionally show participant count or avatars
- Make route public: `/groups/public/{slug}` or similar

### Affected Locations
- PublicGroupPage.vue
- routes/web.php
- GroupController

### Acceptance Criteria
- [ ] Page is publicly accessible via link
- [ ] Shows essential group info
- [ ] Handles invalid or expired links gracefully

### Expected Outcome

Guests can view the public group page and decide to join via a clean, shareable URL.
"
gh issue create --repo igor-ponso/secret-santa --title "Display Private Draw Result to Each Participant" --body "## Issue Description

Once the draw is complete, each participant should see who they drew, in a private and secure way.

### Fixes & Improvements
- Add route to view draw result (e.g. `/my-draw/{group}`)
- Ensure only the correct participant can see their result
- Prevent reveal before draw is finalized

### Affected Locations
- DrawResult.vue
- routes/web.php
- DrawController

### Acceptance Criteria
- [ ] Only authenticated participant can view their result
- [ ] No result shown before the draw
- [ ] Draw data is fetched securely

### Expected Outcome

Each participant can see who they drew, without exposing other results or compromising privacy.
"
gh issue create --repo igor-ponso/secret-santa --title "Add Dark Mode Toggle" --body "## Issue Description

To improve accessibility and user preference, add a dark mode toggle switch across the app.

### Fixes & Improvements
- Use ShadCN and Tailwind dark mode utilities
- Persist user preference (localStorage or DB)
- Ensure all components adapt properly

### Affected Locations
- App.vue
- layouts/Default.vue
- Settings.vue

### Acceptance Criteria
- [ ] Toggle switches between dark and light
- [ ] Preference is saved and restored
- [ ] No style or layout issues in either mode

### Expected Outcome

Users can switch between light and dark modes for a more comfortable experience.
"
gh issue create --repo igor-ponso/secret-santa --title "Ensure Mobile-first Responsiveness Across Pages" --body "## Issue Description

The entire app should be reviewed and polished for mobile-first responsiveness using utility classes.

### Fixes & Improvements
- Audit all components for mobile responsiveness
- Use Tailwind’s responsive utilities where needed
- Ensure buttons, forms, and text scale properly

### Affected Locations
- All page components
- Form layouts
- Draw & Profile views

### Acceptance Criteria
- [ ] No layout breaks on mobile viewports
- [ ] Text and buttons remain usable
- [ ] Drawer and modals behave properly

### Expected Outcome

Mobile users can comfortably use the platform without zooming or layout glitches.
"
gh issue create --repo igor-ponso/secret-santa --title "Add i18n Support for Multiple Languages" --body "## Issue Description

To support a diverse audience, the app should support internationalization via a Vue i18n setup.

### Fixes & Improvements
- Install and configure `vue-i18n`
- Move static strings to locale files
- Default to English, support Portuguese

### Affected Locations
- App.vue
- components
- views
- locales/en.json
- locales/pt.json

### Acceptance Criteria
- [ ] Text is dynamically translated
- [ ] Language can be toggled by user
- [ ] New components follow i18n structure

### Expected Outcome

The app will support multiple languages and adapt to different audiences more effectively.
"
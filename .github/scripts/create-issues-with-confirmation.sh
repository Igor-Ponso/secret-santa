#!/bin/bash

# Script para criar issues no GitHub usando o CLI
# Repositório: igor-ponso/secret-santa

echo 'WARNING: This script will create issues directly in the repository: igor-ponso/secret-santa'
read -p 'Are you sure you want to proceed? (y/N) ' confirm
if [[ $confirm != "y" ]]; then
  echo 'Aborted.'
  exit 1
fi

echo 'Creating issues on GitHub...'

gh issue create --repo igor-ponso/secret-santa --title "Create App Layout (Sidebar + Topbar)" --body "## Issue Description

We need a base layout using ShadCN components to wrap all internal pages with navigation and branding.

### Fixes & Improvements
- Implement layout with responsive sidebar and topbar
- Include slots/props for child views

### Affected Locations
- App.vue
- Dashboard.vue
- layouts/Default.vue

### Acceptance Criteria
- [ ] Layout is responsive
- [ ] Sidebar and topbar are reusable
- [ ] All protected routes use this layout

### Expected Outcome

The app has a unified layout structure ready to wrap all internal features.
"
gh issue create --repo igor-ponso/secret-santa --title "Implement Avatar Upload and Profile Edit" --body "## Issue Description

Allow users to upload a profile picture and update their basic info (name, email, password).

### Fixes & Improvements
- Add avatar upload field to profile form
- Enable update of name/email/password
- Persist avatar securely and validate format

### Affected Locations
- Profile.vue
- UserController
- storage/app/public/avatars

### Acceptance Criteria
- [ ] Users can upload and view their avatar
- [ ] Name/email/password updates are validated
- [ ] File upload is secure and tested

### Expected Outcome

Users can manage their own profile and avatar, improving personalization.
"
gh issue create --repo igor-ponso/secret-santa --title "Create Group Form" --body "## Issue Description

Users should be able to create a Secret Santa group with essential information.

### Fixes & Improvements
- Create form with name, description, draw_date, min_price, max_price
- Persist group linked to authenticated user

### Affected Locations
- GroupForm.vue
- GroupController
- groups table

### Acceptance Criteria
- [ ] Form validates all fields
- [ ] Group is saved to DB and user redirected
- [ ] Success toast or feedback is shown

### Expected Outcome

Users can create new Secret Santa groups through a simple interface.
"
gh issue create --repo igor-ponso/secret-santa --title "Allow Group Joining via Invite Link" --body "## Issue Description

Users should be able to join a group via an invitation link containing a unique token.

### Fixes & Improvements
- Create route like /join/{token} to receive invitations
- Fetch group by token and add user to it
- Handle already-joined or invalid token cases

### Affected Locations
- JoinGroupPage.vue
- GroupController
- routes/web.php

### Acceptance Criteria
- [ ] Joining works with valid token
- [ ] User sees appropriate messages for invalid/duplicate cases
- [ ] User appears in participant list

### Expected Outcome

Invited users can easily join Secret Santa groups with a simple shared link.
"
gh issue create --repo igor-ponso/secret-santa --title "Implement Draw Logic and Restrictions" --body "## Issue Description

Groups must support restrictions and a draw algorithm that respects them.

### Fixes & Improvements
- Implement 'X cannot draw Y' restrictions
- Create DrawService to calculate valid draws
- Persist draw results securely

### Affected Locations
- DrawService.php
- GroupController
- participants table

### Acceptance Criteria
- [ ] Draw respects all restrictions
- [ ] Result is private and persisted
- [ ] Draw can be re-run only by group owner

### Expected Outcome

Group creators can draw names with respect to custom rules and privacy.
"
#!/usr/bin/env bash

# Helper script: shows how to start all local dev servers for PodInBio Studio.
# This script is **informational only** – it just prints the commands you should run.

cat <<'EOF'
To start all dev servers for PodInBio Studio on your machine:

1) PHP backend (from project root)

   cd /Users/philybarrolaza/.cursor/podinbio
   php -S localhost:8080 router.php

   Leave this running in a terminal window or tab.

2) Admin UI / Vite dev server (from admin-ui)

   cd /Users/philybarrolaza/.cursor/podinbio/admin-ui
   npm install        # run once, or when deps change
   npm run dev

   This should start Vite on http://localhost:5174

3) Open the Studio in your browser

   - Admin preview is usually loaded via the PHP route (e.g., /admin/react-admin.php)
   - That page will connect to the Vite dev server at http://localhost:5174

If the preview page is stuck on "Loading the new admin experience…":
   - Make sure the Vite server (npm run dev) is running with no errors.
   - Reload the browser tab after Vite shows "Local:  http://localhost:5174/".
EOF



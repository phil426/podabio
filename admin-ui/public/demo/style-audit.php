<?php
// This file exists to allow the PHP server to route to this URL.
// In a production build, this would be handled by the SPA routing.
// For dev/playground purposes, we can include the main index or redirect.
// Actually, for the Vite dev setup, if we access this via 8080, it needs to load the JS.
// A simple way is to mimic index.html but point assets to the dev server.
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Style Audit (PHP Port 8080)</title>
    <!-- Point to Vite Dev Server for HMR and main entry -->
    <script type="module" src="http://localhost:5174/@vite/client"></script>
    <script type="module" src="http://localhost:5174/src/main.tsx"></script>
  </head>
  <body>
    <div id="root"></div>
  </body>
</html>

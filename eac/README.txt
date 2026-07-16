EAC Web App (cPanel-ready)

Deployment steps (cPanel)
1. Upload the 'eac' folder to public_html (e.g. public_html/eac/).
2. Import the SQL file (db.sql) into your MySQL via phpMyAdmin. Default DB name suggested: eac_db
3. Update config.php with your database credentials (installer can already do this).
4. Open index.php in browser.

Notes:
- PHP: tested on PHP 7.4+ (MySQLi). No external composer packages required.
- Chart.js and Bootstrap loaded from CDN.
- Theme: Dark Elegant (A) - CSS in assets/css/style.css
- CSV export and chart PNG download are included.
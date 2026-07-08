# AIESEC Opportunity Matcher

Demo-first PHP MVC app that accepts a CV PDF, extracts profile data with Python, loads AIESEC opportunities from CSV, and ranks the best matches.

## Local setup

1. Place the project in `c:\xampp\htdocs\Aiesec`.
2. Create a MySQL database by importing `database/schema.sql`.
3. Copy `.env.example` to `.env` and set environment values locally if needed.
4. Set environment variables for database access if needed:
   - `DB_HOST`
   - `DB_PORT`
   - `DB_DATABASE`
   - `DB_USERNAME`
   - `DB_PASSWORD`
   - `GROQ_API_KEY`
   - `GROQ_MODEL`
5. Install Python dependencies:
   - `pip install pymupdf requests beautifulsoup4`
   - Optional: `pip install spacy`
6. Ensure the `uploads/` directory is writable by PHP.
7. Start Apache and MySQL in XAMPP.
8. Open `http://localhost/Aiesec/` to sign up or log in first.

## Authentication

- The project now includes `signup`, `login`, and `logout` pages.
- New accounts are stored in the `users` table with a hashed password.
- The login page detects whether an account is `member` or `admin` automatically from the stored record.
- Signed-in users are required to access upload, results, and dashboard pages.

## Admin Backoffice

- Open `http://localhost/Aiesec/admin`.
- Create an admin account (Sign Up) using the admin code (`ADMIN_CODE` in `.env`).
- Sign in with admin email and password only; the app now detects the role automatically.
- Upload the opportunities CSV from the admin dashboard.
- Matching always uses the latest admin CSV at `uploads/csv/opportunities_latest.csv`.

## EP Management

- Open `http://localhost/Aiesec/ep-management` as an admin.
- Register EPs, upload CV and passport files, and store extra documents in a dedicated folder per EP.
- Track the EP pipeline through `Applied`, `Accepted`, `Payment`, `Confirmed`, `Preparation Survey`, `Midway Survey`, `Experience Survey`, and `Completed`.
- Download all documents for a participant as a ZIP archive from the EP detail panel.
- Charts, filters, notifications, and live progress updates are available on the dashboard.

## Notes

- The Groq key is read from environment variables at runtime.
- The app loads opportunities from a CSV file, using `OPPORTUNITIES_CSV_PATH` when set.
- If an admin-uploaded CSV exists, it takes priority over other CSV paths.
- If the CSV file is missing, the app falls back to simulated opportunities.
- The current implementation uses a demo user id of `1`; full authentication can be added later.
- The app is configured for an XAMPP subfolder install, so links and redirects resolve under `/Aiesec` automatically.

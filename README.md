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
8. Open `http://localhost/Aiesec/upload`.

## Notes

- The Groq key is read from environment variables at runtime.
- The app loads opportunities from a CSV file, using `OPPORTUNITIES_CSV_PATH` when set.
- If the CSV file is missing, the app falls back to simulated opportunities.
- The current implementation uses a demo user id of `1`; full authentication can be added later.
- The app is configured for an XAMPP subfolder install, so links and redirects resolve under `/Aiesec` automatically.

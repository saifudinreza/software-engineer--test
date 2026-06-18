# Software Engineer Test — Solution

Full-stack app that consumes the JSON feed ([`submission.json`](submission.json)),
stores it in a database, exposes it via an API, and renders it as a submittable form.

- **Backend:** Laravel 13 (PHP 8.4) in [`backend/`](backend/) — API only
- **Database:** SQLite (zero-config, lightweight)
- **Frontend:** Standalone Vite (vanilla JS) project in [`frontend/`](frontend/) that calls the API cross-origin (CORS enabled on the backend)

## How it maps to the brief

| Requirement | Where |
|---|---|
| Laravel backend that consumes the feed, publishes to DB, exposes APIs | `backend/app/Services/FormImporter.php`, `backend/app/Console/Commands/ImportForm.php`, `backend/routes/api.php` |
| DB stores the data well-formed, with indexes | `backend/database/migrations/2026_01_01_000001_create_form_tables.php` (normalised sections → fields → options; indexed FKs, type, position) |
| Frontend calls API, shows form, submits to API | separate project: `frontend/src/main.js`, `frontend/src/api.js` |
| Memory usage considered | Feed is parsed with a **streaming** JSON parser (`halaxa/json-machine`), so the whole file is never held in memory at once; rows inserted in bulk per section |
| Unit tests (nice to have) | `backend/tests/Feature/FormApiTest.php` (9 tests) |
| Migrations (nice to have) | `backend/database/migrations/` |

## Data model

The feed is normalised rather than dumped as a blob, so it is queryable and indexable:

```
form_sections (id, name, position)
  └─ form_fields (id, section_id*, label, type, sub_type, description, orm_only, position)
       └─ field_options (id, field_id*, label, value, position)

submissions (id)
  └─ submission_values (id, submission_id*, field_id*, value[json])
```
`*` = indexed foreign key. Feed string ids are kept as natural primary keys.

## API

| Method | Path | Description |
|---|---|---|
| GET | `/api/form` | Full form definition (sections → fields → options) |
| POST | `/api/submissions` | Validate answers against the definition and store. Body: `{ "answers": { "<field_id>": <value> } }` |
| GET | `/api/submissions/{id}` | Read a stored submission |

Answer encoding: `radio_button` → option id (string), `checkbox` → array of option
ids, `text`/`long_text` → string (`date` sub_type is validated as a date).

## Run it

### Backend (terminal 1)

```bash
cd backend
composer install
cp .env.example .env          # Windows PowerShell: Copy-Item .env.example .env
php artisan key:generate

# Create the (gitignored) SQLite database file, then build schema + import the feed:
#   bash / macOS / Linux:
touch database/database.sqlite
#   Windows PowerShell:
#   New-Item -ItemType File database/database.sqlite -Force

php artisan migrate:fresh --seed     # creates schema + imports submission.json
# (the feed can also be (re)imported explicitly: php artisan forms:import)

php artisan serve                    # API at http://127.0.0.1:8000
```

### Frontend (terminal 2)

```bash
cd frontend
npm install
cp .env.example .env          # optional; default API URL already points to :8000
npm run dev                   # http://localhost:5173
```

Open http://localhost:5173 — the form renders from `GET /api/form`; submitting posts
to `POST /api/submissions` (cross-origin, CORS enabled) and shows the new submission id.
The API base URL is configurable via `frontend/.env` (`VITE_API_BASE_URL`).

## Tests

```bash
cd backend
php artisan test
```

---

## Original task brief

> Develop a combined backend and frontend application that consumes JSON feed and
> displays a (at minimum, barebones) website with form provided in JSON.
>
> **We expect to see:**
> 1. A Laravel backend application in PHP that consumes the JSON feed, publishes it to a database, and exposes the database via APIs.
> 2. A database that stores the data in JSON in a well-formed way, with appropriate indexes.
> 3. Frontend that calls the backend API, shows the Form and submit the form to API.
> 4. The design of the frontend is not important and is not judged in this test.
> 5. Memory usage should be a consideration.
>
> **Nice to have:** Unit tests, Migrations.

# Frontend — Form Submission

Standalone Vite (vanilla JS) app that renders the form from the Laravel API and
submits answers back to it. No framework, no build dependencies beyond Vite.

## Setup

```bash
cd frontend
npm install
cp .env.example .env   # adjust VITE_API_BASE_URL if your backend runs elsewhere
npm run dev            # http://localhost:5173
```

Make sure the backend is running (`cd ../backend && php artisan serve`).
The API base URL is configured via `VITE_API_BASE_URL` (default
`http://127.0.0.1:8000/api`). CORS for localhost is already enabled on the backend.

## Build

```bash
npm run build     # outputs static assets to dist/
npm run preview   # serve the production build locally
```

## Structure

| File | Purpose |
|---|---|
| `src/api.js` | API client (`getForm`, `submitAnswers`); reads `VITE_API_BASE_URL` |
| `src/main.js` | Renders fields per type and handles submit |
| `index.html` / `src/style.css` | Page shell and minimal styling |

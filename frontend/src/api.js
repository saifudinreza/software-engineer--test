// Base URL of the Laravel API. Override with VITE_API_BASE_URL in a .env file.
const API_BASE = import.meta.env.VITE_API_BASE_URL ?? 'http://127.0.0.1:8000/api';

export async function getForm() {
    const res = await fetch(`${API_BASE}/form`);
    if (!res.ok) throw new Error(`Failed to load form (HTTP ${res.status})`);
    return res.json();
}

export async function submitAnswers(answers) {
    const res = await fetch(`${API_BASE}/submissions`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ answers }),
    });
    const body = await res.json().catch(() => ({}));
    return { ok: res.ok, body };
}

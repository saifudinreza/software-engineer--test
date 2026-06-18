import { getForm, submitAnswers } from './api.js';

const statusEl = document.getElementById('status');

function showStatus(msg, kind) {
    statusEl.textContent = msg;
    statusEl.className = kind;
    statusEl.style.display = 'block';
}

function fieldControl(field) {
    const wrap = document.createElement('div');
    wrap.className = 'field';

    const label = document.createElement('label');
    label.className = 'main';
    label.textContent = field.label;
    wrap.appendChild(label);

    if (field.description) {
        const d = document.createElement('div');
        d.className = 'desc';
        d.textContent = field.description;
        wrap.appendChild(d);
    }

    if (field.type === 'radio_button' || field.type === 'checkbox') {
        const isCheckbox = field.type === 'checkbox';
        field.options.forEach(opt => {
            const o = document.createElement('label');
            o.className = 'option';
            const input = document.createElement('input');
            input.type = isCheckbox ? 'checkbox' : 'radio';
            input.name = isCheckbox ? `f_${field.id}[]` : `f_${field.id}`;
            input.value = opt.id;
            input.dataset.fieldId = field.id;
            o.appendChild(input);
            o.appendChild(document.createTextNode(' ' + opt.label));
            wrap.appendChild(o);
        });
    } else if (field.type === 'long_text') {
        const ta = document.createElement('textarea');
        ta.dataset.fieldId = field.id;
        wrap.appendChild(ta);
    } else { // text and fallback
        const input = document.createElement('input');
        input.dataset.fieldId = field.id;
        input.type = field.sub_type === 'date' ? 'date'
                   : field.sub_type === 'amount' ? 'number' : 'text';
        wrap.appendChild(input);
    }
    return wrap;
}

function collectAnswers() {
    const answers = {};
    document.querySelectorAll('[data-field-id]').forEach(el => {
        const id = el.dataset.fieldId;
        if (el.type === 'checkbox') {
            if (!answers[id]) answers[id] = [];
            if (el.checked) answers[id].push(el.value);
        } else if (el.type === 'radio') {
            if (el.checked) answers[id] = el.value;
        } else {
            answers[id] = el.value;
        }
    });
    return answers;
}

async function loadForm() {
    const data = await getForm();
    const container = document.getElementById('sections');
    container.innerHTML = '';

    if (!data.sections || data.sections.length === 0) {
        container.textContent = 'Belum ada definisi form. Jalankan di backend: php artisan forms:import';
        return;
    }

    data.sections.forEach(section => {
        const fs = document.createElement('fieldset');
        const legend = document.createElement('legend');
        legend.textContent = section.name;
        fs.appendChild(legend);
        section.fields.forEach(f => fs.appendChild(fieldControl(f)));
        container.appendChild(fs);
    });
}

document.getElementById('dynamic-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    statusEl.style.display = 'none';

    try {
        const { ok, body } = await submitAnswers(collectAnswers());
        if (ok) {
            showStatus(`Tersimpan! Submission id = ${body.id}`, 'ok');
        } else {
            const errors = body.errors ? JSON.stringify(body.errors, null, 2) : (body.message || 'Gagal');
            showStatus('Validasi gagal:\n' + errors, 'err');
        }
    } catch (err) {
        showStatus('Error: ' + err.message, 'err');
    } finally {
        btn.disabled = false;
    }
});

loadForm().catch(err => showStatus('Gagal memuat form: ' + err.message, 'err'));



import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Show or hide password: adds an eye button to every password box on every page
const eyeOpen = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>';
const eyeShut = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 19c-7 0-11-7-11-7a18.5 18.5 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 7 11 7a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

function addPasswordEyes() {
    document.querySelectorAll('input[type="password"]:not([data-eye])').forEach((input) => {
        input.dataset.eye = '1';

        // wrap the box so the eye can sit inside it on the right
        const wrap = document.createElement('div');
        wrap.className = 'pg-eye-wrap';
        ['mt-1', 'mt-2', 'mt-3'].forEach((c) => {
            if (input.classList.contains(c)) {
                input.classList.remove(c);
                wrap.classList.add(c);
            }
        });
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);
        input.style.paddingRight = '2.75rem';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'pg-eye';
        button.setAttribute('aria-label', 'Show password');
        button.innerHTML = eyeOpen;

        button.addEventListener('click', () => {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.innerHTML = show ? eyeShut : eyeOpen;
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            input.focus();
        });

        wrap.appendChild(button);
    });
}

document.addEventListener('DOMContentLoaded', addPasswordEyes);

// Live password checklist: ticks each rule as the user types (only where a new password is set)
function setRule(item, ok) {
    item.classList.toggle('ok', ok);
    item.querySelector('.pg-pw-mark').textContent = ok ? '✓' : '○';
}

function addPasswordChecklist() {
    document.querySelectorAll('input[name="password"]').forEach((input) => {
        const form = input.form;
        const confirm = form ? form.querySelector('input[name="password_confirmation"]') : null;
        if (!confirm || input.dataset.checklist) {
            return;                                  // login and delete account have no confirm box, so skip them
        }
        input.dataset.checklist = '1';

        const rules = [
            ['At least 8 characters', (v) => v.length >= 8],
            ['A capital letter', (v) => /[A-Z]/.test(v)],
            ['A small letter', (v) => /[a-z]/.test(v)],
            ['A number', (v) => /[0-9]/.test(v)],
            ['A symbol, like @ # ! or %', (v) => /[^A-Za-z0-9\s]/.test(v)],
        ];

        const list = document.createElement('ul');
        list.className = 'pg-pw-rules';

        const items = rules.map(([text]) => {
            const li = document.createElement('li');
            li.innerHTML = '<span class="pg-pw-mark">○</span>' + text;
            list.appendChild(li);
            return li;
        });

        const match = document.createElement('li');
        match.innerHTML = '<span class="pg-pw-mark">○</span>Both passwords match';
        list.appendChild(match);

        (input.closest('.pg-eye-wrap') || input).insertAdjacentElement('afterend', list);

        const update = () => {
            const value = input.value;
            rules.forEach(([, test], i) => setRule(items[i], test(value)));
            setRule(match, value.length > 0 && value === confirm.value);
        };

        input.addEventListener('input', update);
        confirm.addEventListener('input', update);
        update();
    });
}

document.addEventListener('DOMContentLoaded', addPasswordChecklist);
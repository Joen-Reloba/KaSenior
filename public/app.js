document.querySelectorAll('[data-print]').forEach(button => {
    button.addEventListener('click', event => { event.preventDefault(); window.print(); });
});
const confirmation = document.getElementById('site-confirmation');
if (confirmation) {
    let pendingForm;
    let confirmedForm;
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!form.matches('form[data-confirm]') || confirmedForm === form) {
            return;
        }

        event.preventDefault();
        pendingForm = form;
        const signIn = form.elements.namedItem('username');
        const signOut = form.hasAttribute('data-confirm-account');
        const username = signIn?.value.trim() ?? form.dataset.confirmAccount;
        const accountAction = Boolean(signIn || signOut);

        confirmation.classList.toggle('signin-confirmation', accountAction);
        confirmation.querySelectorAll('[data-signin-detail]').forEach(detail => {
            detail.hidden = !accountAction;
        });
        confirmation.querySelector('.confirmation-avatar').textContent = Array.from(username ?? '')
            .slice(0, 2).join('').toUpperCase();
        confirmation.querySelector('.confirmation-role').textContent = form.dataset.confirmRole ?? 'Association Admin';
        confirmation.querySelector('.confirmation-message').textContent = signOut
            ? 'You’re about to sign out of KaSenior.'
            : 'You’re about to sign in to KaSenior. Please make sure this is your account.';
        confirmation.querySelector('.confirmation-hint').textContent = signOut
            ? 'You can sign in again to return to your account.'
            : 'Not you? Close this and use a different account.';
        confirmation.querySelector('[value="cancel"]').textContent = accountAction ? 'No, Go Back' : 'Cancel';
        confirmation.querySelector('[value="confirm"]').textContent = signOut ? 'Yes, Sign Out' : signIn ? 'Yes, Sign In' : 'Confirm';
        confirmation.querySelector('h2').textContent = form.dataset.confirm.replace('{username}', () => username ?? '');

        confirmation.returnValue = 'cancel';
        confirmation.showModal();
    });

    confirmation.addEventListener('close', () => {
        if (confirmation.returnValue === 'confirm' && pendingForm) {
            confirmedForm = pendingForm;
            try {
                pendingForm.requestSubmit();
            } finally {
                confirmedForm = null;
            }
        }
        pendingForm = null;
    });
}
const birthDate = document.getElementById('birth-date');
const agePreview = document.getElementById('age-preview');
function updateAge() {
    if (!birthDate.value) {
        agePreview.textContent = 'Enter a birthdate to see the current age.';
        return;
    }
    const [year, month, day] = birthDate.value.split('-').map(Number);
    const today = new Date();
    let age = today.getFullYear() - year;
    if (today.getMonth() + 1 < month || (today.getMonth() + 1 === month && today.getDate() < day)) {
        age--;
    }
    agePreview.textContent = age >= 0 ? 'Current age: ' + age + ' years old.' : 'Enter a valid birthdate.';
}
if (birthDate && agePreview) {
    birthDate.addEventListener('input', updateAge);
    updateAge();
}

const searchForm = document.querySelector('[data-live-search]');
const searchResults = document.querySelector('[data-search-results]');
const searchStatus = document.querySelector('[data-search-status]');

if (searchForm && searchResults && searchStatus && !new URLSearchParams(location.search).has('print')) {
    const searchInput = searchForm.elements.namedItem('q');
    let searchTimer;
    let pendingSearch;

    searchInput.addEventListener('input', event => {
        clearTimeout(searchTimer);
        pendingSearch?.abort();
        searchResults.removeAttribute('aria-busy');

        if (event.isComposing) {
            return;
        }

        searchTimer = setTimeout(async () => {
            const request = new AbortController();
            pendingSearch = request;
            const url = new URL(searchForm.action);
            url.search = new URLSearchParams(new FormData(searchForm)).toString();
            searchResults.setAttribute('aria-busy', 'true');
            searchStatus.classList.remove('sr-only');
            searchStatus.textContent = 'Searching…';

            try {
                const response = await fetch(url, { signal: request.signal });
                if (!response.ok) {
                    throw new Error('Search request failed');
                }
                const html = await response.text();
                if (request.signal.aborted) {
                    return;
                }
                const page = new DOMParser().parseFromString(html, 'text/html');
                const results = page.querySelector('[data-search-results]');
                if (!results) {
                    throw new Error('Search results unavailable');
                }

                searchResults.replaceChildren(...results.childNodes);
                const printLink = document.querySelector('[data-search-print-link]');
                if (printLink) {
                    printLink.href = page.querySelector('[data-search-print-link]').href;
                }
                history.replaceState(history.state, '', url);
                searchStatus.classList.add('sr-only');
                searchStatus.textContent = searchResults.querySelector('.empty')?.textContent
                    ?? searchResults.querySelector('.pagination p')?.textContent
                    ?? 'Search results updated.';
            } catch (error) {
                if (!request.signal.aborted) {
                    searchStatus.classList.remove('sr-only');
                    searchStatus.textContent = 'Live search could not load. Use the Search button to try again.';
                }
            } finally {
                if (!request.signal.aborted) {
                    searchResults.removeAttribute('aria-busy');
                }
            }
        }, 300);
        searchStatus.classList.add('sr-only');
        searchStatus.textContent = '';
    });
}

const pinForm = document.querySelector('[data-pin-form]');
if (pinForm) {
    const pinInput = pinForm.elements.namedItem('password');
    const pinDialog = pinForm.closest('dialog');
    const pinShortcut = document.querySelector('.superadmin-shortcut');
    pinShortcut.addEventListener('click', event => {
        event.preventDefault();
        pinDialog.showModal();
    });
    pinForm.querySelector('[data-pin-close]').addEventListener('click', () => pinDialog.close());
    pinDialog.addEventListener('close', () => {
        pinForm.reset();
        pinShortcut.focus();
    });
    if (pinDialog.hasAttribute('data-pin-auto-open')) {
        pinDialog.showModal();
    }
    const pinPad = pinForm.querySelector('.pin-pad');
    pinPad.hidden = false;
    pinPad.addEventListener('click', event => {
        const key = event.target.closest('[data-pin-key]')?.dataset.pinKey;
        if (key === 'Clear') {
            pinInput.value = '';
        } else if (key === 'Delete') {
            pinInput.value = pinInput.value.slice(0, -1);
        } else if (key && pinInput.value.length < pinInput.maxLength) {
            pinInput.value += key;
        }
    });
}

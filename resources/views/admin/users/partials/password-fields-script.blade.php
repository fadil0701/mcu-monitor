<script>
(function () {
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('password_confirmation');
    const checklist = document.getElementById('password-policy-checklist');
    const matchFeedback = document.getElementById('password-match-feedback');
    const form = passwordInput?.closest('form');

    if (!passwordInput || !confirmInput || !checklist) {
        return;
    }

    const rules = {
        length: (value) => value.length >= 8,
        upper: (value) => /[A-Z]/.test(value),
        lower: (value) => /[a-z]/.test(value),
        number: (value) => /[0-9]/.test(value),
        symbol: (value) => /[^A-Za-z0-9]/.test(value),
    };

    function setRuleState(rule, passed) {
        const item = checklist.querySelector('[data-rule="' + rule + '"]');
        if (!item) {
            return;
        }

        const icon = item.querySelector('i');
        if (!icon) {
            return;
        }

        icon.className = passed ? 'bx bx-check text-success me-1' : 'bx bx-x text-danger me-1';
        item.classList.toggle('text-success', passed);
        item.classList.toggle('text-muted', !passed);
    }

    function passwordIsValid(value) {
        return Object.keys(rules).every(function (rule) {
            return rules[rule](value);
        });
    }

    function refreshPolicy() {
        const value = passwordInput.value;
        const hasValue = value !== '';

        Object.keys(rules).forEach(function (rule) {
            setRuleState(rule, hasValue && rules[rule](value));
        });

        refreshMatch();
    }

    function refreshMatch() {
        if (!matchFeedback) {
            return;
        }

        const password = passwordInput.value;
        const confirm = confirmInput.value;

        matchFeedback.textContent = '';
        matchFeedback.className = 'form-text mt-2';

        if (confirm === '' && password === '') {
            return;
        }

        if (confirm === '') {
            return;
        }

        if (password === confirm) {
            matchFeedback.textContent = 'Password dan konfirmasi cocok.';
            matchFeedback.classList.add('text-success');
            return;
        }

        matchFeedback.textContent = 'Password dan konfirmasi tidak sama.';
        matchFeedback.classList.add('text-danger');
    }

    document.querySelectorAll('.password-toggle-btn').forEach(function (toggle) {
        const input = toggle.parentElement ? toggle.parentElement.querySelector('input') : null;
        const icon = toggle.querySelector('i');
        if (!input || !icon) {
            return;
        }

        const handler = function () {
            const isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');
            icon.classList.toggle('bx-hide', !isPassword);
            icon.classList.toggle('bx-show', isPassword);
        };

        toggle.addEventListener('click', handler);
        toggle.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                handler();
            }
        });
    });

    passwordInput.addEventListener('input', refreshPolicy);
    confirmInput.addEventListener('input', refreshMatch);

    if (form) {
        form.addEventListener('submit', function (event) {
            const password = passwordInput.value;
            const confirm = confirmInput.value;

            if (password === '' && confirm === '') {
                return;
            }

            if (!passwordIsValid(password) || password !== confirm) {
                event.preventDefault();
                refreshPolicy();
                refreshMatch();

                if (!passwordIsValid(password)) {
                    passwordInput.focus();
                } else {
                    confirmInput.focus();
                }
            }
        });
    }

    refreshPolicy();
})();
</script>

@props(['captcha' => null])

@if(is_array($captcha))
    <div
        class="mb-3"
        data-math-captcha
        data-refresh-url="{{ route('login.captcha.refresh') }}"
    >
        <input
            type="hidden"
            name="captcha_token"
            value="{{ $captcha['captcha_token'] }}"
            data-captcha-token
        >
        <div class="d-flex align-items-center gap-2 mb-2">
            <img
                src="{{ $captcha['captcha_image_url'] ?? '' }}"
                alt="Captcha penjumlahan"
                width="280"
                height="80"
                class="rounded border bg-light"
                data-captcha-image
            >
            <button
                type="button"
                class="btn btn-outline-secondary d-flex align-items-center justify-content-center"
                style="width: 44px; height: 44px;"
                data-captcha-refresh
                aria-label="Perbarui captcha"
                title="Perbarui captcha"
            >
                <i class="bx bx-refresh fs-4"></i>
            </button>
        </div>
        <input
            type="text"
            id="captcha_answer"
            name="captcha_answer"
            value="{{ old('captcha_answer') }}"
            placeholder="Masukkan hasil penjumlahan di atas"
            inputmode="numeric"
            pattern="[0-9]*"
            autocomplete="off"
            required
            class="form-control @error('captcha_answer') is-invalid @enderror"
        >
        @error('captcha_answer')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <script>
        (() => {
            const root = document.currentScript?.previousElementSibling;
            if (!root || !root.matches('[data-math-captcha]')) {
                return;
            }

            const refreshUrl = root.dataset.refreshUrl;
            const tokenInput = root.querySelector('[data-captcha-token]');
            const image = root.querySelector('[data-captcha-image]');
            const answerInput = root.querySelector('#captcha_answer');
            const refreshButton = root.querySelector('[data-captcha-refresh]');

            if (!refreshUrl || !tokenInput || !image || !refreshButton) {
                return;
            }

            const loadCaptcha = async () => {
                refreshButton.disabled = true;
                try {
                    const response = await fetch(refreshUrl, {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) {
                        return;
                    }
                    const data = await response.json();
                    if (!data?.captcha_token || !data?.captcha_image_url) {
                        return;
                    }
                    tokenInput.value = data.captcha_token;
                    image.src = `${data.captcha_image_url}?t=${Date.now()}`;
                    if (answerInput) {
                        answerInput.value = '';
                    }
                } finally {
                    refreshButton.disabled = false;
                }
            };

            refreshButton.addEventListener('click', loadCaptcha);
        })();
    </script>
@endif

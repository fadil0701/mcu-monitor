@php
    $passwordRequired = $passwordRequired ?? false;
    $passwordOptionalHint = $passwordOptionalHint ?? null;
@endphp

<div class="col-md-6">
    <div class="form-password-toggle">
        <label for="password" class="form-label">
            Password{{ $passwordRequired ? ' *' : '' }}
            @if($passwordOptionalHint)
                <span class="text-muted fw-normal">({{ $passwordOptionalHint }})</span>
            @endif
        </label>
        <div class="input-group input-group-merge">
            <input
                type="password"
                id="password"
                name="password"
                class="form-control @error('password') is-invalid @enderror"
                autocomplete="new-password"
                @if($passwordRequired) required @endif
            >
            <span class="input-group-text cursor-pointer password-toggle-btn" tabindex="0" role="button" aria-label="Tampilkan atau sembunyikan password">
                <i class="bx bx-hide"></i>
            </span>
        </div>
        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <ul class="list-unstyled small text-muted mt-2 mb-0 password-policy-checklist" id="password-policy-checklist" aria-live="polite">
            <li data-rule="length"><i class="bx bx-x text-danger me-1"></i>Minimal 8 karakter</li>
            <li data-rule="upper"><i class="bx bx-x text-danger me-1"></i>Huruf kapital (A–Z)</li>
            <li data-rule="lower"><i class="bx bx-x text-danger me-1"></i>Huruf kecil (a–z)</li>
            <li data-rule="number"><i class="bx bx-x text-danger me-1"></i>Angka (0–9)</li>
            <li data-rule="symbol"><i class="bx bx-x text-danger me-1"></i>Symbol (contoh: !, @@, #, $)</li>
        </ul>
    </div>
</div>

<div class="col-md-6">
    <div class="form-password-toggle">
        <label for="password_confirmation" class="form-label">
            Konfirmasi Password{{ $passwordRequired ? ' *' : '' }}
        </label>
        <div class="input-group input-group-merge">
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                class="form-control @error('password_confirmation') is-invalid @enderror"
                autocomplete="new-password"
                @if($passwordRequired) required @endif
            >
            <span class="input-group-text cursor-pointer password-toggle-btn" tabindex="0" role="button" aria-label="Tampilkan atau sembunyikan konfirmasi password">
                <i class="bx bx-hide"></i>
            </span>
        </div>
        @error('password_confirmation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <div id="password-match-feedback" class="form-text mt-2" aria-live="polite"></div>
    </div>
</div>

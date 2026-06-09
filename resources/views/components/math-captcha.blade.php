@props(['captcha' => null])

@if(is_array($captcha))
    <input type="hidden" name="captcha_token" value="{{ $captcha['captcha_token'] }}">
    <div class="mb-3">
        <label for="captcha_answer" class="form-label">Verifikasi: {{ $captcha['captcha_question'] }}</label>
        <input
            type="text"
            id="captcha_answer"
            name="captcha_answer"
            value="{{ old('captcha_answer') }}"
            inputmode="numeric"
            pattern="[0-9]*"
            autocomplete="off"
            required
            class="form-control @error('captcha_answer') is-invalid @enderror"
        >
        @error('captcha_answer')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
@endif

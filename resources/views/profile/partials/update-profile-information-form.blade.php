<section>
    <p class="text-muted mb-4">Perbarui informasi profile dan alamat email akun Anda.</p>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="mb-3">
            <label for="name" class="form-label">Nama</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" />
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username" />
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="text-muted mb-1">Email belum diverifikasi.</p>
                    @if (session('status') === 'verification-link-sent')
                        <p class="text-success mb-1">Link verifikasi baru telah dikirim.</p>
                    @endif
                </div>
            @endif
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
        @if (session('status') === 'profile-updated')
            <span class="text-success ms-2">Tersimpan.</span>
        @endif
    </form>

    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
        <form method="post" action="{{ route('verification.send') }}" class="mt-2">
            @csrf
            <button type="submit" class="btn btn-link p-0">Kirim ulang email verifikasi</button>
        </form>
    @endif
</section>

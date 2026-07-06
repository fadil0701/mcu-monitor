            <div class="col-md-6">
                <label for="role" class="form-label">Role</label>
                @if($canAssignRoles)
                    <select id="role" name="role" required class="form-select @error('role') is-invalid @enderror">
                        @php
                            $currentRole = old('role', $user->role === 'user' ? 'peserta' : $user->role);
                        @endphp
                        <option value="peserta" {{ $currentRole === 'peserta' ? 'selected' : '' }}>Peserta</option>
                        <option value="pimpinan" {{ $currentRole === 'pimpinan' ? 'selected' : '' }}>Pimpinan</option>
                        <option value="admin" {{ $currentRole === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="super_admin" {{ $currentRole === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    </select>
                @else
                    <input type="text" class="form-control" value="{{ $user->role_label }}" disabled>
                    <div class="form-text">Admin tidak dapat mengubah role pengguna.</div>
                @endif
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

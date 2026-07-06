            <div class="col-md-6">
                <label for="role" class="form-label">Role *</label>
                @if($canAssignRoles)
                    <select id="role" name="role" required class="form-select @error('role') is-invalid @enderror">
                        <option value="peserta" {{ old('role') === 'peserta' ? 'selected' : '' }}>Peserta</option>
                        <option value="pimpinan" {{ old('role') === 'pimpinan' ? 'selected' : '' }}>Pimpinan</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    </select>
                @else
                    <input type="hidden" name="role" value="peserta">
                    <input type="text" class="form-control" value="Peserta" disabled>
                    <div class="form-text">Admin hanya dapat menambahkan akun peserta.</div>
                @endif
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

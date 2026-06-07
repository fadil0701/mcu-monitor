@extends('layouts.sneat.app')

@section('title', 'Tambah Email Template')

@section('pageTitle', 'Tambah Email Template')

@section('content')

<x-common.component-card title="Form Email Template">
    <form method="POST" action="{{ route('admin.email-templates.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Nama <span class="text-danger">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required class="form-control @error('name') is-invalid @enderror">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="type" class="form-label">Tipe <span class="text-danger">*</span></label>
                <select id="type" name="type" required class="form-select @error('type') is-invalid @enderror">
                    <option value="mcu_invitation" {{ old('type') === 'mcu_invitation' ? 'selected' : '' }}>MCU Invitation</option>
                    <option value="reminder" {{ old('type') === 'reminder' ? 'selected' : '' }}>Reminder</option>
                    <option value="notification" {{ old('type') === 'notification' ? 'selected' : '' }}>Notification</option>
                    <option value="mcu_result" {{ old('type') === 'mcu_result' ? 'selected' : '' }}>Hasil MCU</option>
                    <option value="custom" {{ old('type') === 'custom' ? 'selected' : '' }}>Custom</option>
                </select>
                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required class="form-control @error('subject') is-invalid @enderror">
                @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label">Variable</label>
                <p class="form-text mb-2">Klik variabel untuk menyisipkan ke Body HTML (sesuai tipe template di atas).</p>
                <script type="application/json" id="variables-by-type">@json($availableVariablesByType ?? [])</script>
                <div id="variable-tags" class="d-flex flex-wrap gap-2 border rounded p-3"></div>
            </div>
            <div class="col-12">
                <label for="body_html" class="form-label">Body HTML</label>
                <textarea name="body_html" id="body_html" rows="12" class="form-control @error('body_html') is-invalid @enderror">{{ old('body_html', '') }}</textarea>
                @error('body_html')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label for="body_text" class="form-label">Body Plain Text</label>
                <textarea id="body_text" name="body_text" rows="4" class="form-control @error('body_text') is-invalid @enderror">{{ old('body_text') }}</textarea>
                @error('body_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label for="description" class="form-label">Deskripsi</label>
                <textarea id="description" name="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <div class="form-check form-check-inline">
                    <input type="checkbox" class="form-check-input" name="is_active" value="1" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Aktif</label>
                </div>
                <div class="form-check form-check-inline">
                    <input type="checkbox" class="form-check-input" name="is_default" value="1" id="is_default" {{ old('is_default') ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_default">Default</label>
                </div>
            </div>
        </div>
        <x-admin.form-actions :cancelUrl="route('admin.email-templates.index')" />
    </form>
</x-common.component-card>
@endsection

@push('scripts')
    <script src="https://unpkg.com/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var variablesByType = {};
        try {
            var el = document.getElementById('variables-by-type');
            if (el && el.textContent) variablesByType = JSON.parse(el.textContent);
        } catch (e) {}
        var typeSelect = document.querySelector('select[name="type"]');
        var tagsContainer = document.getElementById('variable-tags');

        function renderVariableTags() {
            var type = typeSelect ? typeSelect.value : 'mcu_invitation';
            var vars = variablesByType[type] || {};
            tagsContainer.innerHTML = '';
            Object.keys(vars).forEach(function(key) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm btn-outline-secondary font-monospace';
                btn.textContent = '{' + key + '}';
                btn.dataset.var = key;
                btn.onclick = function() {
                    var placeholder = '{' + key + '}';
                    if (typeof tinymce !== 'undefined' && tinymce.get('body_html')) {
                        tinymce.get('body_html').insertContent(placeholder);
                    } else {
                        var ta = document.getElementById('body_html');
                        if (ta) {
                            var start = ta.selectionStart, end = ta.selectionEnd;
                            ta.value = ta.value.slice(0, start) + placeholder + ta.value.slice(end);
                            ta.selectionStart = ta.selectionEnd = start + placeholder.length;
                        }
                    }
                };
                tagsContainer.appendChild(btn);
            });
        }
        if (typeSelect) typeSelect.addEventListener('change', renderVariableTags);
        renderVariableTags();

        tinymce.init({
            selector: '#body_html',
            height: 320,
            menubar: false,
            plugins: 'lists link table code charmap',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link table charmap | code | removeformat',
            block_formats: 'Paragraf=p; Heading 2=h2; Heading 3=h3',
            content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
            branding: false,
            promotion: false,
            resize: true
        });
    });
    </script>
@endpush

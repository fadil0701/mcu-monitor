@props([
    'name',
    'label' => null,
    'options' => [],
    'valueKey' => 'id',
    'labelKey' => 'nama_lengkap',
    'sublabelKey' => null,
    'placeholder' => 'Pilih...',
    'value' => '',
    'required' => false,
])

@php
    $uid = 'ss-' . preg_replace('/[^a-z0-9]/i', '-', $name) . '-' . uniqid();
    $selectedValue = old($name, $value);
    $selectedLabel = $placeholder;
    foreach ($options as $opt) {
        $optValue = is_array($opt) ? ($opt[$valueKey] ?? null) : ($opt->{$valueKey} ?? null);
        if ((string) $optValue === (string) $selectedValue) {
            $selectedLabel = is_array($opt) ? ($opt[$labelKey] ?? '') : ($opt->{$labelKey} ?? '');
            if ($sublabelKey) {
                $sub = is_array($opt) ? ($opt[$sublabelKey] ?? '') : ($opt->{$sublabelKey} ?? '');
                if ($sub) {
                    $selectedLabel .= ' (' . $sub . ')';
                }
            }
            break;
        }
    }
@endphp

<div class="mb-3 searchable-select" data-searchable-select>
    @if($label)
        <label for="{{ $uid }}-input" class="form-label">{{ $label }}</label>
    @endif

    <input type="hidden" name="{{ $name }}" id="{{ $uid }}-value" value="{{ $selectedValue }}" @if($required) required @endif>

    <div class="position-relative">
        <input
            type="text"
            id="{{ $uid }}-input"
            class="form-control @error($name) is-invalid @enderror"
            value="{{ $selectedValue ? $selectedLabel : '' }}"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            role="combobox"
            aria-expanded="false"
            aria-controls="{{ $uid }}-list"
        >
        <div
            id="{{ $uid }}-list"
            class="searchable-select-dropdown list-group position-absolute w-100 shadow-sm d-none"
            style="z-index: 1050; max-height: 240px; overflow-y: auto;"
        >
            @foreach($options as $opt)
                @php
                    $optValue = is_array($opt) ? ($opt[$valueKey] ?? '') : ($opt->{$valueKey} ?? '');
                    $optLabel = is_array($opt) ? ($opt[$labelKey] ?? '') : ($opt->{$labelKey} ?? '');
                    $optSub = $sublabelKey
                        ? (is_array($opt) ? ($opt[$sublabelKey] ?? '') : ($opt->{$sublabelKey} ?? ''))
                        : '';
                    $searchText = strtolower(trim($optLabel . ' ' . $optSub));
                @endphp
                <button
                    type="button"
                    class="list-group-item list-group-item-action searchable-select-option py-2"
                    data-value="{{ $optValue }}"
                    data-label="{{ $optLabel }}{{ $optSub ? ' (' . $optSub . ')' : '' }}"
                    data-search="{{ $searchText }}"
                >
                    <span class="d-block fw-medium">{{ $optLabel }}</span>
                    @if($optSub)
                        <small class="text-muted">{{ $optSub }}</small>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

@once
    @push('scripts')
    <script>
    (function() {
        if (window.__mcuSearchableSelectInit) return;
        window.__mcuSearchableSelectInit = true;

        function closeAll(except) {
            document.querySelectorAll('[data-searchable-select]').forEach(function(root) {
                if (except && root === except) return;
                var list = root.querySelector('.searchable-select-dropdown');
                var input = root.querySelector('input[type="text"]');
                if (list) list.classList.add('d-none');
                if (input) input.setAttribute('aria-expanded', 'false');
            });
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('[data-searchable-select]')) {
                closeAll();
            }
        });

        document.querySelectorAll('[data-searchable-select]').forEach(function(root) {
            var hidden = root.querySelector('input[type="hidden"]');
            var input = root.querySelector('input[type="text"]');
            var list = root.querySelector('.searchable-select-dropdown');
            var options = root.querySelectorAll('.searchable-select-option');

            function filterOptions(query) {
                var q = (query || '').toLowerCase().trim();
                options.forEach(function(opt) {
                    var match = !q || (opt.dataset.search || '').indexOf(q) !== -1;
                    opt.classList.toggle('d-none', !match);
                });
            }

            function selectOption(opt) {
                hidden.value = opt.dataset.value || '';
                input.value = opt.dataset.label || '';
                closeAll();
            }

            input.addEventListener('focus', function() {
                closeAll(root);
                filterOptions(input.value);
                list.classList.remove('d-none');
                input.setAttribute('aria-expanded', 'true');
            });

            input.addEventListener('input', function() {
                hidden.value = '';
                filterOptions(input.value);
                list.classList.remove('d-none');
                input.setAttribute('aria-expanded', 'true');
            });

            options.forEach(function(opt) {
                opt.addEventListener('click', function() {
                    selectOption(opt);
                });
            });
        });
    })();
    </script>
    @endpush
@endonce

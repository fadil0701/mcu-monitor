/**
 * Combobox pencarian instansi SKPD (tanpa library eksternal).
 */
(function () {
    function collectOptions(selectEl) {
        return Array.from(selectEl.options)
            .filter(function (o) { return o.value && !o.disabled; })
            .map(function (o) { return { value: o.value, text: o.textContent.trim() }; });
    }

    function ensureOption(selectEl, options, value) {
        if (!value || options.some(function (o) { return o.value === value; })) {
            return options;
        }
        var opt = document.createElement('option');
        opt.value = value;
        opt.textContent = value;
        selectEl.appendChild(opt);
        return options.concat([{ value: value, text: value }]);
    }

    function initInstansiSearchableSelect(selectEl) {
        if (!selectEl || selectEl.dataset.instansiSearchableInit === '1') {
            return;
        }
        selectEl.dataset.instansiSearchableInit = '1';

        var wrap = document.createElement('div');
        wrap.className = 'position-relative instansi-combobox';
        wrap.setAttribute('data-instansi-combobox-for', selectEl.id || '');

        var input = document.createElement('input');
        input.type = 'text';
        input.autocomplete = 'off';
        input.placeholder = 'Ketik untuk mencari instansi…';
        input.className = 'form-control' + (selectEl.classList.contains('is-invalid') ? ' is-invalid' : '');
        if (selectEl.id) {
            input.id = selectEl.id;
            selectEl.removeAttribute('id');
        }
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('role', 'combobox');

        var listId = (selectEl.id || 'instansi') + '_listbox';
        var list = document.createElement('ul');
        list.id = listId;
        list.setAttribute('role', 'listbox');
        list.className = 'list-unstyled position-absolute w-100 mt-1 mb-0 rounded border bg-white shadow-sm';
        list.style.maxHeight = '14rem';
        list.style.overflowY = 'auto';
        list.style.zIndex = '1050';
        list.hidden = true;
        input.setAttribute('aria-controls', listId);

        selectEl.classList.add('visually-hidden');
        selectEl.setAttribute('tabindex', '-1');
        selectEl.setAttribute('aria-hidden', 'true');

        var parent = selectEl.parentNode;
        if (parent) {
            parent.insertBefore(wrap, selectEl);
        }
        wrap.appendChild(selectEl);
        wrap.appendChild(input);
        wrap.appendChild(list);

        var options = collectOptions(selectEl);
        var activeIndex = -1;

        function syncInputFromSelect() {
            var opt = selectEl.selectedOptions[0];
            input.value = opt && opt.value ? opt.textContent.trim() : '';
        }

        function setExpanded(open) {
            input.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function filter(term) {
            var q = term.trim().toLowerCase();
            if (!q) {
                return options;
            }
            return options.filter(function (o) {
                return o.text.toLowerCase().indexOf(q) !== -1;
            });
        }

        function pick(option) {
            selectEl.value = option.value;
            input.value = option.text;
            list.hidden = true;
            activeIndex = -1;
            setExpanded(false);
            selectEl.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function renderList(items) {
            list.innerHTML = '';
            activeIndex = -1;
            if (!items.length) {
                var empty = document.createElement('li');
                empty.className = 'px-3 py-2 small text-muted';
                empty.textContent = 'Instansi tidak ditemukan';
                list.appendChild(empty);
            } else {
                items.slice(0, 80).forEach(function (o, idx) {
                    var li = document.createElement('li');
                    li.setAttribute('role', 'option');
                    li.dataset.index = String(idx);
                    li.className = 'px-3 py-2 small text-body';
                    li.style.cursor = 'pointer';
                    li.textContent = o.text;
                    li.addEventListener('mouseenter', function () {
                        li.classList.add('bg-light');
                    });
                    li.addEventListener('mouseleave', function () {
                        li.classList.remove('bg-light');
                    });
                    li.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        pick(o);
                    });
                    list.appendChild(li);
                });
            }
            list.hidden = false;
            setExpanded(true);
        }

        function highlightOptions() {
            Array.from(list.querySelectorAll('[role="option"]')).forEach(function (li, i) {
                li.classList.toggle('bg-primary', i === activeIndex);
                li.classList.toggle('text-white', i === activeIndex);
            });
        }

        function openList() {
            renderList(filter(input.value));
        }

        input.addEventListener('focus', openList);
        input.addEventListener('input', function () {
            selectEl.value = '';
            openList();
        });

        input.addEventListener('blur', function () {
            window.setTimeout(function () {
                list.hidden = true;
                setExpanded(false);
                var match = options.find(function (o) {
                    return o.text.toLowerCase() === input.value.trim().toLowerCase();
                });
                if (match) {
                    pick(match);
                } else if (input.value.trim() === '') {
                    selectEl.value = '';
                } else {
                    input.value = selectEl.value
                        ? (selectEl.selectedOptions[0] && selectEl.selectedOptions[0].textContent
                            ? selectEl.selectedOptions[0].textContent.trim()
                            : '')
                        : '';
                }
            }, 150);
        });

        input.addEventListener('keydown', function (e) {
            var items = filter(input.value);
            var max = Math.min(items.length, 80) - 1;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (list.hidden) {
                    openList();
                }
                activeIndex = activeIndex < max ? activeIndex + 1 : 0;
                highlightOptions();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = activeIndex > 0 ? activeIndex - 1 : max;
                highlightOptions();
            } else if (e.key === 'Enter' && !list.hidden && activeIndex >= 0 && items[activeIndex]) {
                e.preventDefault();
                pick(items[activeIndex]);
            } else if (e.key === 'Escape') {
                list.hidden = true;
                setExpanded(false);
            }
        });

        selectEl._instansiCombobox = {
            input: input,
            setValue: function (value) {
                options = ensureOption(selectEl, options, value);
                selectEl.value = value || '';
                syncInputFromSelect();
            },
            setDisabled: function (disabled) {
                input.disabled = disabled;
                if (disabled) {
                    input.value = '';
                    selectEl.value = '';
                    list.hidden = true;
                    setExpanded(false);
                } else {
                    syncInputFromSelect();
                }
            },
            syncRequired: function (required) {
                input.required = required;
            },
        };

        syncInputFromSelect();
        selectEl._instansiCombobox.setDisabled(selectEl.disabled);
        selectEl._instansiCombobox.syncRequired(selectEl.required);
    }

    function initAllInstansiSearchableSelects(root) {
        root = root || document;
        root.querySelectorAll('select[data-instansi-searchable]').forEach(function (el) {
            initInstansiSearchableSelect(el);
        });
    }

    window.InstansiSearchableSelect = {
        init: initInstansiSearchableSelect,
        initAll: initAllInstansiSearchableSelects,
    };

    document.addEventListener('DOMContentLoaded', function () {
        initAllInstansiSearchableSelects();
    });
})();

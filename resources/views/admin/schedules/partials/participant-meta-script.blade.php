@php
    $scheduleMetaUrl = route('admin.participants.schedule-meta', ['participant' => '__ID__']);
@endphp
<script>
(function () {
    const warning = document.getElementById('participant-interval-warning');
    const ckgField = document.getElementById('participant-ckg-status-field');
    const participantInput = document.querySelector('input[name="participant_id"]');
    const metaUrlTemplate = @json($scheduleMetaUrl);

    function metaUrl(participantId) {
        return metaUrlTemplate.replace('__ID__', encodeURIComponent(participantId));
    }

    function renderCkgField(ckg) {
        if (!ckgField) {
            return;
        }

        if (!ckg) {
            ckgField.innerHTML = '<span class="text-muted">Pilih peserta untuk melihat status CKG.</span>';
            return;
        }

        ckgField.innerHTML =
            '<div class="d-flex flex-wrap align-items-center justify-content-between gap-2">' +
                '<div><span class="badge ' + ckg.badge + '">' + ckg.label + '</span></div>' +
                (ckg.hint ? '<small class="text-muted">' + ckg.hint + '</small>' : '') +
            '</div>';
    }

    async function refreshParticipantMeta(participantId) {
        if (!participantId) {
            if (warning) {
                warning.classList.add('d-none');
            }
            renderCkgField(null);
            return;
        }

        try {
            const response = await fetch(metaUrl(participantId), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('meta request failed');
            }

            const data = await response.json();
            if (warning) {
                warning.classList.toggle('d-none', !data.within_mcu_interval);
            }
            renderCkgField(data.ckg);
        } catch (error) {
            if (warning) {
                warning.classList.add('d-none');
            }
            renderCkgField({
                label: 'Status CKG tidak tersedia',
                badge: 'bg-label-secondary',
                hint: null,
            });
        }
    }

    if (participantInput) {
        participantInput.addEventListener('change', function () {
            refreshParticipantMeta(participantInput.value);
        });
    }
})();
</script>

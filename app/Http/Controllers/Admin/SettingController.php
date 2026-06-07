<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $sections = config('admin_settings.sections', []);
        $activeTab = $request->get('tab', array_key_first($sections) ?: 'general');

        if (! array_key_exists($activeTab, $sections)) {
            $activeTab = array_key_first($sections) ?: 'general';
        }

        $values = [];
        foreach ($sections as $section) {
            foreach ($section['fields'] as $key => $field) {
                $values[$key] = Setting::getValue($key, $field['default'] ?? '');
            }
        }

        $links = collect(config('admin_settings.links', []))
            ->when(! auth()->user()?->hasRole('super_admin'), fn ($c) => $c->where('super_admin_only', false))
            ->values()
            ->all();

        return view('admin.settings.index', compact('sections', 'values', 'activeTab', 'links'));
    }

    public function updateSection(Request $request, string $section)
    {
        $sections = config('admin_settings.sections', []);

        if (! isset($sections[$section])) {
            abort(404);
        }

        $fields = $sections[$section]['fields'];
        $rules = [];
        foreach ($fields as $key => $field) {
            $rules[$key] = $field['rules'] ?? 'nullable|string';
        }

        $validated = $request->validate($rules);

        foreach ($fields as $key => $field) {
            $value = $validated[$key] ?? '';

            if (($field['type'] ?? '') === 'password' && $value === '') {
                continue;
            }

            Setting::setValue(
                $key,
                $value,
                $field['storage_type'] ?? 'string',
                $field['group'] ?? 'general',
                $field['label'] ?? $key
            );
        }

        $label = $sections[$section]['label'] ?? 'Pengaturan';

        return redirect()
            ->route('admin.settings.index', ['tab' => $section])
            ->with('success', "Pengaturan {$label} berhasil disimpan.");
    }

    /**
     * Template email fallback untuk hasil MCU (super admin).
     */
    public function emailResultTemplate()
    {
        $defaultSubject = 'Hasil MCU Anda Tersedia';
        $defaultBody = "Kepada {participant_name},\n\nHasil MCU Anda untuk pemeriksaan tanggal {tanggal_pemeriksaan} telah tersedia.\n\nStatus Kesehatan: {status_kesehatan}\nDiagnosis: {diagnosis}\n\nSilakan login ke {hasil_url} untuk melihat dan mendownload hasil lengkap.\n\nTerima kasih.";
        $subject = Setting::getValue('email_result_subject', $defaultSubject) ?: $defaultSubject;
        $body = Setting::getValue('email_result_template', $defaultBody) ?: $defaultBody;

        return view('admin.settings.email-result-template', compact('subject', 'body'));
    }

    public function updateEmailResultTemplate(Request $request)
    {
        $request->validate([
            'email_result_subject' => 'required|string|max:255',
            'email_result_template' => 'required|string',
        ]);

        Setting::setValue('email_result_subject', $request->email_result_subject, 'string', 'email_template', 'Subject email notifikasi hasil MCU');
        Setting::setValue('email_result_template', $request->email_result_template, 'text', 'email_template', 'Body email notifikasi hasil MCU');

        return redirect()
            ->route('admin.settings.email-result-template')
            ->with('success', 'Template email hasil MCU berhasil disimpan.');
    }
}

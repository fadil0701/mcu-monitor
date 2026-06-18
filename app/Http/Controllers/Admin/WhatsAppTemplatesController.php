<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Rules\MetaWhatsAppTemplateBody;
use App\Support\WhatsAppTemplateDefaults;
use Illuminate\Http\Request;

class WhatsAppTemplatesController extends Controller
{
    public function index()
    {
        $useMetaFormat = WhatsAppTemplateDefaults::usesMetaFormat();
        $defaultInvitation = $useMetaFormat
            ? WhatsAppTemplateDefaults::INVITATION_META
            : WhatsAppTemplateDefaults::INVITATION_LEGACY;
        $defaultResult = $useMetaFormat
            ? WhatsAppTemplateDefaults::RESULT_META
            : WhatsAppTemplateDefaults::RESULT_LEGACY;

        $invitation_template = Setting::getValue('whatsapp_invitation_template', $defaultInvitation) ?: $defaultInvitation;
        $result_template = Setting::getValue('whatsapp_result_template', $defaultResult) ?: $defaultResult;

        return view('admin.whatsapp-templates.index', [
            'invitation_template' => $invitation_template,
            'result_template' => $result_template,
            'useMetaFormat' => $useMetaFormat,
            'invitationLegend' => WhatsAppTemplateDefaults::invitationVariableLegend(),
            'resultLegend' => WhatsAppTemplateDefaults::resultVariableLegend(),
        ]);
    }

    public function update(Request $request)
    {
        $rules = ['invitation_template' => 'required|string'];

        if (WhatsAppTemplateDefaults::usesMetaFormat()) {
            $rules['invitation_template'] = ['required', 'string', new MetaWhatsAppTemplateBody];
        }

        $request->validate($rules);

        try {
            Setting::setValue(
                'whatsapp_invitation_template',
                $request->invitation_template,
                'text',
                'whatsapp_template',
                'Template WhatsApp Undangan'
            );

            return redirect()->route('admin.whatsapp-templates.index')->with('success', 'Template undangan berhasil disimpan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan: '.$e->getMessage())->withInput();
        }
    }

    public function updateResult(Request $request)
    {
        $rules = ['result_template' => 'required|string'];

        if (WhatsAppTemplateDefaults::usesMetaFormat()) {
            $rules['result_template'] = ['required', 'string', new MetaWhatsAppTemplateBody];
        }

        $request->validate($rules);

        try {
            Setting::setValue(
                'whatsapp_result_template',
                $request->result_template,
                'text',
                'whatsapp_template',
                'Template WhatsApp Hasil MCU'
            );

            return redirect()->route('admin.whatsapp-templates.index')->with('success', 'Template hasil MCU berhasil disimpan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan: '.$e->getMessage())->withInput();
        }
    }

    public function reset()
    {
        $default = WhatsAppTemplateDefaults::usesMetaFormat()
            ? WhatsAppTemplateDefaults::INVITATION_META
            : WhatsAppTemplateDefaults::INVITATION_LEGACY;

        Setting::setValue('whatsapp_invitation_template', $default, 'text', 'whatsapp_template', 'Template WhatsApp Undangan');

        return redirect()->route('admin.whatsapp-templates.index')->with('success', 'Template undangan direset ke default.');
    }

    public function resetResult()
    {
        $default = WhatsAppTemplateDefaults::usesMetaFormat()
            ? WhatsAppTemplateDefaults::RESULT_META
            : WhatsAppTemplateDefaults::RESULT_LEGACY;

        Setting::setValue('whatsapp_result_template', $default, 'text', 'whatsapp_template', 'Template WhatsApp Hasil MCU');

        return redirect()->route('admin.whatsapp-templates.index')->with('success', 'Template hasil MCU direset ke default.');
    }
}

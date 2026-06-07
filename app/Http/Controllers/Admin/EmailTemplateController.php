<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = EmailTemplate::query()->orderBy('type')->orderBy('name');
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        $templates = $query->paginate(15)->withQueryString();
        return view('admin.email-templates.index', compact('templates'));
    }

    public function create()
    {
        $availableVariablesByType = EmailTemplate::getVariablesByType();
        return view('admin.email-templates.create', compact('availableVariablesByType'));
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:mcu_invitation,reminder,notification,mcu_result,custom',
            'subject' => 'required|string|max:255',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);
        $valid['is_active'] = (bool) ($request->boolean('is_active', true));
        $valid['is_default'] = (bool) ($request->boolean('is_default', false));
        $valid['variables'] = [];
        EmailTemplate::create($valid);
        return redirect()->route('admin.email-templates.index')->with('success', 'Email template berhasil ditambahkan.');
    }

    public function edit(EmailTemplate $email_template)
    {
        $availableVariablesByType = EmailTemplate::getVariablesByType();
        return view('admin.email-templates.edit', ['emailTemplate' => $email_template, 'availableVariablesByType' => $availableVariablesByType]);
    }

    public function update(Request $request, EmailTemplate $email_template)
    {
        $valid = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:mcu_invitation,reminder,notification,mcu_result,custom',
            'subject' => 'required|string|max:255',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);
        $valid['is_active'] = (bool) ($request->boolean('is_active', true));
        $valid['is_default'] = (bool) ($request->boolean('is_default', false));
        $email_template->update($valid);
        if ($email_template->is_default) {
            $email_template->setAsDefault();
        }
        return redirect()->route('admin.email-templates.index')->with('success', 'Email template berhasil diubah.');
    }

    public function destroy(EmailTemplate $email_template)
    {
        $email_template->delete();
        return redirect()->route('admin.email-templates.index')->with('success', 'Email template berhasil dihapus.');
    }
}

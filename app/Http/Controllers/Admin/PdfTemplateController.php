<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PdfTemplate;
use Illuminate\Http\Request;

class PdfTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = PdfTemplate::query()->orderBy('type')->orderBy('name');
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        $templates = $query->paginate(15)->withQueryString();
        return view('admin.pdf-templates.index', compact('templates'));
    }

    public function create()
    {
        $availableVariablesByType = PdfTemplate::getVariablesByType();
        return view('admin.pdf-templates.create', compact('availableVariablesByType'));
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:mcu_letter,reminder_letter,custom',
            'title' => 'required|string|max:255',
            'combined_html' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);
        $valid['is_active'] = (bool) ($request->boolean('is_active', true));
        $valid['is_default'] = (bool) ($request->boolean('is_default', false));
        $valid['variables'] = $valid['settings'] = $valid['image_settings'] = [];
        PdfTemplate::create($valid);
        return redirect()->route('admin.pdf-templates.index')->with('success', 'PDF template berhasil ditambahkan.');
    }

    public function edit(PdfTemplate $pdf_template)
    {
        $availableVariablesByType = PdfTemplate::getVariablesByType();
        return view('admin.pdf-templates.edit', ['pdfTemplate' => $pdf_template, 'availableVariablesByType' => $availableVariablesByType]);
    }

    public function update(Request $request, PdfTemplate $pdf_template)
    {
        $valid = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:mcu_letter,reminder_letter,custom',
            'title' => 'required|string|max:255',
            'combined_html' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);
        $valid['is_active'] = (bool) ($request->boolean('is_active', true));
        $valid['is_default'] = (bool) ($request->boolean('is_default', false));
        $pdf_template->update($valid);
        if ($pdf_template->is_default) {
            $pdf_template->setAsDefault();
        }
        return redirect()->route('admin.pdf-templates.index')->with('success', 'PDF template berhasil diubah.');
    }

    public function destroy(PdfTemplate $pdf_template)
    {
        $pdf_template->delete();
        return redirect()->route('admin.pdf-templates.index')->with('success', 'PDF template berhasil dihapus.');
    }
}

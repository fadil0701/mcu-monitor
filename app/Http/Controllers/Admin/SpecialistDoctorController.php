<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SpecialistDoctor;
use Illuminate\Http\Request;

class SpecialistDoctorController extends Controller
{
    public function index(Request $request)
    {
        $query = SpecialistDoctor::query()->orderBy('name');
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->where('name', 'like', "%{$q}%")
                    ->orWhere('specialty', 'like', "%{$q}%");
            });
        }
        if ($request->filled('is_active')) {
            if ($request->is_active === '1') {
                $query->where('is_active', true);
            } elseif ($request->is_active === '0') {
                $query->where('is_active', false);
            }
        }
        $doctors = $query->paginate(15)->withQueryString();
        return view('admin.specialist-doctors.index', compact('doctors'));
    }

    public function create()
    {
        return view('admin.specialist-doctors.create');
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'name' => 'required|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        $valid['is_active'] = (bool) ($request->boolean('is_active', true));
        SpecialistDoctor::create($valid);
        return redirect()->route('admin.specialist-doctors.index')->with('success', 'Dokter spesialis berhasil ditambahkan.');
    }

    public function edit(SpecialistDoctor $specialist_doctor)
    {
        return view('admin.specialist-doctors.edit', ['specialistDoctor' => $specialist_doctor]);
    }

    public function update(Request $request, SpecialistDoctor $specialist_doctor)
    {
        $valid = $request->validate([
            'name' => 'required|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        $valid['is_active'] = (bool) ($request->boolean('is_active', true));
        $specialist_doctor->update($valid);
        return redirect()->route('admin.specialist-doctors.index')->with('success', 'Dokter spesialis berhasil diubah.');
    }

    public function destroy(SpecialistDoctor $specialist_doctor)
    {
        $specialist_doctor->delete();
        return redirect()->route('admin.specialist-doctors.index')->with('success', 'Dokter spesialis berhasil dihapus.');
    }
}

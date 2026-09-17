<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kiosk;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KioskController extends Controller
{
    public function index(): View
    {
        $kiosks = Kiosk::with('assignedStaff')->orderBy('name')->get();
        return view('admin.kiosks.index', compact('kiosks'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'identifier' => 'nullable|string|max:100|unique:kiosks,identifier',
            'assigned_staff_id' => 'nullable|exists:users,id',
            'assigned_user_id' => 'nullable|exists:users,id',
        ]);

        if (empty($data['assigned_staff_id']) && !empty($data['assigned_user_id'])) {
            $data['assigned_staff_id'] = $data['assigned_user_id'];
        }
        unset($data['assigned_user_id']);

        if (empty($data['identifier'])) {
            $data['identifier'] = Kiosk::generateUniqueIdentifier();
        }

        $kiosk = Kiosk::create($data);
        AuditLogger::log('kiosk.created', $kiosk, [], $kiosk->toArray());

        return back()->with('success', "Kiosk '{$kiosk->name}' created.");
    }

    public function update(Request $request, Kiosk $kiosk): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'assigned_staff_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
        ]);

        $kiosk->update($data);
        return back()->with('success', 'Kiosk updated.');
    }

    public function destroy(Kiosk $kiosk): RedirectResponse
    {
        $kiosk->delete();
        return back()->with('success', 'Kiosk removed.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Kiosk;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with(['student', 'kiosk.assignedEvent', 'creator'])
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'active') {
                    $q->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $q->where('is_active', false);
                }
            })
            ->when($request->event_id, function ($q) use ($request) {
                $q->whereHas('kiosk', fn($kq) => $kq->where('assigned_event_id', $request->event_id));
            })
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhereHas('kiosk', function ($kq) use ($search) {
                            $kq->where('name', 'like', "%{$search}%")
                               ->orWhere('identifier', 'like', "%{$search}%");
                        });
                });
            });

        $users = $query->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $events = Event::orderByDesc('event_date')->get();

        $roles = [
            '' => 'All Roles',
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'kiosk' => 'Kiosk',
            'staff' => 'Staff',
            'treasurer' => 'Treasurer',
            'student' => 'Student',
        ];

        $statuses = [
            '' => 'All Statuses',
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];

        $eventOptions = ['' => 'All Events'];
        foreach ($events as $ev) {
            $eventOptions[$ev->id] = $ev->name;
        }

        return view('admin.users.index', compact('users', 'events', 'roles', 'statuses', 'eventOptions'));
    }

    public function create(Request $request): View
    {
        $events = Event::orderByDesc('event_date')->get();
        $initialType = $request->query('type', 'admin');
        return view('admin.users.create', compact('events', 'initialType'));
    }

    public function store(Request $request): RedirectResponse
    {
        $accountType = $request->input('account_type', 'admin');

        if ($accountType === 'kiosk') {
            $validated = $request->validate([
                'kiosk_name' => ['required', 'string', 'max:255'],
                'kiosk_code' => ['required', 'string', 'max:100', 'unique:kiosks,identifier'],
                'username' => ['required', 'string', 'max:100', 'unique:users,username', 'unique:users,email'],
                'assigned_event_id' => ['nullable', 'exists:events,id'],
                'password' => ['required', 'string', Password::min(8), 'confirmed'],
                'is_active' => ['nullable', 'boolean'],
            ]);

            $status = $request->boolean('is_active', true);

            $user = DB::transaction(function () use ($validated, $status) {
                $user = User::create([
                    'name' => trim($validated['kiosk_name']),
                    'username' => trim($validated['username']),
                    'email' => trim($validated['username']) . '@kiosk.local',
                    'password' => Hash::make($validated['password']),
                    'role' => 'kiosk',
                    'is_active' => $status,
                    'created_by' => auth()->id(),
                ]);

                Kiosk::create([
                    'user_id' => $user->id,
                    'name' => trim($validated['kiosk_name']),
                    'identifier' => strtoupper(trim($validated['kiosk_code'])),
                    'assigned_event_id' => $validated['assigned_event_id'] ?: null,
                    'assigned_staff_id' => $user->id,
                    'created_by' => auth()->id(),
                    'is_active' => $status,
                ]);

                return $user;
            });

            AuditLogger::log('account.kiosk_created', $user, [], [
                'name' => $user->name,
                'username' => $user->username,
                'kiosk_code' => $validated['kiosk_code'],
                'assigned_event_id' => $validated['assigned_event_id'] ?? null,
                'is_active' => $status,
            ]);

            return redirect()->route('admin.users.index')->with('success', "Kiosk account '{$user->name}' created successfully.");
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255', 'unique:users,email', 'unique:users,username'],
            'role' => ['required', Rule::in(['admin', 'super_admin', 'staff', 'treasurer'])],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $status = $request->boolean('is_active', true);

        $input = trim($validated['email']);
        $isEmail = filter_var($input, FILTER_VALIDATE_EMAIL);
        $email = $isEmail ? $input : ($input . '@admin.local');
        $username = $isEmail ? strstr($input, '@', true) : $input;

        $user = User::create([
            'name' => trim($validated['name']),
            'email' => $email,
            'username' => $username,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => $status,
            'created_by' => auth()->id(),
        ]);

        AuditLogger::log('account.admin_created', $user, [], [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $status,
        ]);

        return redirect()->route('admin.users.index')->with('success', "Account '{$user->name}' created successfully.");
    }

    public function edit(User $user): View
    {
        $user->load(['kiosk', 'student']);
        $events = Event::orderByDesc('event_date')->get();
        return view('admin.users.edit', compact('user', 'events'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $currentUser = auth()->user();

        if ($user->role === 'super_admin' && !$currentUser->isSuperAdmin()) {
            abort(403, 'Only a Super Admin can edit Super Admin accounts.');
        }

        if ($user->role === 'kiosk') {
            $validated = $request->validate([
                'kiosk_name' => ['required', 'string', 'max:255'],
                'kiosk_code' => ['required', 'string', 'max:100', Rule::unique('kiosks', 'identifier')->ignore($user->kiosk?->id)],
                'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user->id)],
                'assigned_event_id' => ['nullable', 'exists:events,id'],
                'is_active' => ['nullable', 'boolean'],
                'password' => ['nullable', 'string', Password::min(8), 'confirmed'],
            ]);

            $isActive = $request->boolean('is_active', true);
            $oldUser = $user->only(['name', 'username', 'is_active', 'role']);
            $oldKiosk = $user->kiosk ? $user->kiosk->only(['name', 'identifier', 'assigned_event_id', 'is_active']) : [];

            DB::transaction(function () use ($user, $validated, $isActive) {
                $userData = [
                    'name' => trim($validated['kiosk_name']),
                    'username' => trim($validated['username']),
                    'email' => trim($validated['username']) . '@kiosk.local',
                    'is_active' => $isActive,
                ];

                if (!empty($validated['password'])) {
                    $userData['password'] = Hash::make($validated['password']);
                }

                $user->update($userData);

                $kiosk = $user->kiosk;
                if ($kiosk) {
                    $kiosk->update([
                        'name' => trim($validated['kiosk_name']),
                        'identifier' => strtoupper(trim($validated['kiosk_code'])),
                        'assigned_event_id' => $validated['assigned_event_id'] ?: null,
                        'is_active' => $isActive,
                    ]);
                } else {
                    Kiosk::create([
                        'user_id' => $user->id,
                        'name' => trim($validated['kiosk_name']),
                        'identifier' => strtoupper(trim($validated['kiosk_code'])),
                        'assigned_event_id' => $validated['assigned_event_id'] ?: null,
                        'assigned_staff_id' => $user->id,
                        'created_by' => auth()->id(),
                        'is_active' => $isActive,
                    ]);
                }
            });

            AuditLogger::log('account.kiosk_updated', $user, [
                'user' => $oldUser,
                'kiosk' => $oldKiosk,
            ], [
                'user' => $user->fresh()->only(['name', 'username', 'is_active', 'role']),
                'kiosk' => $user->fresh()->kiosk?->only(['name', 'identifier', 'assigned_event_id', 'is_active']),
            ]);

            return redirect()->route('admin.users.index')->with('success', "Kiosk account '{$user->name}' updated.");
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['super_admin', 'admin', 'treasurer', 'staff', 'student'])],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', Password::min(8), 'confirmed'],
        ]);

        if ($user->id === $currentUser->id && $validated['role'] !== $user->role) {
            return back()->withErrors(['role' => 'You cannot change your own role.'])->withInput();
        }

        if ($user->role === 'super_admin' && $validated['role'] !== 'super_admin') {
            $otherSuperAdmins = User::where('role', 'super_admin')
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherSuperAdmins === 0) {
                return back()->withErrors(['role' => 'Cannot downgrade the only active Super Admin account.'])->withInput();
            }
        }

        $isActive = $request->boolean('is_active', true);
        if ($user->role === 'super_admin' && !$isActive && $user->is_active) {
            $otherSuperAdmins = User::where('role', 'super_admin')
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherSuperAdmins === 0) {
                return back()->withErrors(['is_active' => 'Cannot deactivate the only active Super Admin account.'])->withInput();
            }
        }

        $old = $user->only(['name', 'email', 'role', 'is_active']);

        $updateData = [
            'name' => trim($validated['name']),
            'email' => trim($validated['email']),
            'role' => $validated['role'],
            'is_active' => $isActive,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        AuditLogger::log('account.updated', $user, $old, $user->fresh()->only(['name', 'email', 'role', 'is_active']));

        return redirect()->route('admin.users.index')->with('success', "Account '{$user->name}' updated.");
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $currentUser = auth()->user();

        if ($user->role === 'super_admin' && !$currentUser->isSuperAdmin()) {
            abort(403, 'Only a Super Admin can change Super Admin status.');
        }

        if ($user->id === $currentUser->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        if ($user->role === 'super_admin' && $user->is_active) {
            $activeSuperAdmins = User::where('role', 'super_admin')
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($activeSuperAdmins === 0) {
                return back()->with('error', 'Cannot deactivate the last active Super Admin account.');
            }
        }

        $user->is_active = !$user->is_active;
        $user->save();

        if ($user->kiosk) {
            $user->kiosk->is_active = $user->is_active;
            $user->kiosk->save();
        }

        $action = $user->is_active ? 'account.activated' : 'account.deactivated';
        AuditLogger::log($action, $user, ['is_active' => !$user->is_active], ['is_active' => $user->is_active]);

        $statusText = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Account '{$user->name}' has been {$statusText}.");
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $currentUser = auth()->user();

        if ($user->role === 'super_admin' && !$currentUser->isSuperAdmin()) {
            abort(403, 'Only a Super Admin can reset Super Admin passwords.');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        AuditLogger::log('account.password_reset', $user, [], [
            'reset_by' => $currentUser->id,
        ]);

        return back()->with('success', "Password for '{$user->name}' has been reset successfully.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->role === 'super_admin') {
            return back()->with('error', 'Super Admin accounts cannot be deleted. Deactivate instead.');
        }

        return back()->with('error', 'To preserve historical records and audit logs, accounts cannot be permanently deleted. Please deactivate the account instead.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdminAccess($request);
        $perPage = (int) $request->integer('per_page', 10);
        $perPage = in_array($perPage, [10, 50, 100], true) ? $perPage : 10;
        $selectedClass = $request->query('class');
        $selectedClass = in_array($selectedClass, ['A', 'B', 'C', 'D'], true) ? $selectedClass : null;

        $classOptions = collect(['A', 'B', 'C', 'D'])->merge(User::query()
            ->where('role', 'mahasiswa')
            ->whereNotNull('class')
            ->distinct()
            ->orderBy('class')
            ->pluck('class'))
            ->unique()
            ->values();

        $users = User::query()
            ->where('role', '!=', 'superadmin')
            ->when($selectedClass, fn ($query) => $query->where('class', $selectedClass))
            ->orderByRaw('LENGTH(identity_id)')
            ->orderBy('identity_id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.users', [
            'users' => $users,
            'perPage' => $perPage,
            'selectedClass' => $selectedClass,
            'classOptions' => $classOptions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->authorizeAdminAccess($request);
        $validated = $this->validateUserData($request);

        $this->authorizeRoleAssignment($actor, $validated['role']);

        $validated['status'] = 'active';
        $validated['email_verified_at'] = now();

        User::create($validated);

        return back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $actor = $this->authorizeAdminAccess($request);
        $validated = $this->validateUserData($request, $user);
        $this->authorizeRoleAssignment($actor, $validated['role']);

        if ($actor->role === 'superadmin' && $user->role !== 'superadmin' && isset($validated['status'])) {
            $user->update(['status' => $validated['status']]);
        }
        unset($validated['status']);

        $user->update($validated);

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $this->authorizeAdminAccess($request);

        abort_if(
            $actor->is($user),
            422,
            'The currently signed-in account cannot be deleted from this page.'
        );

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }

    private function authorizeAdminAccess(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor?->isAdmin(), 403, 'You do not have access to this page.');

        return $actor;
    }

    private function authorizeRoleAssignment(User $actor, string $role): void
    {
        abort_if(
            $actor->role !== 'superadmin' && $role === 'superadmin',
            403,
            'Only superadmins can create or promote a superadmin account.'
        );
    }

    private function validateUserData(Request $request, ?User $user = null): array
    {
        $passwordRules = $user
            ? ['nullable', 'string', 'min:8', 'confirmed']
            : ['required', 'string', 'min:8', 'confirmed'];

        $validated = $request->validate([
            'identity_id' => [
                'required',
                'string',
                'regex:/^[0-9]+$/',
                'max:18',
                Rule::unique('user', 'identity_id')->ignore($user?->getKey(), 'id_user'),
            ],
            'name'     => ['required', 'string', 'max:60'],
            'email'    => [
                'required',
                'email',
                'max:40',
                Rule::unique('user', 'email')->ignore($user?->getKey(), 'id_user'),
            ],
            'role'     => ['required', Rule::in(['superadmin', 'dosen', 'mahasiswa'])],
            'class'    => ['nullable', Rule::requiredIf($request->input('role') === 'mahasiswa'), Rule::in(['A', 'B', 'C', 'D'])],
            'password' => $passwordRules,
            'status'   => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        if ($validated['role'] !== 'mahasiswa') {
            $validated['class'] = null;
        }

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        return $validated;
    }
}

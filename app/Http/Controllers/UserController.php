<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdminAccess($request);
        $perPage = (int) $request->integer('per_page', 10);
        $perPage = in_array($perPage, [10, 50, 100], true) ? $perPage : 10;

        $users = User::query()
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.users', [
            'users' => $users,
            'perPage' => $perPage,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->authorizeAdminAccess($request);
        $validated = $this->validateUserData($request);

        $this->authorizeRoleAssignment($actor, $validated['role']);

        User::create($validated);

        return back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $actor = $this->authorizeAdminAccess($request);
        $validated = $this->validateUserData($request, $user);

        $this->ensureOwnRoleIsUnchanged($actor, $user, $validated['role']);
        $this->authorizeManagedUser($actor, $user);
        $this->authorizeRoleAssignment($actor, $validated['role']);
        $this->ensureSuperadminStillExists($user, $validated['role']);

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

        $this->authorizeManagedUser($actor, $user);
        $this->ensureSuperadminStillExists($user);

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }

    private function authorizeAdminAccess(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor?->isAdmin(), 403, 'You do not have access to this page.');

        return $actor;
    }

    private function authorizeManagedUser(User $actor, User $target): void
    {
        abort_if(
            $actor->role !== 'superadmin' && $target->role === 'superadmin',
            403,
            'Only superadmins can manage superadmin accounts.'
        );
    }

    private function authorizeRoleAssignment(User $actor, string $role): void
    {
        abort_if(
            $actor->role !== 'superadmin' && $role === 'superadmin',
            403,
            'Only superadmins can create or promote a superadmin account.'
        );
    }

    private function ensureSuperadminStillExists(User $user, ?string $nextRole = null): void
    {
        $roleAfterUpdate = $nextRole ?? 'deleted';
        $isLeavingSuperadminRole = $user->role === 'superadmin' && $roleAfterUpdate !== 'superadmin';

        abort_if(
            $isLeavingSuperadminRole && User::where('role', 'superadmin')->count() <= 1,
            422,
            'At least one active superadmin account must remain.'
        );
    }

    private function ensureOwnRoleIsUnchanged(User $actor, User $target, string $nextRole): void
    {
        abort_if(
            $actor->is($target) && $target->role !== $nextRole,
            422,
            'You cannot change your own role from this page.'
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
                'max:50',
                Rule::unique('users', 'identity_id')->ignore($user?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'role' => ['required', Rule::in(['superadmin', 'dosen', 'mahasiswa'])],
            'password' => $passwordRules,
        ]);

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        return $validated;
    }
}

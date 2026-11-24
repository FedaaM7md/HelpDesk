<?php

namespace App\Http\Controllers\App;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->when($request->query('role'), fn ($query, $role) => $query->where('role', $role))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('app.users.index', [
            'users' => $users,
            'term' => $term,
            'roles' => UserRole::options(),
        ]);
    }

    public function create(): View
    {
        return view('app.users.form', [
            'user' => new User(['role' => UserRole::Agent, 'locale' => 'ar', 'is_active' => true]),
            'roles' => UserRole::options(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->user()->isDemo() && $validated['role'] === UserRole::Admin->value) {
            throw ValidationException::withMessages([
                'role' => __('app.users.demo_cannot_create_admin'),
            ]);
        }

        $member = User::create([
            'name' => $validated['name'],
            'email' => mb_strtolower($validated['email']),
            'role' => $validated['role'],
            'job_title' => $validated['job_title'] ?? null,
            'locale' => $validated['locale'],
            'password' => $validated['password'],
            'is_active' => $request->boolean('is_active'),
        ]);

        Audit::record('user.created', $member, [
            'email' => mb_strtolower($validated['email']),
            'role' => $validated['role'],
        ]);

        return redirect()->route('app.users.index')->with('status', __('app.users.created'));
    }

    public function edit(User $user): View
    {
        return view('app.users.form', [
            'user' => $user,
            'roles' => UserRole::options(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $isActive = $request->boolean('is_active');

        if ($user->isDemo()) {
            $changesIdentity = mb_strtolower($validated['email']) !== $user->email
                || ! empty($validated['password'])
                || UserRole::from($validated['role']) !== $user->role
                || ! $isActive;

            if ($changesIdentity) {
                throw ValidationException::withMessages([
                    'email' => __('app.users.demo_locked'),
                ]);
            }
        }

        if ($user->is($request->user()) && ($validated['role'] !== UserRole::Admin->value || ! $isActive)) {
            throw ValidationException::withMessages([
                'role' => __('app.users.self_lockout'),
            ]);
        }

        $this->guardLastAdmin($user, UserRole::from($validated['role']), $isActive);

        $data = [
            'name' => $validated['name'],
            'email' => mb_strtolower($validated['email']),
            'role' => $validated['role'],
            'job_title' => $validated['job_title'] ?? null,
            'locale' => $validated['locale'],
            'is_active' => $isActive,
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $user->update($data);

        Audit::record('user.updated', $user, [
            'role' => $validated['role'],
            'is_active' => $isActive,
        ]);

        return redirect()->route('app.users.index')->with('status', __('app.users.updated'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            throw ValidationException::withMessages([
                'user' => __('app.users.cannot_delete_self'),
            ]);
        }

        if ($user->isDemo()) {
            throw ValidationException::withMessages([
                'user' => __('app.users.demo_protected'),
            ]);
        }

        $this->guardLastAdmin($user, UserRole::Agent, false);

        $user->assignedTickets()->update(['assigned_to' => null]);
        Audit::record('user.deleted', $user, [
            'email' => $user->email,
        ]);
        $user->delete();

        return redirect()->route('app.users.index')->with('status', __('app.users.deleted'));
    }

    protected function guardLastAdmin(User $user, UserRole $newRole, bool $willBeActive): void
    {
        $currentlyActiveAdmin = $user->role === UserRole::Admin && $user->is_active;
        $remainsActiveAdmin = $newRole === UserRole::Admin && $willBeActive;

        if ($currentlyActiveAdmin && ! $remainsActiveAdmin) {
            $others = User::query()
                ->where('role', UserRole::Admin->value)
                ->where('is_active', true)
                ->whereKeyNot($user->id)
                ->count();

            if ($others === 0) {
                throw ValidationException::withMessages([
                    'role' => __('app.users.last_admin'),
                ]);
            }
        }
    }
}

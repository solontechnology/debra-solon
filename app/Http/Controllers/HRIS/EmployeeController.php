<?php

namespace App\Http\Controllers\HRIS;

use App\Http\Controllers\Controller;
use App\Models\Divisi;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeEmployeeAction($request, 'list');

        $employees = Employee::query()
            ->with('user.roles')
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q')->toString();
                $query->where(function ($employeeQuery) use ($search) {
                    $employeeQuery->where('nik', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%")
                        ->orWhere('division', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('pages.hris.employee.index', compact('employees'));
    }

    public function create()
    {
        $this->authorizeEmployeeAction(request(), 'create');

        return view('pages.hris.employee.create', [
            'roles' => $this->roles(),
            'divisions' => $this->divisions(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeEmployeeAction($request, 'create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'max:255', 'unique:employees,nik'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'position' => ['nullable', 'string', 'max:255'],
            'division' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:255', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'array', 'min:1'],
            'role.*' => ['required', 'integer', 'exists:roles,id'],
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
            ]);
            $user->syncRoles($this->rolesByIds($validated['role']));

            Employee::create([
                'user_id' => $user->id,
                'nik' => $validated['nik'],
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'address' => $validated['address'] ?? null,
                'position' => $validated['position'] ?? null,
                'division' => $validated['division'] ?? null,
            ]);
        });

        return redirect()->route('hris.employee.index')->with('success', 'Data employee dan login berhasil ditambahkan.');
    }

    public function edit(Employee $employee)
    {
        $this->authorizeEmployeeAction(request(), 'edit');

        $employee->load('user.roles');

        return view('pages.hris.employee.edit', [
            'employee' => $employee,
            'roles' => $this->roles(),
            'divisions' => $this->divisions(),
            'userRole' => $employee->user?->roles->pluck('id')->all() ?? [],
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $this->authorizeEmployeeAction($request, 'edit');

        $user = $employee->user;
        $usernameRule = Rule::unique('users', 'username');
        $emailRule = Rule::unique('users', 'email');
        $phoneRule = Rule::unique('users', 'phone');

        if ($user) {
            $usernameRule->ignore($user->id);
            $emailRule->ignore($user->id);
            $phoneRule->ignore($user->id);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'max:255', Rule::unique('employees', 'nik')->ignore($employee->id)],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'position' => ['nullable', 'string', 'max:255'],
            'division' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', $usernameRule],
            'email' => ['required', 'email', 'max:255', $emailRule],
            'phone' => ['nullable', 'string', 'max:255', $phoneRule],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', 'array', 'min:1'],
            'role.*' => ['required', 'integer', 'exists:roles,id'],
        ]);

        DB::transaction(function () use ($validated, $employee, $user) {
            $loginData = [
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
            ];
            if (! empty($validated['password'])) {
                $loginData['password'] = Hash::make($validated['password']);
            }

            if ($user) {
                $user->update($loginData);
            } else {
                $user = User::create($loginData);
                $employee->user()->associate($user);
            }

            $user->syncRoles($this->rolesByIds($validated['role']));

            $employee->update([
                'nik' => $validated['nik'],
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'address' => $validated['address'] ?? null,
                'position' => $validated['position'] ?? null,
                'division' => $validated['division'] ?? null,
            ]);
            $employee->save();
        });

        return redirect()->route('hris.employee.index')->with('success', 'Data employee dan login berhasil diperbarui.');
    }

    public function destroy(Employee $employee)
    {
        $this->authorizeEmployeeAction(request(), 'delete');

        $employee->delete();

        return redirect()->route('hris.employee.index')
            ->with('success', 'Data employee dihapus. Akun login tetap tersedia di menu User.');
    }

    private function roles()
    {
        return Role::query()->orderBy('name')->get(['id', 'name']);
    }

    private function rolesByIds(array $roleIds)
    {
        return Role::query()->whereIn('id', $roleIds)->get();
    }

    private function divisions()
    {
        return Divisi::query()->orderBy('nama')->pluck('nama');
    }

    private function authorizeEmployeeAction(Request $request, string $action): void
    {
        abort_unless($request->user()->can("hris/employee/{$action}"), 403);
    }
}

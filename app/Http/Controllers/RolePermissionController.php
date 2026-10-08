<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{


    public function index(Request $request)
    {
        $search = $request->q;

        $roles = Role::when($search, function ($query) use ($search) {
            return $query->where("name", "LIKE", "%$search%");
        })->orderBy("id", "desc")->paginate(32);

        return view("pages.role-permission.index", compact("roles"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("pages.role-permission.create", [
            'permissionMatrix' => $this->permissionMatrix(),
            'selectedPermissionIds' => [],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string'
        ]);

        DB::beginTransaction();

        try {

            $role = Role::create([
                'name' => $request->name
            ]);

            if ($request->permissions) {
                foreach ($request->permissions as $itempermissions) {
                    $permission = Permission::findById($itempermissions);

                    if ($permission) {
                        $role->givePermissionTo($permission);
                    }
                }
            }



            DB::commit();

            return redirect()->route("akses.role.index")->with("success", "Berhasil tambah data");
        } catch (Exception $th) {
            DB::rollBack();
            return redirect()->back()->with("error", "Gagal simpan data ");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $roles = Role::findById($id);

        return view('pages.role-permission.edit', [
            'itemRol' => $roles,
            'permissionMatrix' => $this->permissionMatrix(),
            'selectedPermissionIds' => $roles->permissions->pluck('id')->map(fn ($id) => (string) $id)->all(),
        ]);
    }

    private function permissionMatrix(): array
    {
        $groups = getAllPermission();
        $allNames = collect($groups)->flatten()->unique()->values();
        foreach ($allNames as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }
        $permissions = Permission::whereIn('name', $allNames)->get()->keyBy('name');
        $actionAliases = [
            'list' => 'read',
            'detail' => 'read',
            'view' => 'read',
            'read' => 'read',
            'create' => 'create',
            'add' => 'create',
            'tambah-item' => 'create',
            'edit' => 'edit',
            'edit-item' => 'edit',
            'delete' => 'delete',
        ];
        $matrix = [];

        foreach ($groups as $menu => $permissionNames) {
            $rows = [];

            foreach ($permissionNames as $permissionName) {
                $permission = $permissions->get($permissionName);
                if (!$permission) {
                    continue;
                }

                $prefix = $menu . '/';
                $relativeName = str_starts_with($permissionName, $prefix)
                    ? substr($permissionName, strlen($prefix))
                    : $permissionName;
                $segments = explode('/', $relativeName);
                $lastSegment = end($segments);
                $action = $actionAliases[$lastSegment] ?? 'special';

                if ($action !== 'special') {
                    array_pop($segments);
                }

                $rowKey = implode('/', $segments);
                $rowLabel = $rowKey === ''
                    ? 'Menu Utama'
                    : $this->formatPermissionLabel($rowKey);

                if (!isset($rows[$rowKey])) {
                    $rows[$rowKey] = [
                        'label' => $rowLabel,
                        'is_submenu' => $rowKey !== '',
                        'permissions' => [
                            'create' => [],
                            'read' => [],
                            'edit' => [],
                            'delete' => [],
                            'special' => [],
                        ],
                    ];
                }

                $rows[$rowKey]['permissions'][$action][] = [
                    'id' => $permission->id,
                    'caption' => $this->formatPermissionLabel($lastSegment),
                ];
            }

            if ($rows !== []) {
                $matrix[] = [
                    'label' => $this->formatPermissionLabel($menu),
                    'rows' => array_values($rows),
                ];
            }
        }

        return $matrix;
    }

    private function formatPermissionLabel(string $value): string
    {
        return ucwords(str_replace(['-', '_', '/'], ' ', $value));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string'
        ]);


        $role = Role::findById($id);
        if (!$role) {
            return redirect()->back()->with("message", "Data tidak di temukan");
        }

        $permissionName = [];
        if ($request->permissions) {
            foreach ($request->permissions as $itempermissions) {
                $permission = Permission::findById($itempermissions);

                if ($permission) {
                    array_push($permissionName, $permission->name);
                }
            }
        }

        $role->update(['name' => $request->name]);

        $permissions = Permission::whereIn('name', $permissionName)->get()->pluck('name');
        $role->syncPermissions($permissions);

        return redirect()->route("akses.role.index")->with("success", "Berhasil update data");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);

        $role->delete();


        return redirect()->back()->with("success", "Berhasil hapus data");
    }
}

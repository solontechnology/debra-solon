<div class="table-responsive">
    <table class="table table-bordered table-sm align-middle" data-sortable="false">
        <thead class="table-light">
            <tr>
                <th scope="col">Menu / Sub Menu</th>
                <th scope="col" class="text-center">Create</th>
                <th scope="col" class="text-center">Read</th>
                <th scope="col" class="text-center">Edit</th>
                <th scope="col" class="text-center">Delete</th>
                <th scope="col" class="text-center">Akses Khusus</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($permissionMatrix as $groupIndex => $group)
                <tr class="table-secondary">
                    <th scope="row" colspan="6">{{ $group['label'] }}</th>
                </tr>
                @foreach ($group['rows'] as $rowIndex => $row)
                    <tr>
                        <th scope="row" class="{{ $row['is_submenu'] ? 'ps-4 fw-normal' : '' }}">
                            {{ $row['label'] }}
                        </th>
                        @foreach (['create', 'read', 'edit', 'delete', 'special'] as $action)
                            <td class="text-center">
                                @foreach ($row['permissions'][$action] as $permission)
                                    <label class="d-inline-flex align-items-center gap-1 mx-1"
                                        title="{{ $permission['caption'] }}">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission['id'] }}"
                                            @checked(in_array((string) $permission['id'], $selectedPermissionIds, true))
                                            aria-label="{{ $group['label'] }} - {{ $row['label'] }} - {{ $permission['caption'] }}">
                                        @if (count($row['permissions'][$action]) > 1 || $action === 'special')
                                            <span class="small text-muted">{{ $permission['caption'] }}</span>
                                        @endif
                                    </label>
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">Tidak ada permission yang tersedia.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@extends('layouts.admin')

@section('title')
    Workflow Job Akta
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h3 class="card-title mb-1">Workflow Job Akta</h3>
                <div class="text-secondary small">Atur tahapan dan persetujuan untuk setiap kategori pekerjaan.</div>
            </div>
            <form action="{{ route('setting.workflow-akta.index') }}" method="GET" class="d-flex align-items-center gap-2">
                <label for="workflow-category" class="form-label mb-0">Kategori</label>
                <select id="workflow-category" name="kategori" class="form-select" onchange="this.form.submit()">
                    @foreach ($categories as $key => $label)
                        <option value="{{ $key }}" @selected($category === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <form action="{{ route('setting.workflow-akta.update', $category) }}" method="POST" id="workflow-form">
            @csrf
            @method('PUT')

            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if (session('info'))
                    <div class="alert alert-info">{{ session('info') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="alert alert-info">
                    Untuk stage penugasan, pilih tipe assigner (user atau role), lalu pilih siapa yang berwenang
                    menunjuk petugas. Petugas yang ditunjuk
                    mengerjakan stage dan mengajukan hasil. Jika approval aktif, user atau role approver yang dipilih
                    dapat menyetujui atau menolak hasilnya.
                    Jika ditolak, petugas memperbaiki dan mengajukan ulang stage yang sama.
                    Hapus stage tidak menghapus riwayat yang sudah tercatat.
                </div>

                <div id="workflow-stages" class="d-flex flex-column gap-3">
                    @foreach ($stages as $index => $stage)
                        <div class="workflow-stage border rounded p-3" data-stage>
                            <input type="hidden" name="stages[{{ $index }}][id]" value="{{ $stage['id'] ?? '' }}" data-field="id">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary-subtle text-primary" data-stage-number>{{ $index + 1 }}</span>
                                    <strong data-stage-heading>{{ $stage['name'] }}</strong>
                                </div>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-move="up" aria-label="Pindah ke atas">
                                        <i class="bi bi-arrow-up"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-move="down" aria-label="Pindah ke bawah">
                                        <i class="bi bi-arrow-down"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" data-remove-stage>
                                        <i class="bi bi-trash me-1"></i>Hapus
                                    </button>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-lg-4">
                                    <label class="form-label">Nama Stage</label>
                                    <input type="text" class="form-control" maxlength="255" required
                                        name="stages[{{ $index }}][name]" value="{{ $stage['name'] }}" data-field="name">
                                </div>
                                <div class="col-lg-2">
                                    <div class="form-check mt-lg-4 pt-lg-2">
                                        <input class="form-check-input" type="checkbox" value="1"
                                            id="assignment-{{ $index }}" name="stages[{{ $index }}][is_assignment]"
                                            @checked($stage['is_assignment'] ?? false) data-field="is_assignment">
                                        <label class="form-check-label" for="assignment-{{ $index }}">Stage perlu penugasan</label>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" value="1"
                                            id="approval-{{ $index }}" name="stages[{{ $index }}][is_approval]"
                                            @checked($stage['is_approval'] ?? false) data-field="is_approval">
                                        <label class="form-check-label" for="approval-{{ $index }}">Hasil perlu approval</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div @class(['col-12', 'd-none' => !($stage['is_approval'] ?? false)]) data-approval-settings>
                                    <div class="row g-3">
                                        <div class="col-lg-3">
                                            <label class="form-label">Tipe approver</label>
                                            <select class="form-select" data-field="approver_type"
                                                @disabled(!($stage['is_approval'] ?? false))>
                                                <option value="user" @selected(($stage['approver_type'] ?? 'user') === 'user')>Berdasarkan user</option>
                                                <option value="role" @selected(($stage['approver_type'] ?? 'user') === 'role')>Berdasarkan role</option>
                                            </select>
                                        </div>
                                        <div @class(['col-lg-3', 'd-none' => ($stage['approver_type'] ?? 'user') !== 'user']) data-approver-picker="user">
                                            <label class="form-label">User approver</label>
                                            <select class="form-select select2" data-field="users" data-placeholder="Pilih user approver"
                                                @disabled(!($stage['is_approval'] ?? false) || ($stage['approver_type'] ?? 'user') !== 'user')>
                                                <option value="">Pilih user approver</option>
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->id }}"
                                                        @selected(($stage['user_ids'][0] ?? $stage['users'][0] ?? null) == $user->id)>
                                                        {{ $user->name }} ({{ $user->username }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div @class(['col-lg-3', 'd-none' => ($stage['approver_type'] ?? 'user') !== 'role']) data-approver-picker="role">
                                            <label class="form-label">Role approver</label>
                                            <select class="form-select select2" data-field="roles" data-placeholder="Pilih role approver"
                                                @disabled(!($stage['is_approval'] ?? false) || ($stage['approver_type'] ?? 'user') !== 'role')>
                                                <option value="">Pilih role approver</option>
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role->id }}"
                                                        @selected(($stage['role_ids'][0] ?? $stage['roles'][0] ?? null) == $role->id)>
                                                        {{ $role->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div @class(['col-12', 'd-none' => !($stage['is_assignment'] ?? false)]) data-assignment-settings>
                                    <div class="row g-3">
                                        <div class="col-lg-3">
                                            <label class="form-label">Tipe assigner</label>
                                            <select class="form-select" data-field="assigner_type"
                                                @disabled(!($stage['is_assignment'] ?? false))>
                                                <option value="user" @selected(($stage['assigner_type'] ?? 'user') === 'user')>Berdasarkan user</option>
                                                <option value="role" @selected(($stage['assigner_type'] ?? 'user') === 'role')>Berdasarkan role</option>
                                            </select>
                                        </div>
                                        <div @class(['col-lg-3', 'd-none' => ($stage['assigner_type'] ?? 'user') !== 'user']) data-assigner-picker="user">
                                            <label class="form-label">User yang boleh assign</label>
                                            <select class="form-select select2" multiple data-field="assigner_users" data-placeholder="Pilih user assigner">
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->id }}"
                                                        @selected(in_array($user->id, $stage['assigner_user_ids'] ?? $stage['assigner_users'] ?? []))>
                                                        {{ $user->name }} ({{ $user->username }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div @class(['col-lg-3', 'd-none' => ($stage['assigner_type'] ?? 'user') !== 'role']) data-assigner-picker="role">
                                            <label class="form-label">Role yang boleh assign</label>
                                            <select class="form-select select2" multiple data-field="assigner_roles" data-placeholder="Pilih role assigner">
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role->id }}"
                                                        @selected(in_array($role->id, $stage['assigner_role_ids'] ?? $stage['assigner_roles'] ?? []))>
                                                        {{ $role->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="button" id="add-workflow-stage" class="btn btn-outline-primary mt-3">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Stage
                </button>
            </div>

            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2 me-1"></i>Simpan Workflow
                </button>
            </div>
        </form>
    </div>
@endsection

@push('addScript')
    <script>
        (() => {
            const list = document.getElementById('workflow-stages');
            const userOptions = @json($users->map(fn ($user) => ['id' => $user->id, 'label' => $user->name . ' (' . $user->username . ')'])->values());
            const roleOptions = @json($roles->map(fn ($role) => ['id' => $role->id, 'label' => $role->name])->values());

            const refreshSelect2 = (root) => {
                $(root).find('select.select2').each(function () {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                    $(this).select2({ theme: 'bootstrap-5', width: '100%' });
                });
            };

            const renumber = () => {
                Array.from(list.querySelectorAll('[data-stage]')).forEach((stage, index) => {
                    stage.querySelector('[data-stage-number]').textContent = index + 1;
                    const name = stage.querySelector('[data-field="name"]').value.trim();
                    stage.querySelector('[data-stage-heading]').textContent = name || `Stage ${index + 1}`;
                    stage.querySelectorAll('[data-field]').forEach((field) => {
                        const fieldName = field.dataset.field;
                        if (['users', 'roles', 'assigner_users', 'assigner_roles'].includes(fieldName)) {
                            field.name = `stages[${index}][${fieldName}][]`;
                        } else {
                            field.name = `stages[${index}][${fieldName}]`;
                        }
                    });
                    stage.querySelector('[data-field="is_assignment"]').id = `assignment-${index}`;
                    stage.querySelector('label[for^="assignment-"]').htmlFor = `assignment-${index}`;
                    stage.querySelector('[data-field="is_approval"]').id = `approval-${index}`;
                    stage.querySelector('label[for^="approval-"]').htmlFor = `approval-${index}`;
                    stage.querySelector('[data-field="name"]').oninput = () => {
                        stage.querySelector('[data-stage-heading]').textContent =
                            stage.querySelector('[data-field="name"]').value.trim() || `Stage ${index + 1}`;
                    };
                });
            };

            const syncApproverPicker = (stage) => {
                const selectedType = stage.querySelector('[data-field="approver_type"]').value;
                const enabled = stage.querySelector('[data-field="is_approval"]').checked;
                stage.querySelector('[data-approval-settings]').classList.toggle('d-none', !enabled);
                ['user', 'role'].forEach((type) => {
                    const picker = stage.querySelector(`[data-approver-picker="${type}"]`);
                    const select = picker.querySelector('select');
                    const selected = type === selectedType;
                    picker.classList.toggle('d-none', !selected);
                    select.disabled = !enabled || !selected;
                    $(select).trigger('change.select2');
                });
            };

            const syncAssignerPicker = (stage) => {
                const selectedType = stage.querySelector('[data-field="assigner_type"]').value;
                const enabled = stage.querySelector('[data-field="is_assignment"]').checked;
                stage.querySelector('[data-assignment-settings]').classList.toggle('d-none', !enabled);
                ['user', 'role'].forEach((type) => {
                    const picker = stage.querySelector(`[data-assigner-picker="${type}"]`);
                    const select = picker.querySelector('select');
                    const selected = type === selectedType;
                    picker.classList.toggle('d-none', !selected);
                    select.disabled = !enabled || !selected;
                    $(select).trigger('change.select2');
                });
            };

            const makeSelect = (options, fieldName) => {
                const select = document.createElement('select');
                select.className = 'form-select select2';
                select.multiple = fieldName === 'assigner_users' || fieldName === 'assigner_roles';
                select.dataset.field = fieldName;
                select.dataset.placeholder = 'Pilih ' + (fieldName.includes('users') ? 'user' : 'role');
                if (!select.multiple) {
                    select.add(new Option('Pilih ' + (fieldName === 'users' ? 'user approver' : 'role approver'), ''));
                }
                options.forEach(({ id, label }) => select.add(new Option(label, id)));
                return select;
            };

            const addStage = () => {
                const stage = document.createElement('div');
                stage.className = 'workflow-stage border rounded p-3';
                stage.dataset.stage = '';
                stage.innerHTML = `
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary-subtle text-primary" data-stage-number></span>
                            <strong data-stage-heading>Stage baru</strong>
                        </div>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-move="up" aria-label="Pindah ke atas"><i class="bi bi-arrow-up"></i></button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-move="down" aria-label="Pindah ke bawah"><i class="bi bi-arrow-down"></i></button>
                            <button type="button" class="btn btn-outline-danger btn-sm" data-remove-stage><i class="bi bi-trash me-1"></i>Hapus</button>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <label class="form-label">Nama Stage</label>
                            <input type="text" class="form-control" maxlength="255" required data-field="name">
                        </div>
                        <div class="col-lg-2">
                            <div class="form-check mt-lg-4 pt-lg-2">
                                <input class="form-check-input" type="checkbox" value="1" data-field="is_assignment">
                                <label class="form-check-label">Stage perlu penugasan</label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" value="1" data-field="is_approval">
                                <label class="form-check-label">Hasil perlu approval</label>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-12 d-none" data-approval-settings>
                            <div class="row g-3">
                                <div class="col-lg-3">
                                    <label class="form-label">Tipe approver</label>
                                    <select class="form-select" data-field="approver_type">
                                        <option value="user" selected>Berdasarkan user</option>
                                        <option value="role">Berdasarkan role</option>
                                    </select>
                                </div>
                                <div class="col-lg-3" data-approver-picker="user"><label class="form-label">User approver</label><div data-select="users"></div></div>
                                <div class="col-lg-3" data-approver-picker="role"><label class="form-label">Role approver</label><div data-select="roles"></div></div>
                            </div>
                        </div>
                        <div class="col-12 d-none" data-assignment-settings>
                            <div class="row g-3">
                                <div class="col-lg-3">
                                    <label class="form-label">Tipe assigner</label>
                                    <select class="form-select" data-field="assigner_type">
                                        <option value="user" selected>Berdasarkan user</option>
                                        <option value="role">Berdasarkan role</option>
                                    </select>
                                </div>
                                <div class="col-lg-3" data-assigner-picker="user"><label class="form-label">User yang boleh assign</label><div data-select="assigner_users"></div></div>
                                <div class="col-lg-3" data-assigner-picker="role"><label class="form-label">Role yang boleh assign</label><div data-select="assigner_roles"></div></div>
                            </div>
                        </div>
                    </div>`;
                stage.insertAdjacentHTML('afterbegin', '<input type="hidden" data-field="id" value="">');
                ['users', 'roles', 'assigner_users', 'assigner_roles'].forEach((fieldName) => {
                    const options = fieldName.includes('users') ? userOptions : roleOptions;
                    stage.querySelector(`[data-select="${fieldName}"]`).replaceWith(makeSelect(options, fieldName));
                });
                list.append(stage);
                refreshSelect2(stage);
                renumber();
                syncApproverPicker(stage);
                syncAssignerPicker(stage);
                stage.querySelector('[data-field="name"]').focus();
            };

            list.addEventListener('click', (event) => {
                const remove = event.target.closest('[data-remove-stage]');
                if (remove) {
                    remove.closest('[data-stage]').remove();
                    renumber();
                    return;
                }

                const move = event.target.closest('[data-move]');
                if (move) {
                    const stage = move.closest('[data-stage]');
                    if (move.dataset.move === 'up' && stage.previousElementSibling) {
                        list.insertBefore(stage, stage.previousElementSibling);
                    } else if (move.dataset.move === 'down' && stage.nextElementSibling) {
                        list.insertBefore(stage.nextElementSibling, stage);
                    }
                    renumber();
                }
            });

            list.addEventListener('input', (event) => {
                if (event.target.matches('[data-field="name"]')) {
                    renumber();
                }
            });

            list.addEventListener('change', (event) => {
                if (event.target.matches('[data-field="approver_type"]')) {
                    syncApproverPicker(event.target.closest('[data-stage]'));
                }
                if (event.target.matches('[data-field="assigner_type"]')) {
                    syncAssignerPicker(event.target.closest('[data-stage]'));
                }
                if (event.target.matches('[data-field="is_approval"]')) {
                    syncApproverPicker(event.target.closest('[data-stage]'));
                }
                if (event.target.matches('[data-field="is_assignment"]')) {
                    syncAssignerPicker(event.target.closest('[data-stage]'));
                }
            });

            document.getElementById('add-workflow-stage').addEventListener('click', addStage);
            refreshSelect2(list);
            renumber();
            list.querySelectorAll('[data-stage]').forEach((stage) => {
                syncApproverPicker(stage);
                syncAssignerPicker(stage);
            });
        })();
    </script>
@endpush

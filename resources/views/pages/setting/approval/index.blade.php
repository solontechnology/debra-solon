@extends('layouts.admin')

@section('title', 'Konfigurasi Approval')

@section('content')
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title mb-1">Konfigurasi Approval</h3>
                <div class="text-secondary small">
                    Approver dapat dipilih berdasarkan user atau role. Jika beberapa dipilih, salah satu approver yang berwenang dapat memberikan keputusan.
                </div>
            </div>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
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

            <div class="d-flex flex-column gap-4">
                @foreach ($configurations as $workflowKey => $configuration)
                    <form action="{{ route('setting.approval.update', $workflowKey) }}" method="POST"
                        class="approval-configuration border rounded p-3" data-approval-configuration>
                        @csrf
                        @method('PUT')
                        <h4 class="h5 mb-3">{{ $configuration['label'] }}</h4>
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-3">
                                <label class="form-label">Approver berdasarkan</label>
                                <select name="approver_type" class="form-select" data-approver-type>
                                    <option value="user" @selected($configuration['approver_type'] === 'user')>User</option>
                                    <option value="role" @selected($configuration['approver_type'] === 'role')>Role</option>
                                </select>
                            </div>
                            <div class="col-lg-6" data-approver-users>
                                <label class="form-label">User approver</label>
                                <select name="users[]" class="form-select select2" multiple
                                    data-placeholder="Pilih satu atau beberapa user" @disabled($configuration['approver_type'] !== 'user')>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" @selected(in_array($user->id, $configuration['user_ids']))>
                                            {{ $user->name }} ({{ $user->username }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-6" data-approver-roles>
                                <label class="form-label">Role approver</label>
                                <select name="roles[]" class="form-select select2" multiple
                                    data-placeholder="Pilih satu atau beberapa role" @disabled($configuration['approver_type'] !== 'role')>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}" @selected(in_array($role->id, $configuration['role_ids']))>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3">
                                <button type="submit" class="btn btn-primary">Simpan Approver</button>
                            </div>
                        </div>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@push('addScript')
    <script>
        (() => {
            document.querySelectorAll('[data-approval-configuration]').forEach((form) => {
                const type = form.querySelector('[data-approver-type]');
                const userPicker = form.querySelector('[data-approver-users]');
                const rolePicker = form.querySelector('[data-approver-roles]');
                const userSelect = userPicker.querySelector('select');
                const roleSelect = rolePicker.querySelector('select');

                const updatePicker = () => {
                    const isUser = type.value === 'user';
                    userPicker.classList.toggle('d-none', !isUser);
                    rolePicker.classList.toggle('d-none', isUser);
                    userSelect.disabled = !isUser;
                    roleSelect.disabled = isUser;
                };

                type.addEventListener('change', updatePicker);
                updatePicker();
            });

            $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });
        })();
    </script>
@endpush

@extends('layouts.admin')

@section('title')
    Master Employee
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between gap-2">
            @can('hris/employee/create')
                <a href="{{ route('hris.employee.create') }}" class="btn btn-primary">
                    <i class="bi bi-person-plus me-1"></i> Tambah Employee
                </a>
            @endcan
            <form action="{{ route('hris.employee.index') }}" method="GET" class="d-flex gap-2">
                <input type="search" name="q" class="form-control" placeholder="Cari employee atau login"
                    value="{{ request('q') }}">
                <button class="btn btn-outline-primary" type="submit" aria-label="Cari">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Data Diri</th>
                        <th>Kontak</th>
                        <th>Jabatan / Divisi</th>
                        <th>Login</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $employee->user?->name ?? 'Akun login belum terhubung' }}</div>
                                <div class="small text-muted">NIK: {{ $employee->nik }}</div>
                                <div class="small text-muted">
                                    {{ $employee->gender === 'male' ? 'Laki-laki' : 'Perempuan' }}
                                    @if ($employee->date_of_birth)
                                        · {{ $employee->date_of_birth->format('d/m/Y') }}
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>{{ $employee->user?->email ?? '-' }}</div>
                                <div class="small text-muted">{{ $employee->user?->phone ?? '-' }}</div>
                            </td>
                            <td>
                                <div>{{ $employee->position ?: '-' }}</div>
                                <div class="small text-muted">{{ $employee->division ?: '-' }}</div>
                            </td>
                            <td>{{ $employee->user?->username ?? 'Belum ada' }}</td>
                            <td>{{ $employee->user?->roles->pluck('name')->join(', ') ?: '-' }}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    @can('hris/employee/edit')
                                        <a href="{{ route('hris.employee.edit', $employee) }}"
                                            class="btn btn-outline-primary btn-sm" aria-label="Edit employee">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endcan
                                    @can('hris/employee/delete')
                                        <form action="{{ route('hris.employee.destroy', $employee) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm confirm_delete"
                                                data-message="{{ $employee->user?->name ?? $employee->nik }}"
                                                aria-label="Hapus employee">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada data employee.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $employees->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection

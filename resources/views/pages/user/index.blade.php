@extends('layouts.admin')

@section('title')
    User
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <a href="{{ route('akses.user.create') }}" class="btn btn-primary">Tambah User</a>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>
                                Nama
                            </th>
                            <th>
                                Username
                            </th>
                            <th>
                                Email
                            </th>
                            <th>
                                Role
                            </th>
                            <th>
                                Employee
                            </th>
                            <th>
                                Aksi
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($user as $item)
                            <tr>
                                <td>
                                    {{ $item->name }}
                                </td>
                                <td>
                                    {{ $item->username }}
                                    {{-- {{ dd($item) }} --}}
                                </td>
                                <td>
                                    {{ $item->email }}
                                </td>
                                <td>
                                    {{ $item->roles->pluck('name')->join(', ') ?: '-' }}
                                </td>
                                <td>
                                    @if ($item->employee)
                                        @can('hris/employee/edit')
                                            <a href="{{ route('hris.employee.edit', $item->employee) }}" class="btn btn-outline-primary btn-sm">
                                                Lihat data employee
                                            </a>
                                        @else
                                            <span class="text-muted">Terhubung</span>
                                        @endcan
                                    @else
                                        <span class="text-muted">Akun mandiri</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex">
                                        <a href="{{ route('akses.user.edit', $item->id) }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-pen"></i>
                                        </a>
                                        <form action="{{ route('akses.user.destroy', $item->id) }}" method="post">
                                            @csrf
                                            @method('DELETE')
                                            <span class="btn btn-danger confirm_delete ms-2 btn-sm">
                                                <i class="bi bi-trash3"></i>
                                            </span>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

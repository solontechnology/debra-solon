@extends('layouts.admin')

@section('title')
    Tambah Data Role Permission
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('akses.role.store') }}" method="post">
                @csrf

                <div class="mb-3">
                    <label for="role-name" class="form-label">Nama Role</label>
                    <input type="text" class="form-control" id="role-name" required name="name"
                        value="{{ old('name') }}" placeholder="Nama role">
                </div>

                @include('pages.role-permission._permission-matrix')

                <button type="submit" class="mt-3 btn btn-primary">Simpan</button>
            </form>
        </div>
    </div>
@endsection

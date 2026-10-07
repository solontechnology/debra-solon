@extends('layouts.admin')

@section('title')
    Edit Role {{ $itemRol->name }}
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('akses.role.update', $itemRol->id) }}" method="post">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="role-name" class="form-label">Nama Role</label>
                    <input type="text" class="form-control" id="role-name" required name="name"
                        value="{{ old('name', $itemRol->name) }}">
                </div>

                @include('pages.role-permission._permission-matrix')

                <button type="submit" class="mt-3 btn btn-primary">Simpan Perubahan</button>
            </form>
        </div>
    </div>
@endsection

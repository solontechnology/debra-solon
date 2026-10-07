@extends('layouts.admin')

@section('title')
    Tambah Employee
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('hris.employee.store') }}" method="POST">
                @csrf
                @include('pages.hris.employee._form')
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">Simpan Employee dan Login</button>
                    <a href="{{ route('hris.employee.index') }}" class="btn btn-outline-secondary">Kembali</a>
                </div>
            </form>
        </div>
    </div>
@endsection

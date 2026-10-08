@extends('layouts.admin')

@section('title')
    Edit Employee
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('hris.employee.update', $employee) }}" method="POST">
                @csrf
                @method('PUT')
                @include('pages.hris.employee._form')
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="{{ route('hris.employee.index') }}" class="btn btn-outline-secondary">Kembali</a>
                </div>
            </form>
        </div>
    </div>
@endsection

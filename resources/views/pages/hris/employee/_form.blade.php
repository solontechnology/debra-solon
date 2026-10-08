@php
    $employeeRecord = $employee ?? null;
    $loginUser = $employeeRecord?->user;
    $currentDivision = old('division', $employeeRecord?->division ?? '');
@endphp

<h3 class="mb-3">Data Diri Karyawan</h3>
<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label required">Nama Lengkap</label>
        <input id="name" name="name" type="text" required
            class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $loginUser?->name) }}">
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="nik" class="form-label required">NIK</label>
        <input id="nik" name="nik" type="text" required
            class="form-control @error('nik') is-invalid @enderror"
            value="{{ old('nik', $employeeRecord?->nik ?? '') }}">
        @error('nik') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="gender" class="form-label required">Jenis Kelamin</label>
        <select id="gender" name="gender" required class="form-select @error('gender') is-invalid @enderror">
            <option value="">Pilih jenis kelamin</option>
            <option value="male" @selected(old('gender', $employeeRecord?->gender ?? '') === 'male')>Laki-laki</option>
            <option value="female" @selected(old('gender', $employeeRecord?->gender ?? '') === 'female')>Perempuan</option>
        </select>
        @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="date_of_birth" class="form-label">Tanggal Lahir</label>
        <input id="date_of_birth" name="date_of_birth" type="date"
            class="form-control @error('date_of_birth') is-invalid @enderror"
            value="{{ old('date_of_birth', $employeeRecord?->date_of_birth?->format('Y-m-d') ?? '') }}">
        @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="position" class="form-label">Jabatan</label>
        <input id="position" name="position" type="text"
            class="form-control @error('position') is-invalid @enderror"
            value="{{ old('position', $employeeRecord?->position ?? '') }}">
        @error('position') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="division" class="form-label">Divisi</label>
        <select id="division" name="division" class="form-select @error('division') is-invalid @enderror">
            <option value="">Pilih divisi</option>
            @if ($currentDivision && !$divisions->contains($currentDivision))
                <option value="{{ $currentDivision }}" selected>{{ $currentDivision }}</option>
            @endif
            @foreach ($divisions as $division)
                <option value="{{ $division }}" @selected($currentDivision === $division)>{{ $division }}</option>
            @endforeach
        </select>
        @error('division') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label for="address" class="form-label">Alamat</label>
        <textarea id="address" name="address" rows="3"
            class="form-control @error('address') is-invalid @enderror">{{ old('address', $employeeRecord?->address ?? '') }}</textarea>
        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<hr class="my-4">
<h3 class="mb-3">Informasi Login</h3>
<div class="row g-3">
    <div class="col-md-6">
        <label for="username" class="form-label required">Username</label>
        <input id="username" name="username" type="text" required
            class="form-control @error('username') is-invalid @enderror"
            value="{{ old('username', $loginUser?->username) }}">
        @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="email" class="form-label required">Email Login</label>
        <input id="email" name="email" type="email" required
            class="form-control @error('email') is-invalid @enderror"
            value="{{ old('email', $loginUser?->email) }}">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="phone" class="form-label">Nomor Telepon</label>
        <input id="phone" name="phone" type="text"
            class="form-control @error('phone') is-invalid @enderror"
            value="{{ old('phone', $loginUser?->phone) }}">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="password" class="form-label {{ $loginUser ? '' : 'required' }}">
            {{ $loginUser ? 'Password Baru (opsional)' : 'Password' }}
        </label>
        <input id="password" name="password" type="password" autocomplete="new-password"
            {{ $loginUser ? '' : 'required' }}
            class="form-control @error('password') is-invalid @enderror">
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        @if ($loginUser)
            <div class="form-text">Kosongkan jika password tidak ingin diubah.</div>
        @else
            <div class="form-text">Minimal 8 karakter.</div>
        @endif
    </div>
    <div class="col-12">
        <label for="role" class="form-label required">Role Login</label>
        <select id="role" name="role[]" multiple required
            class="form-select select2 @error('role') is-invalid @enderror"
            data-placeholder="Pilih role">
            @foreach ($roles as $role)
                <option value="{{ $role->id }}"
                    @selected(in_array($role->id, old('role', $userRole ?? [])))>
                    {{ $role->name }}
                </option>
            @endforeach
        </select>
        @error('role') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
</div>

@push('addScript')
    <script>
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    </script>
@endpush

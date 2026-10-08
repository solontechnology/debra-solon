@php
@endphp

<div>
    @if ($approvalPending)
        <div class="d-flex flex-column gap-2">
            <span class="badge bg-warning-subtle text-warning-emphasis">
                {{ $approvalPending->status }}: Menunggu approval
            </span>
            <small class="text-secondary">
                Ditugaskan kepada: {{ $approvalPending->user?->name ?? 'Belum ada petugas' }}
            </small>
            @if ($canDecideApproval)
                <form action="{{ route('job.akta.data.approval', $approvalPending) }}" method="post">
                    @csrf
                    <textarea name="comment" class="form-control form-control-sm mb-2"
                        placeholder="Catatan approval (opsional)"></textarea>
                    <div class="d-flex gap-1">
                        <button type="submit" name="decision" value="approve" class="btn btn-success btn-sm">
                            Approve
                        </button>
                        <button type="submit" name="decision" value="reject" class="btn btn-danger btn-sm">
                            Tolak
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @elseif ($currentStatus !== 'Selesai' && $nextStep)
        @if ($canAssignStage || $canSubmitStage)
            @if ($currentStatus !== '')
                <button type="button" class="btn btn-primary btn-md"
                    data-bs-toggle="modal" data-bs-target="#modalEditAkta{{ $key }}">
                    @if ($workflowAction === 'assign')
                        Tugaskan {{ $nextStatus }}
                    @elseif ($isApproval)
                        Ajukan hasil {{ $nextStatus }}
                    @else
                        Proses {{ $nextStatus }}
                    @endif
                </button>
            @endif

            <form action="{{ route('job.akta.data.store') }}" method="post" class="form_step_akad"
                id="formData{{ $key }}">
                @csrf

                <input type="text" value="{{ $formOrder->id }}" name="form_id" hidden>
                <input type="hidden" name="kategori" value="{{ $tipe }}">
                <input type="hidden" name="workflow_action" value="{{ $workflowAction }}">

                <div class="modal fade" id="modalEditAkta{{ $key }}" tabindex="-1"
                    aria-labelledby="modalEditAkta{{ $key }}Label" aria-hidden="true">

                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
                        <div class="modal-content">

                            <div class="modal-header">
                                <div>
                                    <h1 class="modal-title fs-5" id="modalEditAkta{{ $key }}Label">
                                        Status Proses {{ $formOrder->nama }}
                                    </h1>
                                    <div class="text-secondary">
                                        Parent {{ $jobDivisi->kode }}
                                    </div>
                                </div>

                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="row justify-content-center g-3">
                                    <div class="col-lg-4 d-lg-block d-none">
                                        <ul class="steps steps-counter steps-vertical">
                                            @foreach ($steps as $step)
                                                <li
                                                    class="step-item {{ $nextStatus === $step ? 'active' : '' }} {{ $currentStatus === 'Belum diproses' ? 'active' : '' }}">
                                                    {{ $step }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    <div class="col-lg-8">
                                        <div class="mb-3">
                                            <label class="form-label required">Jenis Akad</label>
                                            <input type="text" class="form-control" disabled
                                                value="{{ $jobDivisi->jenisAkad->nama }}">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">{{ $isAssignedWork ? 'Stage yang dikerjakan' : 'Status Sebelumnya' }}</label>
                                            <input type="text" class="form-control" disabled
                                                value="{{ $isAssignedWork ? $nextStatus : $currentStatus }}">
                                        </div>

                                        @if (!$isAssignedWork)
                                            <div class="mb-3">
                                            <label class="form-label">Status Berikutnya</label>
                                            <input type="text" class="form-control" disabled
                                                value="{{ $nextStatus }}">
                                        </div>
                                        @endif

                                        @if ($workflowAction === 'assign')
                                            <div class="mb-3">
                                                <label for="assigned-staff-{{ $key }}" class="form-label required">
                                                    Petugas yang ditugaskan
                                                </label>
                                                <select name="staff" class="form-select select2_ops"
                                                    id="assigned-staff-{{ $key }}" required>
                                                    <option value="">Pilih petugas</option>
                                                    @foreach ($users as $user)
                                                        <option value="{{ $user['value'] }}">
                                                            {{ $user['label'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @elseif ($isAssignedWork)
                                            <div class="alert alert-info">
                                                Petugas stage ini: {{ $activeStage->user?->name ?? 'Belum ditentukan' }}.
                                                Setelah pekerjaan selesai, ajukan hasilnya
                                                {{ $isApproval ? 'untuk diperiksa approver.' : 'untuk menutup stage.' }}
                                            </div>
                                        @endif

                                        <div>
                                            <label class="form-label">Keterangan / hasil pekerjaan</label>
                                            <textarea name="keterangan" class="form-control">{{ $activeStage?->keterangan ?? '' }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    Tutup
                                </button>

                                <button type="submit" name="tipe" value="next_step"
                                    class="btn btn-primary btn__submit_data{{ $key }}">
                                    @if ($workflowAction === 'assign')
                                        Tetapkan Petugas
                                    @elseif ($isApproval)
                                        Ajukan Hasil untuk Approval
                                    @elseif ($isAssignedWork)
                                        Selesaikan Stage
                                    @else
                                        Selesaikan {{ $nextStatus }}
                                    @endif
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

            </form>
        @endif
    @endif
</div>
<div class="accordion" id="accordion-default">
    @foreach ($jobFormOrder as $kategori => $item)
        <div class="accordion-item">
            <div class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapse-{{ $kategori }}" aria-expanded="false">
                    @php
                        $categoryProgress = $progressByCategory[$kategori];
                        $categoryPercentage = $categoryProgress['percentage'];
                        $categoryProgressColor = match (true) {
                            $categoryPercentage === 100 => '#2fb344',
                            $categoryPercentage >= 50 => '#066fd1',
                            $categoryPercentage > 0 => '#f59f00',
                            default => '#6c757d',
                        };
                    @endphp
                    <span class="d-flex flex-column flex-grow-1 gap-2 me-3">
                        <span>{{ strtoupper(str_replace('_', ' ', $kategori)) }}</span>
                        <span class="d-flex align-items-center gap-2">
                            <span class="progress flex-grow-1" role="progressbar"
                                aria-label="Progress kategori {{ strtoupper(str_replace('_', ' ', $kategori)) }}"
                                aria-valuenow="{{ $categoryPercentage }}" aria-valuemin="0" aria-valuemax="100"
                                style="height: 8px; background-color: #e5e7eb;">
                                <span style="display: block; width: {{ $categoryPercentage }}%; height: 100%; background-color: {{ $categoryProgressColor }}; border-radius: inherit;"></span>
                            </span>
                            <small class="text-muted text-nowrap">
                                {{ $categoryProgress['completed'] }}/{{ $categoryProgress['total'] }}
                                ({{ $categoryPercentage }}%)
                            </small>
                        </span>
                    </span>
                    <div class="accordion-button-toggle">
                        <!-- Download SVG icon from http://tabler.io/icons/icon/chevron-down -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="icon icon-1">
                            <path d="M6 9l6 6l6 -6"></path>
                        </svg>
                    </div>
                </button>
            </div>
            <div id="collapse-{{ $kategori }}" class="accordion-collapse collapse"
                data-bs-parent="#accordion-default">
                <div class="accordion-body">
                    @php
                        $riwayatNotEmpty = 0;
                    @endphp
                    @foreach ($item as $child)
                        @php
                            $processProgress = $progressByCategory[$kategori]['processes'][$child->id];
                            $processPercentage = $processProgress['percentage'];
                            $processProgressColor = match (true) {
                                $processPercentage === 100 => '#2fb344',
                                $processPercentage >= 50 => '#066fd1',
                                $processPercentage > 0 => '#f59f00',
                                default => '#6c757d',
                            };
                        @endphp
                        <div class="accordion accordion-inverted accordion-plus" id="accordion-inverted-plus">

                            <div class="accordion-item">
                                <div class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapse-{{ $child->id }}-inverted-plus"
                                        aria-expanded="false">
                                        <span class="d-flex flex-column flex-grow-1 gap-2 me-3">
                                            <span>{{ $child->nama }}</span>
                                            <span class="d-flex align-items-center gap-2">
                                                <span class="progress flex-grow-1" role="progressbar"
                                                    aria-label="Progress proses {{ $child->nama }}"
                                                    aria-valuenow="{{ $processPercentage }}" aria-valuemin="0"
                                                    aria-valuemax="100" style="height: 8px; background-color: #e5e7eb;">
                                                    <span style="display: block; width: {{ $processPercentage }}%; height: 100%; background-color: {{ $processProgressColor }}; border-radius: inherit;"></span>
                                                </span>
                                                <small class="text-muted text-nowrap">
                                                    {{ $processProgress['completed'] }}/{{ $processProgress['total'] }}
                                                    stage ({{ $processPercentage }}%)
                                                </small>
                                            </span>
                                        </span>
                                        <div class="accordion-button-toggle accordion-button-toggle-plus">
                                            <!-- Download SVG icon from http://tabler.io/icons/icon/plus -->
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                class="icon icon-1">
                                                <path d="M12 5l0 14"></path>
                                                <path d="M5 12l14 0"></path>
                                            </svg>
                                        </div>
                                    </button>
                                </div>
                                <div id="collapse-{{ $child->id }}-inverted-plus" class="accordion-collapse collapse"
                                    data-bs-parent="#accordion-inverted-plus">
                                    <div class="accordion-body">
                                        @forelse ($child->statusJobOps->sortByDesc("id") as $number => $statusPengerjaan)
                                            <div class="d-flex gap-3 mb-3">
                                                <div class="">

                                                    <span
                                                        class="badge {{ $statusPengerjaan->status_penolakan ? 'bg-danger text-white' : 'bg-primary text-blue-fg' }}  badge-pill">
                                                        {{ $number + 1 }}
                                                    </span>
                                                </div>
                                                <div
                                                    class="{{ $statusPengerjaan->status_penolakan ? 'text-danger' : '' }}">
                                                    <div
                                                        class="fw-bold {{ $statusPengerjaan->status_penolakan ? 'text-danger' : 'text-dark' }}">
                                                        {{ $statusPengerjaan->status_penolakan ? "$statusPengerjaan->status_penolakan Perlu Perbaikan" : $statusPengerjaan->status }}
                                                    </div>
                                                    <div class="text-secondary mb-2">
                                                        {{ $statusPengerjaan->keterangan }}
                                                    </div>
                                                    <div class="text-secondary mb-2">
                                                        <i class="bi bi-person-circle"></i> Diproses oleh :
                                                        {{ $statusPengerjaan->createdBy->name ?? 'Otomatis' }}
                                                    </div>
                                                    @if ($statusPengerjaan->user)
                                                        <div class="text-secondary mb-2">
                                                            <i class="bi bi-person-check"></i> Penugasan :
                                                            {{ $statusPengerjaan->user->name }}
                                                        </div>
                                                    @endif
                                                    <div class="text-secondary mb-2">
                                                        <i class="bi bi-stopwatch"></i> Waktu :
                                                        {{ $statusPengerjaan->created_at->format('d M Y H:i') }}
                                                    </div>
                                                </div>
                                            </div>
                                            @php
                                                $riwayatNotEmpty += 1;
                                            @endphp
                                        @empty
                                            <div class="">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="icon icon-tabler icons-tabler-outline icon-tabler-circle-dashed-number-0">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M8.56 3.69a9 9 0 0 0 -2.92 1.95" />
                                                    <path d="M3.69 8.56a9 9 0 0 0 -.69 3.44" />
                                                    <path d="M3.69 15.44a9 9 0 0 0 1.95 2.92" />
                                                    <path d="M8.56 20.31a9 9 0 0 0 3.44 .69" />
                                                    <path d="M15.44 20.31a9 9 0 0 0 2.92 -1.95" />
                                                    <path d="M20.31 15.44a9 9 0 0 0 .69 -3.44" />
                                                    <path d="M20.31 8.56a9 9 0 0 0 -1.95 -2.92" />
                                                    <path d="M15.44 3.69a9 9 0 0 0 -3.44 -.69" />
                                                    <path d="M10 10v4a2 2 0 1 0 4 0v-4a2 2 0 1 0 -4 0" />
                                                </svg>
                                                Tidak Ada Riwayat
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>
        </div>
    @endforeach

</div>

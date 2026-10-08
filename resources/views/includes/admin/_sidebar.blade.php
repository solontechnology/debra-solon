<aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu"
            aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <h1 class="navbar-brand navbar-brand-autodark">
            <a href="">
                <img src="{{ asset('logo_white.png') }}" alt="KUSUMA POS" class="navbar-brand-image">
            </a>
            {{-- EMRSAMPURNA --}}
        </h1>
        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                @php($tenantMenus = app(\App\Services\Tenancy\TenantFeatureService::class))
                @if ($tenantMenus->isEnabled('menu_dashboard'))
                <li class="nav-item {{ request()->is('dashboard') ? 'active' : '' }}">
                    <a class="nav-link" href="/dashboard">
                        <span
                            class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler-icons.io/i/home -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M5 12l-2 0l9 -9l9 9l-2 0" />
                                <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
                                <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />
                            </svg>
                        </span>
                        <span class="nav-link-title">
                            Dashboard
                        </span>
                    </a>
                </li>
                @endif
                @if ($tenantMenus->anyEnabled(['menu_master_wilayah_provinsi', 'menu_master_wilayah_kota', 'menu_master_wilayah_kecamatan', 'menu_master_wilayah_desa', 'menu_master_bank', 'menu_master_developer', 'menu_master_broker', 'menu_master_notaris_rekanan', 'menu_master_pekerjaan', 'menu_master_paket_pekerjaan']))
                @can('master-data/list')
                    <li class="nav-item dropdown {{ request()->is('master-data*') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle" href="#navbar-help" data-bs-toggle="dropdown"
                            data-bs-auto-close="false" role="button" aria-expanded="false">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-database-star"
                                    width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M4 6c0 1.657 3.582 3 8 3s8 -1.343 8 -3s-3.582 -3 -8 -3s-8 1.343 -8 3">
                                    </path>
                                    <path d="M4 6v6c0 1.43 2.67 2.627 6.243 2.927"></path>
                                    <path d="M20 10.5v-4.5"></path>
                                    <path d="M4 12v6c0 1.546 3.12 2.82 7.128 2.982"></path>
                                    <path
                                        d="M17.8 20.817l-2.172 1.138a.392 .392 0 0 1 -.568 -.41l.415 -2.411l-1.757 -1.707a.389 .389 0 0 1 .217 -.665l2.428 -.352l1.086 -2.193a.392 .392 0 0 1 .702 0l1.086 2.193l2.428 .352a.39 .39 0 0 1 .217 .665l-1.757 1.707l.414 2.41a.39 .39 0 0 1 -.567 .411l-2.172 -1.138z">
                                    </path>
                                </svg>
                            </span>
                            <span class="nav-link-title">
                                Master Data
                            </span>
                        </a>
                        <div class="dropdown-menu {{ request()->is('master-data*') ? 'show' : '' }}">
                            {{-- Lokasi --}}
                            <div class="dropend ">
                                @if ($tenantMenus->anyEnabled(['menu_master_wilayah_provinsi', 'menu_master_wilayah_kota', 'menu_master_wilayah_kecamatan', 'menu_master_wilayah_desa']))
                                <a class="dropdown-item dropdown-toggle" href="#sidebar-authentication"
                                    data-bs-toggle="dropdown" data-bs-auto-close="true" role="button"
                                    aria-expanded="false">
                                    Wilayah
                                </a>
                                <div class="dropdown-menu {{ request()->is('master-data/lokasi*') ? 'show' : '' }}">
                                    @if ($tenantMenus->isEnabled('menu_master_wilayah_provinsi'))
                                    <a href="{{ route('master-data.provinsi.index') }}"
                                        class="dropdown-item {{ request()->is('master-data/lokasi/provinsi*') ? 'active' : '' }}">
                                        Provinsi
                                    </a>
                                    @endif
                                    @if ($tenantMenus->isEnabled('menu_master_wilayah_kota'))
                                    <a href="{{ route('master-data.kota.index') }}"
                                        class="dropdown-item {{ request()->is('master-data/lokasi/kota*') ? 'active' : '' }}">
                                        Kota
                                    </a>
                                    @endif
                                    @if ($tenantMenus->isEnabled('menu_master_wilayah_kecamatan'))
                                    <a href="{{ route('master-data.kecamatan.index') }}"
                                        class="dropdown-item {{ request()->is('master-data/lokasi/kecamatan*') ? 'active' : '' }}">
                                        Kecamatan
                                    </a>
                                    @endif
                                    @if ($tenantMenus->isEnabled('menu_master_wilayah_desa'))
                                    <a href="{{ route('master-data.desa.index') }}"
                                        class="dropdown-item {{ request()->is('master-data/lokasi/desa*') ? 'active' : '' }}">
                                        Desa
                                    </a>
                                    @endif
                                </div>
                                @endif
                            </div>
                            {{-- Lokasi end --}}
                            {{-- <a href="{{ route('master-data.divisi.index') }}"
                                class="dropdown-item {{ request()->is('master-data/divisi*') ? 'active' : '' }}">
                                Divisi
                            </a> --}}
                            @if ($tenantMenus->isEnabled('menu_master_bank'))
                            <a href="{{ route('master-data.bank.index') }}"
                                class="dropdown-item {{ request()->is('master-data/bank*') ? 'active' : '' }}">
                                Bank
                            </a>
                            @endif
                            @if ($tenantMenus->isEnabled('menu_master_developer'))
                            <a href="{{ route('master-data.developer.index') }}"
                                class="dropdown-item {{ request()->is('master-data/developer*') ? 'active' : '' }}">
                                Developer
                            </a>
                            @endif
                            @if ($tenantMenus->isEnabled('menu_master_broker'))
                            <a href="{{ route('master-data.broker.index') }}"
                                class="dropdown-item {{ request()->is('master-data/broker*') ? 'active' : '' }}">
                                Broker
                            </a>
                            @endif
                            @if ($tenantMenus->isEnabled('menu_master_notaris_rekanan'))
                            <a href="{{ route('master-data.notaris-rekanan.index') }}"
                                class="dropdown-item {{ request()->is('master-data/notaris-rekanan*') ? 'active' : '' }}">
                                Notaris Rekanan
                            </a>
                            @endif
                            @if ($tenantMenus->isEnabled('menu_master_pekerjaan'))
                            <a href="{{ route('master-data.pekerjaan.index') }}"
                                class="dropdown-item {{ request()->is('master-data/pekerjaan*') ? 'active' : '' }}">
                                Pekerjaan
                            </a>
                            @endif
                            @if ($tenantMenus->isEnabled('menu_master_paket_pekerjaan'))
                            <a href="{{ route('master-data.form-order.index') }}"
                                class="dropdown-item {{ request()->is('master-data/form-order*') ? 'active' : '' }}">
                                Paket Pekerjaan
                            </a>
                            @endif
                        </div>
                    </li>
                @endcan
                @endif
                @if ($tenantMenus->anyEnabled(['menu_job_divisi', 'menu_job_operasional', 'menu_job_notaris', 'menu_job_ppat', 'menu_job_waarmerking', 'menu_job_covernot', 'menu_job_surat_keluar', 'menu_job_legalisasi', 'menu_job_wasiat', 'menu_job_pajak', 'menu_job_pnbp', 'menu_job_penambahan_item', 'menu_job_pembatalan_item']))
                <li class="nav-item dropdown {{ request()->is('job*') ? 'active' : '' }}">
                    <a class="nav-link dropdown-toggle" href="#navbar-help" data-bs-toggle="dropdown"
                        data-bs-auto-close="false" role="button" aria-expanded="false">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-briefcase">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path
                                    d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                                <path d="M8 7v-2a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v2" />
                                <path d="M12 12l0 .01" />
                                <path d="M3 13a20 20 0 0 0 18 0" />
                            </svg>
                        </span>
                        <span class="nav-link-title">
                            Job
                        </span>
                    </a>
                    <div class="dropdown-menu {{ request()->is('job*') ? 'show' : '' }}">
                        @can('job/divisi/list')
                            @if ($tenantMenus->isEnabled('menu_job_divisi'))
                            <a href="{{ route('job.divisi.index') }}"
                                class="dropdown-item {{ request()->is('job/divisi*') ? 'active' : '' }}">
                                Divisi
                            </a>
                            @endif
                        @endcan
                        @can('job/ops/list')
                            @if ($tenantMenus->isEnabled('menu_job_operasional'))
                            <a href="{{ route('job.ops.data.index') }}"
                                class="dropdown-item {{ request()->is('job/ops/data*') ? 'active' : '' }}">
                                Operasional
                            </a>
                            @endif
                        @endcan
                        @can('job/notaris/list')
                            @if ($tenantMenus->isEnabled('menu_job_notaris'))
                            <a href="{{ route('job.akta.data.index') }}"
                                class="dropdown-item {{ request()->is('job/akta/data*') ? 'active' : '' }}">
                                Notaris
                            </a>
                            @endif
                        @endcan
                        @can('job/ppat/list')
                            @if ($tenantMenus->isEnabled('menu_job_ppat'))
                            <a href="{{ route('job.akta.data.filter', 'ppat') }}"
                                class="dropdown-item {{ request()->is('job/akta/filter-data/ppat*') ? 'active' : '' }}">
                                PPAT
                            </a>
                            @endif
                        @endcan
                        @can('job/waarmerking/list')
                            @if ($tenantMenus->isEnabled('menu_job_waarmerking'))
                            <a href="{{ route('job.akta.data.filter', 'waarmerking') }}"
                                class="dropdown-item {{ request()->is('job/akta/filter-data/waarmerking*') ? 'active' : '' }}">
                                Waarmerking
                            </a>
                            @endif
                        @endcan
                        @can('job/covernot/list')
                            @if ($tenantMenus->isEnabled('menu_job_covernot'))
                            <a href="{{ route('job.akta.data.filter', 'covernot') }}"
                                class="dropdown-item {{ request()->is('job/akta/filter-data/covernot*') ? 'active' : '' }}">
                                Cover note
                            </a>
                            @endif
                        @endcan
                        @can('job/surat-keluar/list')
                            @if ($tenantMenus->isEnabled('menu_job_surat_keluar'))
                            <a href="{{ route('job.akta.data.filter', 'surat-keluar') }}"
                                class="dropdown-item {{ request()->is('job/akta/filter-data/surat-keluar*') ? 'active' : '' }}">
                                Surat Keluar
                            </a>
                            @endif
                        @endcan
                        @can('job/legalisasi/list')
                            @if ($tenantMenus->isEnabled('menu_job_legalisasi'))
                            <a href="{{ route('job.akta.data.filter', 'legalisasi') }}"
                                class="dropdown-item {{ request()->is('job/akta/filter-data/legalisasi*') ? 'active' : '' }}">
                                Legalisasi
                            </a>
                            @endif
                        @endcan
                        @can('job/wasiat/list')
                            @if ($tenantMenus->isEnabled('menu_job_wasiat'))
                            <a href="{{ route('job.akta.data.filter', 'wasiat') }}"
                                class="dropdown-item {{ request()->is('job/akta/filter-data/wasiat*') ? 'active' : '' }}">
                                Wasiat
                            </a>
                            @endif
                        @endcan
                        @can('job/pajak/list')
                            @if ($tenantMenus->isEnabled('menu_job_pajak'))
                            <a href="{{ route('job.pajak.data.index') }}"
                                class="dropdown-item {{ request()->is('job/pajak/data*') ? 'active' : '' }}">
                                Pajak
                            </a>
                            @endif
                        @endcan
                        @can('job/pnbp/list')
                            @if ($tenantMenus->isEnabled('menu_job_pnbp'))
                            <a href="{{ route('job.pnbp.index') }}"
                                class="dropdown-item {{ request()->is('job/pnbp*') ? 'active' : '' }}">
                                PNBP/Voucher
                            </a>
                            @endif
                        @endcan
                        @can('job/penambahan-item/list')
                            @if ($tenantMenus->isEnabled('menu_job_penambahan_item'))
                            <a href="{{ route('job.penambahan-item.index') }}"
                                class="dropdown-item {{ request()->is('job/penambahan-item*') ? 'active' : '' }}">
                                Penambahan Item
                            </a>
                            @endif
                        @endcan
                        @can('job/pembatalan-item/list')
                            @if ($tenantMenus->isEnabled('menu_job_pembatalan_item'))
                            <a href="{{ route('job.pembatalan-items.index') }}"
                                class="dropdown-item {{ request()->is('job/pembatalan-item*') ? 'active' : '' }}">
                                Pembatalan Item
                            </a>
                            @endif
                        @endcan
                    </div>
                </li>
                @endif
                {{-- <li class="nav-item">
                    <a class="nav-link" href="#">
                        <span
                            class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler-icons.io/i/home -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                <path d="M21 21v-2a4 4 0 0 0 -3 -3.87" />
                            </svg>
                        </span>
                        <span class="nav-link-title">
                            CRM
                        </span>
                    </a>
                </li> --}}
                @if ($tenantMenus->anyEnabled(['menu_bermasalah_freeze', 'menu_bermasalah_pending', 'menu_bermasalah_disposisi']))
                @can('berkas-bermasalah/list')
                    <li class="nav-item dropdown {{ request()->is('berkas-bermasalah*') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle" href="#navbar-help" data-bs-toggle="dropdown"
                            data-bs-auto-close="false" role="button" aria-expanded="false">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-briefcase-off">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path
                                        d="M11 7h8a2 2 0 0 1 2 2v8m-1.166 2.818a1.993 1.993 0 0 1 -.834 .182h-14a2 2 0 0 1 -2 -2v-9a2 2 0 0 1 2 -2h2" />
                                    <path d="M8.185 4.158a2 2 0 0 1 1.815 -1.158h4a2 2 0 0 1 2 2v2" />
                                    <path d="M12 12v.01" />
                                    <path d="M3 13a20 20 0 0 0 11.905 1.928m3.263 -.763a20 20 0 0 0 2.832 -1.165" />
                                    <path d="M3 3l18 18" />
                                </svg>
                            </span>
                            <span class="nav-link-title">
                                Berkas Bermasalah
                            </span>
                        </a>
                        <div class="dropdown-menu {{ request()->is('berkas-bermasalah*') ? 'show' : '' }}">
                            @can('berkas-bermasalah/freeze/list')
                                @if ($tenantMenus->isEnabled('menu_bermasalah_freeze'))
                                <a href="{{ route('berkas-bermasalah.freeze.index') }}"
                                    class="dropdown-item {{ request()->is('berkas-bermasalah/freeze*') ? 'active' : '' }}">
                                    Freeze Job
                                </a>
                                @endif
                            @endcan
                            @can('berkas-bermasalah/pending/list')
                                @if ($tenantMenus->isEnabled('menu_bermasalah_pending'))
                                <a href="{{ route('berkas-bermasalah.pending.index') }}"
                                    class="dropdown-item {{ request()->is('berkas-bermasalah/pending*') ? 'active' : '' }}">
                                    Pending Job
                                </a>
                                @endif
                            @endcan
                            @can('berkas-bermasalah/dispo/list')
                                @if ($tenantMenus->isEnabled('menu_bermasalah_disposisi'))
                                <a href="{{ route('berkas-bermasalah.dispo.index') }}"
                                    class="dropdown-item {{ request()->is('berkas-bermasalah/dispo*') ? 'active' : '' }}">
                                    Disposisi
                                </a>
                                @endif
                            @endcan

                        </div>
                    </li>
                @endcan
                @endif
                @if ($tenantMenus->anyEnabled(['menu_finance_in', 'menu_finance_out', 'menu_finance_jurnal', 'menu_finance_neraca', 'menu_finance_laba_kotor', 'menu_finance_laba_bersih']))
                @can('finance/list')
                    <li class="nav-item dropdown {{ request()->is('finance*') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle" href="#navbar-help" data-bs-toggle="dropdown"
                            data-bs-auto-close="false" role="button" aria-expanded="false">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-coin">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                    <path
                                        d="M14.8 9a2 2 0 0 0 -1.8 -1h-2a2 2 0 1 0 0 4h2a2 2 0 1 1 0 4h-2a2 2 0 0 1 -1.8 -1" />
                                    <path d="M12 7v10" />
                                </svg>
                            </span>
                            <span class="nav-link-title">
                                Finance Control
                            </span>
                        </a>
                        <div class="dropdown-menu {{ request()->is('finance*') ? 'show' : '' }}">
                            @can('finance/in/list')
                                @if ($tenantMenus->isEnabled('menu_finance_in'))
                                <a href="{{ route('finance.job-divisi.index', ['type' => 'in']) }}"
                                    class="dropdown-item {{ request()->is('finance/job-divisi*') && request('type') == 'in' ? 'active' : '' }}">
                                    IN
                                </a>
                                @endif
                            @endcan

                            @can('finance/out/list')
                                @if ($tenantMenus->isEnabled('menu_finance_out'))
                                <a href="{{ route('finance.job-divisi.index', ['type' => 'out']) }}"
                                    class="dropdown-item {{ request()->is('finance/job-divisi*') && request('type') == 'out' ? 'active' : '' }}">
                                    OUT
                                </a>
                                @endif
                            @endcan
                            @can('finance/jurnal/list')
                                @if ($tenantMenus->isEnabled('menu_finance_jurnal'))
                                <a href="{{ route('finance.reports.jurnal') }}"
                                    class="dropdown-item {{ request()->routeIs('finance.reports.jurnal') ? 'active' : '' }}">
                                    Jurnal
                                </a>
                                @endif
                            @endcan
                            @can('finance/neraca/list')
                                @if ($tenantMenus->isEnabled('menu_finance_neraca'))
                                <a href="{{ route('finance.reports.neraca') }}"
                                    class="dropdown-item {{ request()->routeIs('finance.reports.neraca') ? 'active' : '' }}">
                                    Neraca
                                </a>
                                @endif
                            @endcan
                            @can('finance/laba-kotor/list')
                                @if ($tenantMenus->isEnabled('menu_finance_laba_kotor'))
                                <a href="{{ route('finance.reports.laba-kotor') }}"
                                    class="dropdown-item {{ request()->routeIs('finance.reports.laba-kotor') ? 'active' : '' }}">
                                    Laba Kotor
                                </a>
                                @endif
                            @endcan
                            @can('finance/laba-bersih/list')
                                @if ($tenantMenus->isEnabled('menu_finance_laba_bersih'))
                                <a href="{{ route('finance.reports.laba-bersih') }}"
                                    class="dropdown-item {{ request()->routeIs('finance.reports.laba-bersih') ? 'active' : '' }}">
                                    Laba Bersih
                                </a>
                                @endif
                            @endcan
                        </div>
                    </li>
                @endcan
                @endif
                @if ($tenantMenus->anyEnabled(['menu_hris_employee', 'menu_hris_cuti', 'menu_hris_lembur']))
                @can('hris/list')
                    <li class="nav-item dropdown {{ request()->is('hris*') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle" href="#navbar-help" data-bs-toggle="dropdown"
                            data-bs-auto-close="false" role="button" aria-expanded="false">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">


                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-friends">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M7 5m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                    <path d="M5 22v-5l-1 -1v-4a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4l-1 1v5" />
                                    <path d="M17 5m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                    <path d="M15 22v-4h-2l2 -6a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1l2 6h-2v4" />
                                </svg>
                            </span>
                            <span class="nav-link-title">
                                HRIS
                            </span>
                        </a>
                        <div class="dropdown-menu {{ request()->is('hris*') ? 'show' : '' }}">
                            @can('hris/employee/list')
                                @if ($tenantMenus->isEnabled('menu_hris_employee'))
                                <a href="{{ route('hris.employee.index') }}"
                                    class="dropdown-item {{ request()->is('hris/employee*') ? 'active' : '' }}">
                                    Employee
                                </a>
                                @endif
                            @endcan
                            @can('hris/cuti/list')
                                @if ($tenantMenus->isEnabled('menu_hris_cuti'))
                                <a href="{{ route('hris.cuti.index') }}"
                                    class="dropdown-item {{ request()->is('hris/cuti*') ? 'active' : '' }}">
                                    Cuti Karyawan
                                </a>
                                @endif
                            @endcan
                            @can('hris/lembur/list')
                                @if ($tenantMenus->isEnabled('menu_hris_lembur'))
                                <a href="{{ route('hris.lembur.index') }}"
                                    class="dropdown-item {{ request()->is('hris/lembur*') ? 'active' : '' }}">
                                    Lembur Karyawan
                                </a>
                                @endif
                            @endcan
                        </div>
                    </li>
                @endcan
                @endif


                @if ($tenantMenus->anyEnabled(['menu_arsip_akta', 'menu_arsip_ppat', 'menu_arsip_warkah']))
                @can('arsip/list')
                    <li class="nav-item dropdown {{ request()->is('arsip*') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle" href="#navbar-help" data-bs-toggle="dropdown"
                            data-bs-auto-close="false" role="button" aria-expanded="false">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M4 12a8 8 0 1 0 16 0a8 8 0 1 0 -16 0" />
                                    <path d="M12 8l0 4l2 2" />
                                </svg>
                            </span>
                            <span class="nav-link-title">
                                Arsip
                            </span>
                        </a>
                        <div class="dropdown-menu {{ request()->is('arsip*') ? 'show' : '' }}">
                            @can('arsip/akta/list')
                                @if ($tenantMenus->isEnabled('menu_arsip_akta'))
                                <a href="{{ route('arsip.bundle.index') }}" class="dropdown-item ">
                                    Akta
                                </a>
                                @endif
                            @endcan
                            @can('arsip/ppat/list')
                                @if ($tenantMenus->isEnabled('menu_arsip_ppat'))
                                <a href="{{ route('arsip.bundle.index', ['tipe' => 'ppat']) }}" class="dropdown-item ">
                                    PPAT
                                </a>
                                @endif
                            @endcan
                            @can('arsip/warkah/list')
                                @if ($tenantMenus->isEnabled('menu_arsip_warkah'))
                                <a href="{{ route('arsip.warkah.index') }}"
                                    class="dropdown-item {{ request()->is('arsip/warkah*') ? 'active' : '' }}">
                                    Warkah
                                </a>
                                @endif
                            @endcan
                        </div>
                    </li>
                @endcan

                @endif
                @if ($tenantMenus->anyEnabled(['menu_laporan_nomor_notaris', 'menu_laporan_nomor_ppat', 'menu_laporan_nomor_waarmerking', 'menu_laporan_nomor_covernot', 'menu_laporan_nomor_surat_keluar', 'menu_laporan_nomor_legalisasi', 'menu_laporan_nomor_wasiat', 'menu_laporan_pemakaian_nomor', 'menu_laporan_pekerjaan_staff', 'menu_laporan_job_divisi']))
                <li class="nav-item dropdown {{ request()->is('laporan*') ? 'active' : '' }}">
                    @can('laporan/list')
                        <a class="nav-link dropdown-toggle" href="#navbar-help" data-bs-toggle="dropdown"
                            data-bs-auto-close="false" role="button" aria-expanded="false">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                    fill="currentColor" class="bi bi-archive" viewBox="0 0 16 16">
                                    <path
                                        d="M0 2a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1v7.5a2.5 2.5 0 0 1-2.5 2.5h-9A2.5 2.5 0 0 1 1 12.5V5a1 1 0 0 1-1-1zm2 3v7.5A1.5 1.5 0 0 0 3.5 14h9a1.5 1.5 0 0 0 1.5-1.5V5zm13-3H1v2h14zM5 7.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5" />
                                </svg>
                            </span>
                            <span class="nav-link-title">
                                Laporan
                            </span>
                        </a>
                    @endcan
                    <div class="dropdown-menu {{ request()->is('laporan*') ? 'show' : '' }}">
                        {{-- Laporan Penomoran --}}
                        @if ($tenantMenus->anyEnabled(['menu_laporan_nomor_notaris', 'menu_laporan_nomor_ppat', 'menu_laporan_nomor_waarmerking', 'menu_laporan_nomor_covernot', 'menu_laporan_nomor_surat_keluar', 'menu_laporan_nomor_legalisasi', 'menu_laporan_nomor_wasiat']))
                        <div class="dropend ">
                            @can('laporan/nomor/list')
                                <a class="dropdown-item dropdown-toggle" href="#sidebar-authentication"
                                    data-bs-toggle="dropdown" data-bs-auto-close="true" role="button"
                                    aria-expanded="false">
                                    Penomoran
                                </a>
                            @endcan
                            <div class="dropdown-menu {{ request()->is('laporan/nomor*') ? 'show' : '' }}">
                                @can('laporan/notaris/list')
                                    @if ($tenantMenus->isEnabled('menu_laporan_nomor_notaris'))
                                    <a href="{{ route('laporan.nomor-notaris', 'notaris') }}"
                                        class="dropdown-item {{ request()->is('laporan/nomor-notaris/notaris*') ? 'active' : '' }}">
                                        Notaris
                                    </a>
                                    @endif
                                @endcan
                                @can('laporan/ppat/list')
                                    @if ($tenantMenus->isEnabled('menu_laporan_nomor_ppat'))
                                    <a href="{{ route('laporan.nomor-notaris', 'ppat') }}"
                                        class="dropdown-item {{ request()->is('laporan/nomor-notaris/ppat*') ? 'active' : '' }}">
                                        PPAT
                                    </a>
                                    @endif
                                @endcan
                                @can('laporan/waarmerking/list')
                                    @if ($tenantMenus->isEnabled('menu_laporan_nomor_waarmerking'))
                                    <a href="{{ route('laporan.nomor-notaris', 'waarmerking') }}"
                                        class="dropdown-item {{ request()->is('laporan/nomor-notaris/waarmerking*') ? 'active' : '' }}">
                                        Waarmerking
                                    </a>
                                    @endif
                                @endcan
                                @can('laporan/covernot/list')
                                    @if ($tenantMenus->isEnabled('menu_laporan_nomor_covernot'))
                                    <a href="{{ route('laporan.nomor-notaris', 'covernot') }}"
                                        class="dropdown-item {{ request()->is('laporan/nomor-notaris/covernot*') ? 'active' : '' }}">
                                        Cover Not
                                    </a>
                                    @endif
                                @endcan
                                @can('laporan/surat-keluar/list')
                                    @if ($tenantMenus->isEnabled('menu_laporan_nomor_surat_keluar'))
                                    <a href="{{ route('laporan.nomor-notaris', 'surat-keluar') }}"
                                        class="dropdown-item {{ request()->is('laporan/nomor-notaris/surat-keluar*') ? 'active' : '' }}">
                                        Surat Keluar
                                    </a>
                                    @endif
                                @endcan
                                @can('laporan/legalisasi/list')
                                    @if ($tenantMenus->isEnabled('menu_laporan_nomor_legalisasi'))
                                    <a href="{{ route('laporan.nomor-notaris', 'legalisasi') }}"
                                        class="dropdown-item {{ request()->is('laporan/nomor-notaris/legalisasi*') ? 'active' : '' }}">
                                        Legalisasi
                                    </a>
                                    @endif
                                @endcan
                                @can('laporan/wasiat/list')
                                    @if ($tenantMenus->isEnabled('menu_laporan_nomor_wasiat'))
                                    <a href="{{ route('laporan.nomor-notaris', 'wasiat') }}"
                                        class="dropdown-item {{ request()->is('laporan/nomor-notaris/wasiat*') ? 'active' : '' }}">
                                        Wasiat
                                    </a>
                                    @endif
                                @endcan
                            </div>

                        </div>
                        @endif
                        {{-- Laporan Penomoran end --}}
                        @can('laporan/pemakaian-nomor/list')
                            @if ($tenantMenus->isEnabled('menu_laporan_pemakaian_nomor'))
                            <a href="{{ route('laporan.pemakaian-nomor') }}"
                                class="dropdown-item {{ request()->routeIs('laporan.pemakaian-nomor') ? 'active' : '' }}">
                                Pemakaian Nomor
                            </a>
                            @endif
                        @endcan

                        @can('laporan/list-pekerjaan-staff')
                            @if ($tenantMenus->isEnabled('menu_laporan_pekerjaan_staff'))
                            <a href="{{ route('laporan.list-pekerjaan-staff') }}"
                                class="dropdown-item {{ request()->is('laporan/list-pekerjaan-staff') ? 'active' : '' }}">
                                History Pekerjaan Staff
                            </a>
                            @endif
                        @endcan

                        @can('laporan/list-job-divisi-history')
                            @if ($tenantMenus->isEnabled('menu_laporan_job_divisi'))
                            <a href="{{ route('laporan.list-job-divisi-history') }}"
                                class="dropdown-item {{ request()->routeIs('laporan.list-job-divisi-history') ? 'active' : '' }}">
                                History Job Divisi
                            </a>
                            @endif
                        @endcan

                    </div>

                </li>
                @endif


                @if ($tenantMenus->anyEnabled(['menu_akses_role', 'menu_akses_user']))
                @can('akses/list')
                    <li class="nav-item dropdown {{ request()->is('akses*') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle" href="#navbar-help" data-bs-toggle="dropdown"
                            data-bs-auto-close="false" role="button" aria-expanded="false">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-user-cog">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                    <path d="M6 21v-2a4 4 0 0 1 4 -4h2.5" />
                                    <path d="M19.001 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                    <path d="M19.001 15.5v1.5" />
                                    <path d="M19.001 21v1.5" />
                                    <path d="M22.032 17.25l-1.299 .75" />
                                    <path d="M17.27 20l-1.3 .75" />
                                    <path d="M15.97 17.25l1.3 .75" />
                                    <path d="M20.733 20l1.3 .75" />
                                </svg>
                            </span>
                            <span class="nav-link-title">
                                Akses
                            </span>
                        </a>
                        <div class="dropdown-menu {{ request()->is('akses*') ? 'show' : '' }}">
                            @can('akses/role/list')
                                @if ($tenantMenus->isEnabled('menu_akses_role'))
                                {{-- @if ((int) auth()->user()->id === 1) --}}
                                <a href="{{ route('akses.role.index') }}"
                                    class="dropdown-item {{ request()->is('akses/role*') ? 'active' : '' }}">
                                    Role & Permission
                                </a>
                                @endif
                                {{-- @endif --}}
                            @endcan
                            @can('akses/user/list')
                                @if ($tenantMenus->isEnabled('menu_akses_user'))
                                <a href="{{ route('akses.user.index') }}"
                                    class="dropdown-item {{ request()->is('finance/job-divisi') ? 'active' : '' }}">
                                    User
                                </a>
                                @endif
                            @endcan

                        </div>
                    </li>
                @endcan
                @endif

                @canany(['setting/perusahaan/list', 'setting/penomoran/list', 'setting/step-ops/list', 'setting/workflow-akta/list', 'setting/approval/list', 'setting/features/list'])
                @if ($tenantMenus->anyEnabled(['menu_setting_perusahaan', 'menu_setting_penomoran', 'menu_setting_workflow_akta', 'menu_setting_approval', 'menu_setting_features', 'menu_setting_whatsapp']))
                <li class="nav-item dropdown {{ request()->is('setting*') ? 'active' : '' }}">
                    <a class="nav-link dropdown-toggle" href="#navbar-help" data-bs-toggle="dropdown"
                        data-bs-auto-close="false" role="button" aria-expanded="false">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M4 12a8 8 0 1 0 16 0a8 8 0 1 0 -16 0" />
                                <path d="M12 8l0 4l2 2" />
                            </svg>
                        </span>
                        <span class="nav-link-title">
                            Setting
                        </span>
                    </a>
                    <div class="dropdown-menu {{ request()->is('setting*') ? 'show' : '' }}">
                        @can('setting/perusahaan/list')
                        @if ($tenantMenus->isEnabled('menu_setting_perusahaan'))
                        <a href="{{ route('setting.perusahaan.index') }}"
                            class="dropdown-item {{ request()->is('setting/perusahaan*') ? 'active' : '' }}">
                            Setting Perusahaan
                        </a>
                        @endif
                        @endcan
                        @can('setting/penomoran/list')
                        @if ($tenantMenus->isEnabled('menu_setting_penomoran'))
                        <a href="{{ route('setting.penomoran.index') }}"
                            class="dropdown-item {{ request()->is('setting/penomoran*') ? 'active' : '' }}">
                            Setting Penomoran
                        </a>
                        @endif
                        @endcan
                        @can('setting/workflow-akta/list')
                            @if ($tenantMenus->isEnabled('menu_setting_workflow_akta') && $tenantMenus->isEnabled('configurable_akta_workflow'))
                                <a href="{{ route('setting.workflow-akta.index') }}"
                                    class="dropdown-item {{ request()->is('setting/workflow-akta*') ? 'active' : '' }}">
                                    Workflow Job Akta
                                </a>
                            @endif
                        @endcan
                        @can('setting/approval/list')
                            @if ($tenantMenus->isEnabled('menu_setting_approval') && $tenantMenus->isEnabled('configurable_approval'))
                                <a href="{{ route('setting.approval.index') }}"
                                    class="dropdown-item {{ request()->is('setting/approval*') ? 'active' : '' }}">
                                    Konfigurasi Approval
                                </a>
                            @endif
                        @endcan
                        @can('setting/features/list')
                        @if ($tenantMenus->isEnabled('menu_setting_features'))
                            <a href="{{ route('setting.features.index') }}"
                                class="dropdown-item {{ request()->is('setting/features*') ? 'active' : '' }}">
                                Fitur Tenant
                            </a>
                            @endif
                            @endcan

                        @can('setting/step-ops/list')
                            @if ($tenantMenus->isEnabled('menu_setting_whatsapp'))
                        <a href="{{ route('setting.wa.index') }}"
                            class="dropdown-item {{ request()->is('setting/step-ops*') ? 'active' : '' }}">
                            Nomor WhatsApp
                        </a>
                        @endif
                        @endcan
                    </div>
                </li>
                @endif
                @endcanany

                <li class="nav-item mt-auto mb-2">
                    <a class="nav-link" href="{{ route('logoutProses') }}">
                        <span
                            class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler-icons.io/i/home -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-logout-2"
                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path
                                    d="M10 8v-2a2 2 0 0 1 2 -2h7a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-7a2 2 0 0 1 -2 -2v-2">
                                </path>
                                <path d="M15 12h-12l3 -3"></path>
                                <path d="M6 15l-3 -3"></path>
                            </svg>
                        </span>
                        <span class="n  av-link-title">
                            Logout
                        </span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>

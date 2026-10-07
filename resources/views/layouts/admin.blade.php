<!doctype html>

<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>@yield('title') - SUPER NOTARIS</title>
    <!-- CSS files -->
    <link href="/tabler-admin/demo/dist/css/tabler.min.css?1684106062" rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('plugins/bootstrap-icons-1.11.3/font/bootstrap-icons.css') }}">

    {{-- Plugins JS --}}
    <script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
    <!-- Primary Meta Tags -->
    <meta name="title" content="SUPER NOTARIS" />
    <meta name="description" content="NOTARIS SOFTWARE" />

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ url('/') }}" />
    <meta property="og:title" content="SUPER NOTARIS" />
    <meta property="og:description" content="NOTARIS SOFTWARE" />
    {{-- <meta property="og:image" content="{{ asset('gambar/logo.png') }}" /> --}}

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image" />
    <meta property="twitter:url" content="{{ url('/') }}" />
    <meta property="twitter:title" content="SUPER NOTARIS" />
    <meta property="twitter:description" content="NOTARIS SOFTWARE" />
    {{-- <meta property="twitter:image" content="{{ asset('gambar/logo.png') }}" /> --}}

    <!-- Meta Tags Generated with https://metatags.io -->

    <style>
        @import url('https://rsms.me/inter/inter.css');

        :root {
            --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }

        body {
            font-feature-settings: "cv03", "cv04", "cv11";
        }

        .add_more {
            background-color: #f1f1f1;
            border: 1px dashed #ccc;
            border-radius: 0.25rem;

            cursor: pointer;
        }

        .loading {
            position: fixed;
            height: 100vh;
            width: 100%;
            background-color: rgba(20, 20, 20, 0.623);
            top: 0;
            z-index: 10000;

        }

        header .card.notif {
            max-height: 314px;
            overflow-y: auto;
            min-width: 25rem;
        }

        table.table {
            white-space: nowrap;
        }

        .table-sm>:not(caption)>*>* {
            padding: 0.25rem 0.25rem;
            font-size: 12px;
        }


        .navbar-vertical.navbar-expand-lg {
            overflow-y: auto !important;
        }

        .dropdown-item.active,
        .dropdown-item:active {
            border-left: 3px solid #d6d6d6;
        }

        .table-search-input {
            min-width: 120px;
            font-size: 12px;
        }

        .table-enhancer-toolbar {
            gap: 0.75rem;
        }

        .table-enhancer-pagination .btn {
            min-width: 38px;
        }

        .table-enhancer-footer {
            gap: 0.75rem;
        }

        .table-enhancer-page-size {
            width: auto;
            min-width: 84px;
        }

        .table-sortable-header {
            cursor: pointer;
            user-select: none;
        }

        .table-sortable-header:focus-visible {
            outline: 2px solid var(--tblr-primary);
            outline-offset: -2px;
        }

        .table-sort-indicator {
            display: inline-block;
            margin-left: 0.35rem;
            opacity: 0.45;
        }

        .table-sortable-header[aria-sort="ascending"] .table-sort-indicator,
        .table-sortable-header[aria-sort="descending"] .table-sort-indicator {
            opacity: 1;
        }

    </style>

    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <script src="{{ asset('js/jQuery-Mask-Plugin-master/src/jquery.mask.js') }}"></script>

    @stack('addStyle')
</head>

<body id="body">

    <script src="/tabler-admin/demo/dist/js/demo-theme.min.js?1684106062"></script>
    <div class="page">
        <!-- Sidebar -->
        @include('includes.admin._sidebar')
        @include('includes.admin._header')
        @include('includes._loading')

        <div class="loading__print" style="display: none; left: 1px;">
            <div class="loading  d-flex flex-column align-items-center justify-content-center" style="left: 0">
                <div class="spinner-border text-danger" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="text-center fw-bold mt-3">Loading Print</div>
            </div>
        </div>

        <div class="page-wrapper">
            <!-- Page header -->
            @if (!request()->routeIs('welcome'))
                <div class="page-header d-print-none">
                    <div class="container-fluid">
                        <div class="row g-2 align-items-center">
                            <div class="col">
                                <div class="">
                                    <!-- Page pre-title -->
                                    <div class="page-pretitle">
                                        Page
                                    </div>
                                    <h2 class="page-title">
                                        @hasSection('page-heading')
                                            @yield('page-heading')
                                        @else
                                            @yield('title')
                                        @endif
                                    </h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Page body -->
            <div class="page-body">
                <div class="container-fluid">

                    @if ($errors->any())
                        <div class="validasi_request">
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    @yield('content')

                </div>
            </div>


            <footer class="footer footer-transparent d-print-none">
                <div class="container-xl">
                    <div class="row text-center align-items-center flex-row-reverse">

                        <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                            <ul class="list-inline list-inline-dots mb-0">
                                <li class="list-inline-item">
                                    <div class="text-dark">
                                        SUPER NOTARIS {{ \Carbon\Carbon::now()->format('Y') }}
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Libs JS -->

    <!-- Tabler Core -->
    <script src="/tabler-admin/demo/dist/js/tabler.min.js?1684106062" defer></script>
    <script src="/tabler-admin/demo/dist/js/demo.min.js?1684106062" defer></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


    <script>
        $(document).on('select2:open', () => {
            let searchField = document.querySelector('.select2-container--open .select2-search__field');
            if (searchField) {
                searchField.focus();
            }
        });
    </script>

    <script>
        $('.select2').select2({
            theme: 'bootstrap-5'
        });

        $('.money').mask('#.##0', {
            reverse: true
        });
    </script>

    @if (Session::get('success'))
        <script>
            Swal.fire({
                icon: "success",
                title: "{{ Session::get('success') }}"
            });
        </script>
    @endif

    @if (Session::get('error'))
        <script>
            Swal.fire({
                icon: "error",
                text: "{{ Session::get('error') }}"
            });
        </script>
    @endif

    @if (Session::get('info'))
        <script>
            Swal.fire({
                icon: "info",
                text: "{{ Session::get('info') }}"
            });
        </script>
    @endif

    @if (Session::get('toast_success'))
        <script>
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal
                        .resumeTimer)
                }
            })

            Toast.fire({
                icon: 'success',
                title: '{{ Session::get('toast_success') }}'
            });
        </script>
    @endif

    <script>
        $("table").addClass("table-hover");
        $(".confirm_delete").on("click", function() {
            var form = $(this).closest("form");
            event.preventDefault();
            let message = $(this).attr("data-message");

            if (message) {
                message = `Data ${message} akan dihapus`
            }

            Swal.fire({
                title: "Hapus Data Ini ?",
                icon: "warning",
                text: message ? message : "",
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $(".loading__global").show();
                    form.submit();
                }
            });
        })
    </script>
    <script>
        $(".confirm_batal_akad").on("click", function(event) {
            event.preventDefault();

            let form = $(this).closest("form");
            let message = $(this).attr("data-message");

            Swal.fire({
                title: "Batalkan Akad?",
                text: `Data ${message} akan dibatalkan.`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Ya, Batalkan",
                cancelButtonText: "Batal"
            }).then((result) => {
                if (result.isConfirmed) {
                    $(".loading__global").show();
                    form.submit(); // ✅ WAJIB
                }
            });
        });
    </script>
    <script>
        let tempDisplayLoading = false;

        function displayLoading() {
            if (!displayLoading) {
                tempDisplayLoading = true;

                $(".loading__global").show();

                console.log("HALLO LOADING");

            } else {
                tempDisplayLoading = false;
                $(".loading__global").hide();
            }
        }

        let isFullScreen = false;

        function enterFullScreen() {
            if (document.documentElement.requestFullscreen) {
                document.documentElement.requestFullscreen();
            } else if (document.documentElement.mozRequestFullScreen) {
                document.documentElement.mozRequestFullScreen();
            } else if (document.documentElement.webkitRequestFullscreen) {
                document.documentElement.webkitRequestFullscreen();
            } else if (document.documentElement.msRequestFullscreen) {
                document.documentElement.msRequestFullscreen();
            }
        }

        function exitFullScreen() {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        }

        function toggleFullScreen() {
            if (!isFullScreen) {
                enterFullScreen();
                isFullScreen = true;
            } else {
                exitFullScreen();
                isFullScreen = false;
            }
        }

        window.addEventListener('load', () => {
            if (isFullScreen) {
                enterFullScreen();
            }
        });
    </script>
    <script src="{{ asset('js/table-sort.js') }}"></script>
    @stack('addScript')

</body>

</html>

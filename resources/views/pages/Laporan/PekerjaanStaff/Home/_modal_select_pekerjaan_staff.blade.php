<form id="filterForm" action="{{ route('laporan.list-pekerjaan-staff') }}" method="GET"
    style="display: flex; align-items: center; gap: 10px;">

    {{-- <label>Dari:</label> --}}
    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}"
        style="width: auto;">

    <label>Sampai:</label>
    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}"
        style="width: auto;">

    <button type="submit" class="btn btn-outline-warning"><svg xmlns="http://www.w3.org/2000/svg" width="16"
            height="16" fill="currentColor" class="bi bi-funnel me-1" viewBox="0 0 16 16">
            <path
                d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2z" />
        </svg>Filter</button>

    <a href="{{ route('laporan.list-pekerjaan-staff') }}" class="btn btn-outline-secondary"><svg
            xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
            class="bi bi-arrow-counterclockwise me-1" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M8 3a5 5 0 1 1-4.546 2.914.5.5 0 0 0-.908-.417A6 6 0 1 0 8 2z" />
            <path
                d="M8 4.466V.534a.25.25 0 0 0-.41-.192L5.23 2.308a.25.25 0 0 0 0 .384l2.36 1.966A.25.25 0 0 0 8 4.466" />
        </svg>Reset</a>
</form>


<script>
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');

    // When "Dari" changes, update the minimum allowed date for "Sampai"
    startDate.addEventListener('change', function() {
        if (startDate.value) {
            endDate.min = startDate.value;
        }
    });

    // When "Sampai" changes, update the maximum allowed date for "Dari"
    endDate.addEventListener('change', function() {
        if (endDate.value) {
            startDate.max = endDate.value;
        }
    });
</script>

<!-- Modal Export Excel -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- The form submits to your Export route using GET -->
            <form action="{{ route('job.export-job-ops') }}" method="GET">
                
                <div class="modal-header text-bg-success">
                    <h5 class="modal-title" id="exportModalLabel">
                        <i class="bi bi-file-earmark-excel me-2"></i> Export Data Job Operasional
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <p class="text-muted mb-3">Silahkan pilih filter data yang ingin di-export ke Excel. Kosongkan jika ingin meng-export semua data.</p>

                    <!-- Filter Status -->
                    <div class="mb-3">
                        <label for="export_status" class="form-label fw-bold">Status</label>
                        <select name="status" id="export_status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="belum dikerjakan">Belum Dikerjakan</option>
                            <option value="dispo">Dispo</option>
                            <option value="Penugasan">Penugasan</option>
                            <!-- Add any other status options here -->
                        </select>
                    </div>

                    <div class="row">
                        <!-- Filter Tanggal Mulai (Start Date) -->
                        <div class="col-md-6 mb-3">
                            <label for="export_tanggal_mulai" class="form-label fw-bold">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" id="export_tanggal_mulai" class="form-control">
                        </div>

                        <!-- Filter End Date -->
                        <div class="col-md-6 mb-3">
                            <label for="export_end_date" class="form-label fw-bold">Sampai Tanggal</label>
                            <input type="date" name="end_date" id="export_end_date" class="form-control">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <!-- RESET BUTTON -->
                    <button type="reset" class="btn btn-outline-danger me-auto">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-download"></i> Download Excel
                    </button>
                </div>
                
            </form>
        </div>
    </div>
</div>
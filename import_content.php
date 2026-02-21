<div class="row justify-content-center">
  <div class="col-12 col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h3 class="mb-3">Impor Data</h3>

        <form method="POST" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label">Pilih Jenis Data</label>
            <select name="import_type" class="form-select" required>
              <option value="" disabled selected>-- Pilih --</option>
              <option value="karyawan">Karyawan</option>
              <option value="cuti">Cuti</option>
              <option value="surat_tugas">Surat Tugas</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Pilih File Excel</label>
            <input type="file" name="file" accept=".xlsx" class="form-control" required>
            <div class="form-text">Format file harus .xlsx dan sesuai template.</div>
          </div>

          <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-primary" type="submit">Upload</button>
            <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
            <button type="button" id="btnTemplate" class="btn btn-info text-white">Unduh Template</button>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>

<script>
  document.getElementById('btnTemplate').addEventListener('click', () => {
    const t = document.querySelector('select[name="import_type"]').value;
    if (!t) return alert('Pilih jenis data dulu!');
    window.location = 'import.php?download_template=' + t;
  });
</script>

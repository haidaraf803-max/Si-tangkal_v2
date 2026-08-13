<?php
/**
 * layouts/export_modal.php
 * ---------------------------------------------------------
 * Partial tombol + modal "Export CSV" yang dipakai berulang di
 * semua halaman listing Admin (pohon, pengajuan, monitoring, dll).
 *
 * Cara pakai di halaman pemanggil, sebelum include ini set:
 *   $exportModul       = 'pohon';        // sesuai key di Admin/export.php
 *   $exportLabel        = 'Data Pohon';   // opsional, untuk teks judul modal
 *   $exportSupportsDate = true;           // false jika modul tidak punya kolom tanggal
 *
 * Lalu panggil:
 *   <?php require 'layouts/export_modal.php'; ?>
 * di tempat tombol ingin ditampilkan (biasanya di toolbar dekat tombol "Tambah").
 * ---------------------------------------------------------
 */

$exportModul        = $exportModul ?? '';
$exportLabel         = $exportLabel ?? 'Data';
$exportSupportsDate  = $exportSupportsDate ?? true;
$exportModalId       = 'modalExport_' . preg_replace('/[^a-zA-Z0-9_]/', '', $exportModul);
?>
<button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#<?= $exportModalId ?>">
    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
</button>

<div class="modal fade" id="<?= $exportModalId ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Export CSV — <?= htmlspecialchars($exportLabel) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form onsubmit="return sitangkalSubmitExport('<?= $exportModalId ?>', '<?= htmlspecialchars($exportModul, ENT_QUOTES) ?>')">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Pilih Data yang Diekspor</label>

            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="mode" id="<?= $exportModalId ?>_all" value="all" checked
                     onchange="sitangkalToggleExportFields('<?= $exportModalId ?>')">
              <label class="form-check-label" for="<?= $exportModalId ?>_all">
                Semua Data
              </label>
            </div>

            <?php if ($exportSupportsDate): ?>
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="mode" id="<?= $exportModalId ?>_tanggal" value="tanggal"
                     onchange="sitangkalToggleExportFields('<?= $exportModalId ?>')">
              <label class="form-check-label" for="<?= $exportModalId ?>_tanggal">
                Per Tanggal Tertentu
              </label>
            </div>
            <div class="ms-4 mb-2 export-field-tanggal" id="<?= $exportModalId ?>_field_tanggal" style="display:none;">
              <input type="date" class="form-control form-control-sm" name="tanggal" id="<?= $exportModalId ?>_input_tanggal">
            </div>

            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="mode" id="<?= $exportModalId ?>_rentang" value="rentang"
                     onchange="sitangkalToggleExportFields('<?= $exportModalId ?>')">
              <label class="form-check-label" for="<?= $exportModalId ?>_rentang">
                Rentang Tanggal
              </label>
            </div>
            <div class="ms-4 mb-1 export-field-rentang d-flex gap-2" id="<?= $exportModalId ?>_field_rentang" style="display:none;">
              <div class="flex-fill">
                <label class="form-label small text-muted mb-1">Dari</label>
                <input type="date" class="form-control form-control-sm" name="dari" id="<?= $exportModalId ?>_input_dari">
              </div>
              <div class="flex-fill">
                <label class="form-label small text-muted mb-1">Sampai</label>
                <input type="date" class="form-control form-control-sm" name="sampai" id="<?= $exportModalId ?>_input_sampai">
              </div>
            </div>
            <?php else: ?>
            <div class="form-text">Data pada modul ini tidak memiliki kolom tanggal, sehingga hanya tersedia ekspor seluruh data.</div>
            <?php endif; ?>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success">
            <i class="bi bi-download me-1"></i> Download CSV
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function sitangkalToggleExportFields(modalId) {
  var mode = document.querySelector('#' + modalId + ' input[name="mode"]:checked').value;
  var fTanggal = document.getElementById(modalId + '_field_tanggal');
  var fRentang = document.getElementById(modalId + '_field_rentang');
  if (fTanggal) fTanggal.style.display = (mode === 'tanggal') ? 'block' : 'none';
  if (fRentang) fRentang.style.display = (mode === 'rentang') ? 'flex' : 'none';
}

function sitangkalSubmitExport(modalId, modul) {
  var mode = document.querySelector('#' + modalId + ' input[name="mode"]:checked').value;
  var url = 'export.php?modul=' + encodeURIComponent(modul) + '&mode=' + encodeURIComponent(mode);

  if (mode === 'tanggal') {
    var tgl = document.getElementById(modalId + '_input_tanggal').value;
    if (!tgl) { alert('Silakan pilih tanggal terlebih dahulu.'); return false; }
    url += '&tanggal=' + encodeURIComponent(tgl);
  } else if (mode === 'rentang') {
    var dari = document.getElementById(modalId + '_input_dari').value;
    var sampai = document.getElementById(modalId + '_input_sampai').value;
    if (!dari || !sampai) { alert('Silakan pilih tanggal awal dan akhir.'); return false; }
    url += '&dari=' + encodeURIComponent(dari) + '&sampai=' + encodeURIComponent(sampai);
  }

  window.location.href = url;
  return false;
}
</script>

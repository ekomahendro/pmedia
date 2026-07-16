<!-- MODAL PREVIEW DETAIL LENGKAP MANIFEST -->
<div class="modal fade" id="modalDetailManifest" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-dark text-white"><h6 class="modal-title fw-bold">Detail Profil Lengkap Guest Stay</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body small p-3">
                <table class="table table-sm table-bordered">
                    <tr><th class="bg-light w-40">Nama Tamu</th><td id="det_guest_name" class="fw-bold"></td></tr>
                    <tr><th class="bg-light">Nomor HP</th><td id="det_phone"></td></tr>
                    <tr><th class="bg-light">Nomor Kamar</th><td id="det_room" class="fw-bold text-dark"></td></tr>
                    <tr><th class="bg-light">ETA Kedatangan</th><td id="det_eta"></td></tr>
                    <tr><th class="bg-light">History Frequency</th><td id="det_history"></td></tr>
                    <tr><th class="bg-light">Keterangan / Notes</th><td id="det_notes"></td></tr>
                    <tr class="table-warning"><th class="fw-bold">Room Rate Applied</th><td id="det_rate" class="fw-bold text-primary"></td></tr>
                </table>
                <h6 class="fw-bold text-secondary mt-2 mb-1"><i class="bi bi-clock-history"></i> Log History Pindah Kamar (Room Move)</h6>
                <div id="container_move_history" class="border p-2 bg-light rounded mb-2" style="max-height:95px; overflow-y:auto;"></div>
                <h6 class="fw-bold text-secondary mb-1"><i class="bi bi-wallet2"></i> Rincian Kas Histori Multi Deposit</h6>
                <div id="container_deposit_history" class="border p-2 bg-light rounded" style="max-height:95px; overflow-y:auto;"></div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL RESERVASI BARU -->
<div class="modal fade" id="modalReservation" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-calendar-plus-fill"></i> Form Buat Reservasi Kamar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="action_handler.php" method="POST">
                <div class="modal-body p-3 row g-2 small">
                    <div class="col-12 bg-light p-3 rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="fw-bold text-primary mb-0"><i class="bi bi-person-check-fill"></i> Pilih Profil Guest Card Terdaftar</label>
                            <button type="button" class="btn btn-xs btn-outline-success px-2 py-0 fw-bold rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalFastAddGuest">
                                <i class="bi bi-person-plus-fill"></i> + Cepat Tamu Baru
                            </button>
                        </div>
                        <select name="selected_guest_id" id="res_guest_select" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Tamu Sesuai Kartu Identitas (Wajib Terdaftar) --</option>
                            <?php mysqli_data_seek($guests_datalist, 0); while($g = mysqli_fetch_assoc($guests_datalist)): ?>
                                <option value="<?= $g['id_guest']; ?>"><?= htmlspecialchars($g['guest_name']); ?> - KTP: <?= $g['identity_number']; ?> (Telp: <?= $g['phone_number']; ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4"><label class="fw-bold mb-0">Alokasi Kamar</label><select name="room_number" class="form-select form-select-sm" required><option value="">-- Pilih Kamar --</option><?php mysqli_data_seek($rooms_available, 0); while($r = mysqli_fetch_assoc($rooms_available)): ?><option value="<?= $r['room_number']; ?>">Room <?= $r['room_number']; ?> - <?= $r['room_type']; ?></option><?php endwhile; ?></select></div>
                    <div class="col-md-4"><label class="fw-bold mb-0">Harga Kamar / Malam (Rp)</label><input type="number" name="room_rate" class="form-control form-control-sm" value="0" required></div>
                    <div class="col-md-4"><label class="fw-bold mb-0">Initial Deposit Jaminan (Rp)</label><input type="number" name="deposit" class="form-control form-control-sm" value="0"></div>
                    <div class="col-md-3"><label class="fw-bold mb-0">Estimasi Jam Datang (ETA)</label><input type="time" name="eta" class="form-control form-control-sm"></div>
                    <div class="col-md-3"><label class="fw-bold mb-0">Frekuensi Menginap</label><input type="number" name="history_stay_count" class="form-control form-control-sm" value="0"></div>
                    <div class="col-md-6"><label class="fw-bold mb-0">Keterangan Khusus / Notes</label><input type="text" name="notes" class="form-control form-control-sm" placeholder="Catatan internal Front Office..."></div>
                    <div class="col-md-4"><label class="small text-muted mb-0">Arrangement</label><select name="arrangement" class="form-select form-select-sm"><?php mysqli_data_seek($arr_opts, 0); while($o = mysqli_fetch_assoc($arr_opts)): ?><option value="<?= $o['arr_code']; ?>"><?= $o['arr_name']; ?></option><?php endwhile; ?></select></div>
                    <div class="col-md-4"><label class="small text-muted mb-0">Segment</label><select name="segment" class="form-select form-select-sm"><?php mysqli_data_seek($seg_opts, 0); while($o = mysqli_fetch_assoc($seg_opts)): ?><option value="<?= $o['seg_code']; ?>"><?= $o['seg_name']; ?></option><?php endwhile; ?></select></div>
                    <div class="col-md-4"><label class="small text-muted mb-0">Source</label><select name="source" class="form-select form-select-sm"><?php mysqli_data_seek($src_opts, 0); while($o = mysqli_fetch_assoc($src_opts)): ?><option value="<?= $o['src_code']; ?>"><?= $o['src_name']; ?></option><?php endwhile; ?></select></div>
                    <div class="col-md-6"><label class="small mb-0 fw-bold">Tanggal Check-In Rencana</label><input type="date" name="arrival_date" class="form-control form-control-sm" value="<?= date('Y-m-d'); ?>" required></div>
                    <div class="col-md-6"><label class="small mb-0 fw-bold">Tanggal Check-Out Rencana</label><input type="date" name="departure_date" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+1 day')); ?>" required></div>
                </div>
                <div class="modal-footer py-1 bg-light"><button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Batal</button><button type="submit" name="add_reservation" class="btn btn-primary btn-sm rounded-pill fw-bold">Simpan Berkas Reservasi</button></div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT DATA RESERVASI -->
<div class="modal fade" id="modalEditReservation" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-dark text-white"><h5 class="modal-title fw-bold"><i class="bi bi-pencil-square"></i> Edit Parameter Registrasi Kamar</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form action="action_handler.php" method="POST">
                <input type="hidden" name="id_reg" id="edit_id_reg">
                <input type="hidden" name="guest_id" id="edit_guest_id">
                <input type="hidden" name="old_room_number" id="edit_old_room">
                <div class="modal-body p-3 row g-2 small">
                    <div class="col-12 bg-light p-2 rounded border">
                        <label class="fw-bold text-secondary">Profil Guest Stay Terpilih</label>
                        <select name="selected_guest_id" id="edit_guest_select" class="form-select form-select-sm" required>
                            <?php mysqli_data_seek($guests_datalist, 0); while($g = mysqli_fetch_assoc($guests_datalist)): ?>
                                <option value="<?= $g['id_guest']; ?>"><?= htmlspecialchars($g['guest_name']); ?> [KTP: <?= $g['identity_number']; ?>]</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4"><label class="fw-bold mb-0">Nomor Kamar</label><select name="room_number" id="edit_room_number" class="form-select form-select-sm" required><?php mysqli_data_seek($rooms_available, 0); while($r = mysqli_fetch_assoc($rooms_available)): ?><option value="<?= $r['room_number']; ?>">Room <?= $r['room_number']; ?></option><?php endwhile; ?></select></div>
                    <div class="col-md-4"><label class="fw-bold mb-0">Harga Kamar Applied (Rate)</label><input type="number" name="room_rate" id="edit_room_rate" class="form-control form-control-sm" required></div>
                    <div class="col-md-4"><label class="fw-bold mb-0">Frekuensi Menginap</label><input type="number" name="history_stay_count" id="edit_history_stay" class="form-control form-control-sm"></div>
                    <div class="col-md-3"><label class="fw-bold mb-0">ETA</label><input type="time" name="eta" id="edit_eta" class="form-control form-control-sm"></div>
                    <div class="col-md-9"><label class="fw-bold mb-0">Keterangan / Notes</label><input type="text" name="notes" id="edit_notes" class="form-control form-control-sm"></div>
                    <div class="col-md-4"><label class="small mb-0">Arrangement</label><select name="arrangement" id="edit_arrangement" class="form-select form-select-sm"><?php mysqli_data_seek($arr_opts, 0); while($o = mysqli_fetch_assoc($arr_opts)): ?><option value="<?= $o['arr_code']; ?>"><?= $o['arr_name']; ?></option><?php endwhile; ?></select></div>
                    <div class="col-md-4"><label class="small mb-0">Segment</label><select name="segment" id="edit_segment" class="form-select form-select-sm"><?php mysqli_data_seek($seg_opts, 0); while($o = mysqli_fetch_assoc($seg_opts)): ?><option value="<?= $o['seg_code']; ?>"><?= $o['seg_name']; ?></option><?php endwhile; ?></select></div>
                    <div class="col-md-4"><label class="small mb-0">Source</label><select name="source" id="edit_source" class="form-select form-select-sm"><?php mysqli_data_seek($src_opts, 0); while($o = mysqli_fetch_assoc($src_opts)): ?><option value="<?= $o['src_code']; ?>"><?= $o['src_name']; ?></option><?php endwhile; ?></select></div>
                    <div class="col-md-6"><label class="small mb-0">Tanggal Check-In</label><input type="date" name="arrival_date" id="edit_arrival" class="form-control form-control-sm" required></div>
                    <div class="col-md-6"><label class="small mb-0">Tanggal Check-Out</label><input type="date" name="departure_date" id="edit_departure" class="form-control form-control-sm" required></div>
                </div>
                <div class="modal-footer py-1 bg-light"><button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Batal</button><button type="submit" name="update_reservation" class="btn btn-dark btn-sm rounded-pill fw-bold">Simpan Perubahan</button></div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL ACTION: +DEP -->
<div class="modal fade" id="modalDeposit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg border-0 rounded-3">
            <div class="modal-header py-2 bg-primary text-white"><h6 class="modal-title fw-bold"><i class="bi bi-cash"></i> Tambah Deposit Kas</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form action="action_handler.php" method="POST">
                <input type="hidden" name="registration_id" id="dep_reg_id">
                <div class="modal-body p-3 row g-2 small">
                    <div class="col-12"><label class="fw-bold mb-0">Nominal Dana Tambahan (Rp)</label><input type="number" name="deposit_amount" class="form-control form-control-sm" required value="0"></div>
                    <div class="col-12"><label class="fw-bold mb-0">Metode Pembayaran</label><select name="payment_method" class="form-select form-select-sm"><option value="Cash">Cash / Tunai</option><option value="Debit Card">Debit Card</option><option value="QRIS">QRIS Standar</option></select></div>
                    <div class="col-12"><label class="fw-bold mb-0">Keterangan Catatan</label><input type="text" name="deposit_notes" class="form-control form-control-sm" required placeholder="Contoh: Tambahan Deposit Kamar"></div>
                </div>
                <div class="modal-footer py-1"><button type="submit" name="add_deposit" class="btn btn-sm btn-primary w-100 fw-bold">Simpan & Cetak Slip</button></div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL ACTION: MOVE ROOM -->
<div class="modal fade" id="modalMoveRoom" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg border-0 rounded-3">
            <div class="modal-header py-2 bg-warning text-dark"><h6 class="modal-title fw-bold"><i class="bi bi-arrow-left-right"></i> Prosedur Pindah Kamar</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="action_handler.php" method="POST">
                <input type="hidden" name="registration_id" id="move_reg_id">
                <input type="hidden" name="old_room_number" id="move_old_room">
                <div class="modal-body p-3 row g-2 small">
                    <div class="col-12"><label class="fw-bold mb-0">Kamar Saat Ini</label><input type="text" id="display_old_room" class="form-control form-control-sm bg-light text-danger fw-bold" readonly></div>
                    <div class="col-12"><label class="fw-bold mb-0">Pindah Ke Kamar Baru</label><select name="new_room_number" class="form-select form-select-sm" required><option value="">-- Pilih Kamar Kosong --</option><?php mysqli_data_seek($rooms_available, 0); while($r = mysqli_fetch_assoc($rooms_available)): ?><option value="<?= $r['room_number']; ?>">Kamar #<?= $r['room_number']; ?> (<?= $r['room_type']; ?>)</option><?php endwhile; ?></select></div>
                    <div class="col-12"><label class="fw-bold mb-0">Alasan Mutasi Kamar</label><input type="text" name="move_reason" class="form-control form-control-sm" required placeholder="Contoh: AC Bocor / Pindah tipe kamar"></div>
                </div>
                <div class="modal-footer py-1"><button type="submit" name="move_room_action" class="btn btn-sm btn-warning w-100 text-dark fw-bold shadow-sm">Eksekusi Pindah Kamar</button></div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL PINTASAN CEPAT KARTU TAMU BARU -->
<div class="modal fade" id="modalFastAddGuest" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow rounded-3 border-0">
            <div class="modal-header py-2 bg-success text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-person-plus"></i> Tambah Master Tamu Cepat</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-toggle="modal" data-bs-target="#modalReservation"></button>
            </div>
            <div class="modal-body p-3 small">
                <form id="formFastGuest" onsubmit="return false;">
                    <div class="mb-2">
                        <label class="fw-bold mb-0">Nama Lengkap Tamu <span class="text-danger">*</span></label>
                        <input type="text" id="fast_guest_name" class="form-control form-control-sm" required placeholder="Sesuai KTP / Passport">
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold mb-0">Nomor Identitas (NIK/KTP)</label>
                        <input type="text" id="fast_identity" class="form-control form-control-sm" placeholder="Nomor KTP...">
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold mb-0">Nomor Telepon / HP</label>
                        <input type="text" id="fast_phone" class="form-control form-control-sm" placeholder="Contoh: 0812...">
                    </div>
                    <button type="button" id="btnSubmitFastGuest" class="btn btn-success btn-sm w-100 fw-bold mt-2 rounded-pill">Masukkan Ke Form Reservasi</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL VIEW PHOTO -->
<div class="modal fade" id="viewPhotoModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-body p-1 bg-dark text-center rounded"><img src="" id="bigPhotoPreview" class="img-fluid rounded" style="max-height:80vh;"></div></div></div></div>
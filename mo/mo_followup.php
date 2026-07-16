// mo_followup.js (atau di tag <script> di mo_followup.php)

$(document).ready(function() {
    // Fungsi untuk menambah baris spare part baru secara dinamis
    $('#add-part-row').click(function() {
        var newRow = `
            <div class="row mb-2 part-row">
                <div class="col-md-5">
                    <select class="form-control spare-part-select" name="spare_part_id[]" required>
                        <option value="">-- Pilih Spare Part --</option>
                        <?php 
                        // Ambil daftar spare part dari DB
                        $parts_res = $conn->query("SELECT id, name FROM spare_parts ORDER BY name");
                        while($part = $parts_res->fetch_assoc()) {
                            echo '<option value="'.$part['id'].'">'.$part['name'].'</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="number" class="form-control quantity-input" name="quantity[]" placeholder="Qty" min="1" required>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control unit-price-input" name="unit_price[]" placeholder="Harga Satuan" readonly>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm remove-part-row">X</button>
                </div>
            </div>
        `;
        $('#spare-parts-container').append(newRow);
    });

    // Delegasi event listener untuk elemen dinamis
    $('#spare-parts-container').on('change', '.spare-part-select', function() {
        var partId = $(this).val();
        var $row = $(this).closest('.part-row');
        var $priceInput = $row.find('.unit-price-input');
        
        if (partId) {
            // Panggil AJAX
            $.get('get_spare_part_price.php', { part_id: partId }, function(data) {
                if (data.success) {
                    $priceInput.val(data.price);
                } else {
                    $priceInput.val('0.00');
                    alert('Gagal mendapatkan harga: ' + data.message);
                }
            }, 'json');
        } else {
            $priceInput.val('');
        }
    });
    
    // Hapus baris spare part
    $('#spare-parts-container').on('click', '.remove-part-row', function() {
        $(this).closest('.part-row').remove();
    });
});
// Pastikan user adalah 'engineering' dan MO berstatus 'approved'
if ($_SESSION['user_level'] == 'engineering' && $current_mo_status == 'approved') {
    // FORM untuk Staff Incharge, Date Start, Date Estimate Finish
    // Ketika form ini disubmit, status MO diubah menjadi 'progress'
    $sql_update = "UPDATE maintenance_orders SET 
        staff_incharge_id = ?, 
        date_start = ?, 
        date_estimate_finish = ?,
        status = 'progress' 
        WHERE id = ?";
    // Jalankan prepare, bind, dan execute...
}

// Jika MO berstatus 'progress', Engineering bisa mengisi detail perbaikan dan spare part
if ($_SESSION['user_level'] == 'engineering' && $current_mo_status == 'progress') {
    // 1. FORM untuk Input Spare Part (bisa lebih dari 1 item, perlu AJAX atau JS untuk menambah baris)
    
    // Logika PHP untuk menyimpan Spare Part:
    // LOOP melalui input spare part
    // $sql_part = "INSERT INTO mo_spare_parts (mo_id, spare_part_id, quantity_used, unit_price_used) VALUES (?, ?, ?, ?)";
    // Jalankan prepare, bind, dan execute untuk setiap item spare part.

    // 2. FORM untuk Penyelesaian Pekerjaan (Mengisi Date Finish dan Remark)
    
    // Ketika form penyelesaian disubmit:
    $sql_finish = "UPDATE maintenance_orders SET 
        repair_details = ?, 
        date_finish = ?, 
        remark = ?,
        status = 'done' 
        WHERE id = ?";
    // Jalankan prepare, bind, dan execute...
}

SELECT
    mo.id AS MO_ID,
    mo.location AS Lokasi,
    mo.date_created AS Tanggal_Request,
    u_req.full_name AS Requester,
    u_eng.full_name AS Staff_Incharge,
    SUM(msp.quantity_used * msp.unit_price_used) AS Total_Biaya_Spare_Part,
    mo.status AS Status_MO
FROM 
    maintenance_orders mo
LEFT JOIN 
    users u_req ON mo.order_by_user_id = u_req.id
LEFT JOIN 
    staff_engineering se ON mo.staff_incharge_id = se.id
LEFT JOIN
    users u_eng ON se.user_id = u_eng.id
LEFT JOIN 
    mo_spare_parts msp ON mo.id = msp.mo_id
GROUP BY 
    mo.id, mo.location, mo.date_created, u_req.full_name, u_eng.full_name, mo.status
ORDER BY 
    mo.date_created DESC;
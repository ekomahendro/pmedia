<?php
require_once '../../config.php';
check_login();

// ------------------------------------------------------------------------------------------
// AUTOMATION GUEST NO-SHOW ENGINE
// Mengecek semua data status 'arrival' (reservasi) yang rencana tanggal datangnya (arrival_date) 
// sudah lewat hari ini namun belum melakukan Check-In, maka status otomatis dialihkan ke 'noshow'.
// ------------------------------------------------------------------------------------------
$today_date = date('Y-m-d');
mysqli_query($conn, "UPDATE htl_registrations SET status = 'noshow' WHERE status = 'arrival' AND arrival_date < '$today_date'");


// INTERCEPTOR AJAX UNTUK MEMBUAT KARTU TAMU BARU SECARA REALTIME
if (isset($_POST['ajax_add_guest'])) {
    header('Content-Type: application/json');
    $name     = mysqli_real_escape_string($conn, $_POST['guest_name']);
    $identity = mysqli_real_escape_string($conn, $_POST['identity_number']);
    $phone    = mysqli_real_escape_string($conn, $_POST['phone_number']);
    
    $query = "INSERT INTO htl_guests (guest_name, identity_number, phone_number) VALUES ('$name', '$identity', '$phone')";
    if (mysqli_query($conn, $query)) {
        $new_id = mysqli_insert_id($conn);
        echo json_encode(['success' => true, 'id_guest' => $new_id]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
    exit;
}

// HANDLING ACTION VIA GET (CHECKIN, CHECKOUT, CANCEL, REACTIVATE)
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id_reg = intval($_GET['id'] ?? 0);
    $room   = mysqli_real_escape_string($conn, $_GET['room'] ?? '');

    if ($action === 'checkin') {
        $q = "UPDATE htl_registrations SET status = 'inhouse', actual_checkin = NOW() WHERE id_reg = $id_reg";
        if (mysqli_query($conn, $q)) {
            header("Location: index.php?msg=Tamu berhasil Check-In masuk ke kamar #$room&msg_type=success");
        } else {
            header("Location: index.php?msg=Gagal Check-In: " . mysqli_error($conn) . "&msg_type=danger");
        }
        exit;
    }

    if ($action === 'checkout') {
        $q = "UPDATE htl_registrations SET status = 'departure', actual_checkout = NOW() WHERE id_reg = $id_reg";
        if (mysqli_query($conn, $q)) {
            header("Location: index.php?msg=Tamu kamar #$room telah berhasil Check-Out&msg_type=success");
        } else {
            header("Location: index.php?msg=Gagal Check-Out: " . mysqli_error($conn) . "&msg_type=danger");
        }
        exit;
    }

    if ($action === 'cancel') {
        $q = "UPDATE htl_registrations SET status = 'cancel' WHERE id_reg = $id_reg";
        if (mysqli_query($conn, $q)) {
            header("Location: index.php?msg=Reservasi berhasil dibatalkan dan masuk ke Cancel List&msg_type=warning");
        } else {
            header("Location: index.php?msg=Gagal membatalkan reservasi: " . mysqli_error($conn) . "&msg_type=danger");
        }
        exit;
    }

    // FITUR BARU: Logic Reactivate Reservation Engine
    if ($action === 'reactivate') {
        // Kembalikan status dari cancel menjadi arrival (kembali masuk list booking aktif)
        $q = "UPDATE htl_registrations SET status = 'arrival' WHERE id_reg = $id_reg";
        if (mysqli_query($conn, $q)) {
            header("Location: index.php?msg=Reservasi Berhasil Diaktifkan Kembali! Silakan cek tab Arrival List.&msg_type=success");
        } else {
            header("Location: index.php?msg=Gagal mengaktifkan kembali reservasi: " . mysqli_error($conn) . "&msg_type=danger");
        }
        exit;
    }
}

// HANDLING POST ACTIONS (ADD RESERVATION, UPDATE, DEPOSIT, MOVE ROOM)
if (isset($_POST['add_reservation'])) {
    $guest_id    = intval($_POST['selected_guest_id']);
    $room_num    = mysqli_real_escape_string($conn, $_POST['room_number']);
    $rate        = doubleval($_POST['room_rate']);
    $deposit     = doubleval($_POST['deposit']);
    $eta         = mysqli_real_escape_string($conn, $_POST['eta']);
    $notes       = mysqli_real_escape_string($conn, $_POST['notes']);
    $arr         = mysqli_real_escape_string($conn, $_POST['arrangement']);
    $seg         = mysqli_real_escape_string($conn, $_POST['segment']);
    $src         = mysqli_real_escape_string($conn, $_POST['source']);
    $arrival     = mysqli_real_escape_string($conn, $_POST['arrival_date']);
    $departure   = mysqli_real_escape_string($conn, $_POST['departure_date']);
    $b_code      = "BK" . date('mdHis');

    $q_reg = "INSERT INTO htl_registrations (booking_code, id_guest, room_number, room_rate, eta, notes, arrangement, segment, source, arrival_date, departure_date, status, history_stay_count) 
              VALUES ('$b_code', $guest_id, '$room_num', $rate, '$eta', '$notes', '$arr', '$seg', '$src', '$arrival', '$departure', 'arrival', 0)";
    
    if (mysqli_query($conn, $q_reg)) {
        $new_reg_id = mysqli_insert_id($conn);
        if ($deposit > 0) {
            mysqli_query($conn, "INSERT INTO htl_deposits (registration_id, amount, payment_method, deposit_notes, received_at) VALUES ($new_reg_id, $deposit, 'Cash', 'Initial Booking Deposit', NOW())");
        }
        header("Location: index.php?msg=Reservasi Baru atas nama kode $b_code Berhasil Disimpan&msg_type=success");
    } else {
        header("Location: index.php?msg=Gagal Simpan Reservasi: " . mysqli_error($conn) . "&msg_type=danger");
    }
    exit;
}

if (isset($_POST['update_reservation'])) {
    $id_reg    = intval($_POST['id_reg']);
    $guest_id  = intval($_POST['selected_guest_id']);
    $room_num  = mysqli_real_escape_string($_POST['room_number']);
    $rate      = doubleval($_POST['room_rate']);
    $eta       = mysqli_real_escape_string($_POST['eta']);
    $notes     = mysqli_real_escape_string($_POST['notes']);
    $arr       = mysqli_real_escape_string($_POST['arrangement']);
    $seg       = mysqli_real_escape_string($_POST['segment']);
    $src       = mysqli_real_escape_string($_POST['source']);
    $arrival   = mysqli_real_escape_string($_POST['arrival_date']);
    $departure = mysqli_real_escape_string($_POST['departure_date']);

    $q = "UPDATE htl_registrations SET id_guest = $guest_id, room_number = '$room_num', room_rate = $rate, eta = '$eta', notes = '$notes', arrangement = '$arr', segment = '$seg', source = '$src', arrival_date = '$arrival', departure_date = '$departure' WHERE id_reg = $id_reg";
    if (mysqli_query($conn, $q)) {
        header("Location: index.php?msg=Data Reservasi Berhasil Diperbarui&msg_type=success");
    } else {
        header("Location: index.php?msg=Gagal Update: " . mysqli_error($conn) . "&msg_type=danger");
    }
    exit;
}

if (isset($_POST['add_deposit'])) {
    $reg_id = intval($_POST['registration_id']);
    $amount = doubleval($_POST['deposit_amount']);
    $method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $notes  = mysqli_real_escape_string($conn, $_POST['deposit_notes']);

    $q = "INSERT INTO htl_deposits (registration_id, amount, payment_method, deposit_notes, received_at) VALUES ($reg_id, $amount, '$method', '$notes', NOW())";
    if (mysqli_query($conn, $q)) {
        header("Location: index.php?msg=Tambahan Deposit Rp " . number_format($amount, 0, ',', '.') . " Berhasil Masuk Kas&msg_type=success");
    } else {
        header("Location: index.php?msg=Gagal Input Deposit&msg_type=danger");
    }
    exit;
}

if (isset($_POST['move_room_action'])) {
    $reg_id   = intval($_POST['registration_id']);
    $old_rm   = mysqli_real_escape_string($conn, $_POST['old_room_number']);
    $new_rm   = mysqli_real_escape_string($conn, $_POST['new_room_number']);
    $reason   = mysqli_real_escape_string($conn, $_POST['move_reason']);

    // Update Kamar Registrasi Utama
    $q1 = "UPDATE htl_registrations SET room_number = '$new_rm' WHERE id_reg = $reg_id";
    // Catat ke Log Mutasi Kamar
    $q2 = "INSERT INTO htl_room_moves (registration_id, from_room, to_room, reason, moved_at, moved_by) VALUES ($reg_id, '$old_rm', '$new_rm', '$reason', NOW(), 'FrontOffice')";
    
    if (mysqli_query($conn, $q1) && mysqli_query($conn, $q2)) {
        header("Location: index.php?msg=Sukses Mutasi Kamar Tamu dari #$old_rm ke Kamar #$new_rm&msg_type=success");
    } else {
        header("Location: index.php?msg=Gagal Proses Pindah Kamar&msg_type=danger");
    }
    exit;
}
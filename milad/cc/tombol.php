<?php 
// Contoh: tombol.php?id=1 (Sesuaikan ID kelompok saat login/buka halaman)
$kelompok_id = $_GET['id'] ?? 1; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tombol Peserta</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-slate-900 text-white min-h-screen flex flex-col justify-between p-6 select-none">

    <div class="text-center py-4">
        <h2 class="text-xl text-slate-400">Halaman Tombol</h2>
        <h1 id="nama-kelompok" class="text-3xl font-bold text-amber-400">Loading...</h1>
    </div>

    <div class="flex-grow flex items-center justify-center">
        <button id="btn-buzzer" onclick="tekanBuzzer()" class="w-64 h-64 bg-red-600 hover:bg-red-500 active:scale-95 rounded-full shadow-[0_0_50px_rgba(220,38,38,0.5)] border-8 border-red-800 text-white text-4xl font-black tracking-wider transition-all duration-150 focus:outline-none">
            TEKAN<br>SINI!
        </button>
    </div>

    <div id="status-notif" class="text-center text-lg py-4 font-semibold text-slate-500">
        Siap-siap!
    </div>

    <script>
        const kelompokId = <?= $kelompok_id ?>;

        function tekanBuzzer() {
            $.post('action.php?action=press_buzzer', { kelompok_id: kelompokId }, function(response) {
                let res = JSON.parse(response);
                if(res.status === 'success') {
                    navigator.vibrate([200]); // Getar HP jika sukses
                    $('#status-notif').text("ANDA DULUAN! SILAHKAN JAWAB.").removeClass().addClass('text-center text-lg py-4 font-semibold text-emerald-400');
                } else {
                    $('#status-notif').text("Keduluan kelompok lain!").removeClass().addClass('text-center text-lg py-4 font-semibold text-rose-400');
                }
            });
        }

        // Sinkronisasi status tombol (kunci tombol jika layar sedang me-lock pemenang)
        function syncTombol() {
            $.getJSON('action.php?action=get_status', function(data) {
                // Set nama kelompok saat pertama kali load
                let infoKelompok = data.skor.find(x => x.kelompok_id == kelompokId);
                if(infoKelompok) $('#nama-kelompok').text(infoKelompok.nama_kelompok);

                if (data.buzzer.is_locked == 1) {
                    if (data.buzzer.kelompok_id != kelompokId) {
                        $('#btn-buzzer').prop('disabled', true).addClass('opacity-40 bg-slate-700 border-slate-800 shadow-none');
                        $('#status-notif').text(`Kelompok ${data.buzzer.nama_kelompok} berhasil menekan duluan.`);
                    }
                } else {
                    $('#btn-buzzer').prop('disabled', false).removeClass('opacity-40 bg-slate-700 border-slate-800 shadow-none').addClass('bg-red-600');
                    $('#status-notif').text("Buzzer Siap! Dengarkan pertanyaan...").removeClass().addClass('text-center text-lg py-4 font-semibold text-slate-400');
                }
            });
        }

        setInterval(syncTombol, 400);
    </script>
</body>
</html>
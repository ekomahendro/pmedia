<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Cerdas Cermat - Gebyar Milad</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-slate-900 text-white min-h-screen flex flex-col justify-between p-6">

    <header class="text-center py-4 border-b border-slate-700">
        <h1 class="text-4xl font-extrabold text-amber-400 tracking-wider">CERDAS CERMAT GEBYAR MILAD</h1>
        <p class="text-slate-400 text-lg">Live Buzzer & Score Board</p>
    </header>

    <main class="flex-grow flex flex-col items-center justify-center my-8">
        <div id="buzzer-box" class="w-full max-w-2xl bg-slate-800 rounded-2xl p-8 border-4 border-slate-700 text-center shadow-2xl transition-all duration-300">
            <h2 class="text-2xl text-slate-400 mb-4 font-semibold uppercase tracking-wide">Pencet Duluan:</h2>
            
            <div id="buzzer-idle" class="text-5xl font-bold text-slate-500 animate-pulse py-10">
                MENUNGGU TOMBOL...
            </div>

            <div id="buzzer-active" class="hidden">
                <img id="pemenang-foto" src="" alt="Foto" class="w-40 h-40 object-cover rounded-full mx-auto border-4 border-amber-400 shadow-lg mb-4">
                <div id="pemenang-nama" class="text-6xl font-black text-amber-400 tracking-wide uppercase">Nama Kelompok</div>
                <div class="text-emerald-400 font-bold mt-2 text-xl animate-bounce">Silahkan Menjawab!</div>
            </div>
        </div>
        
        <div class="mt-6 flex gap-4">
            <button onclick="resetBuzzer()" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-6 py-3 rounded-lg text-lg shadow-lg">
                RESET TOMBOL (Pertanyaan Baru)
            </button>
        </div>
    </main>

    <section class="grid grid-cols-3 gap-6 w-full max-w-6xl mx-auto mb-6">
        <div id="skor-container" class="contents"></div>
    </section>

    <script>
        let currentBuzzerStatus = 0;

        function checkStatus() {
            $.getJSON('action.php?action=get_status', function(data) {
                // 1. Handle Tampilan Buzzer
                if (data.buzzer.is_locked == 1) {
                    if (currentBuzzerStatus == 0) {
                        // Mainkan efek suara di sini jika perlu
                        $('#buzzer-idle').addClass('hidden');
                        $('#pemenang-foto').attr('src', 'uploads/' + data.buzzer.foto);
                        $('#pemenang-nama').text(data.buzzer.nama_kelompok);
                        $('#buzzer-active').removeClass('hidden');
                        $('#buzzer-box').addClass('border-amber-400 bg-slate-800/80 scale-105');
                        currentBuzzerStatus = 1;
                    }
                } else {
                    $('#buzzer-active').addClass('hidden');
                    $('#buzzer-idle').removeClass('hidden');
                    $('#buzzer-box').removeClass('border-amber-400 bg-slate-800/80 scale-105').addClass('border-slate-700');
                    currentBuzzerStatus = 0;
                }

                // 2. Handle Tampilan Skor
                let skorHtml = '';
                data.skor.forEach(function(k) {
                    skorHtml += `
                        <div class="bg-slate-800 p-4 rounded-xl border border-slate-700 flex items-center justify-between shadow-lg">
                            <div class="flex items-center gap-4">
                                <img src="uploads/${k.foto}" class="w-16 h-16 rounded-full object-cover border border-slate-600">
                                <div>
                                    <h3 class="text-xl font-bold">${k.nama_kelompok}</h3>
                                    <div class="flex gap-2 mt-1">
                                        <button onclick="updateSkor(${k.kelompok_id}, 100)" class="bg-emerald-600 text-xs px-2 py-1 rounded">+100</button>
                                        <button onclick="updateSkor(${k.kelompok_id}, -50)" class="bg-rose-600 text-xs px-2 py-1 rounded">-50</button>
                                    </div>
                                </div>
                            </div>
                            <div class="text-4xl font-black text-amber-400">${k.skor}</div>
                        </div>
                    `;
                });
                $('#skor-container').html(skorHtml);
            });
        }

        function resetBuzzer() {
            $.post('action.php?action=reset_buzzer');
        }

        function updateSkor(id, poin) {
            $.post('action.php?action=update_skor', { kelompok_id: id, poin: poin });
        }

        // Jalankan polling setiap 300ms untuk responsivitas optimal
        setInterval(checkStatus, 300);
    </script>
</body>
</html>
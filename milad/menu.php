<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Milad Mualaf</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col items-center p-6">
    <div class="w-full max-w-4xl absolute top-4 left-4 sm:top-6 sm:left-6 md:static md:mb-4 flex justify-start">
        <a href="https://grandistanarama.com/apps" class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-100 border border-slate-200 text-slate-600 hover:text-slate-800 text-sm font-semibold rounded-xl shadow-sm transition-all duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Hub
        </a>
    </div>
    <div class="max-w-4xl w-full mt-10">
        <div class="text-center mb-8 md:mb-12 mt-16 md:mt-4 flex flex-col items-center">
            <img src="logo.png" alt="Logo Grand Istana Rama" class="h-16 md:h-20 w-auto mb-4 object-contain">
            
            <h1 class="text-3xl md:text-4xl font-extrabold text-slate-800 mb-3 tracking-tight">
                Milad Mualaf XV
            </h1>
            <p class="text-slate-500 text-sm md:text-base">
                Silakan pilih menu untuk melanjutkan
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <!-- ADMIN -->
            <div class="glass-card border border-slate-200 rounded-3xl p-6 flex flex-col items-center text-center">
                <h2 class="font-bold text-slate-800 mb-4">Admin</h2>
                <div class="flex gap-2">
                    <a href="https://pmediaku.my.id/milad/index.php" target="_blank" class="px-4 py-2 bg-emerald-600 text-white text-xs font-semibold rounded-lg hover:bg-emerald-700 transition-colors">Buka</a>
                </div>
            </div>
            <!-- Daftar mandiri -->
            <div class="glass-card border border-slate-200 rounded-3xl p-6 flex flex-col items-center text-center">
                <h2 class="font-bold text-slate-800 mb-4">Daftar Mandiri</h2>
                <div class="flex gap-2">
                    <a href="https://pmediaku.my.id/milad/daftar_mandiri.php" class="px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700 transition-colors">Buka</a>
                </div>
            </div>
            <!-- Rekap Daftar -->
            <div class="glass-card border border-slate-200 rounded-3xl p-6 flex flex-col items-center text-center">
                <h2 class="font-bold text-slate-800 mb-4">Rekap Pendaftaran</h2>
                <div class="flex gap-2">
                    <a href="https://pmediaku.my.id/milad/rekap-peserta.php" class="px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700 transition-colors">Buka</a>
                </div>
            </div>
            <!-- Soal CC -->
            <div class="glass-card border border-slate-200 rounded-3xl p-6 flex flex-col items-center text-center">
                <h2 class="font-bold text-slate-800 mb-4">Soal CC</h2>
                <div class="flex gap-2">
                    <a href="https://pmediaku.my.id/milad/cc/soal.php" class="px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700 transition-colors">Buka</a>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Password untuk SOP
        function checkPasswordSOP() {
            const pass = prompt("Masukkan password untuk mengakses SOP:");
            if (pass === "Girh2025") {
                window.location.href = "https://drive.google.com/drive/folders/1kbT1W5tUt_J3V9wkZFvtBPVFN30C9B9N";
            } else if (pass !== null) {
                alert("Password salah!");
            }
        }

        // Password untuk Task Force
        function checkPasswordTaskForce() {
            const pass = prompt("Masukkan password untuk mengakses Task Force:");
            if (pass === "Girh2025") {
                window.open("https://drive.google.com/drive/folders/1jDu8gd7LCUd2QGVGcwJHHZG2mZO85OOA", "_blank");
            } else if (pass !== null) {
                alert("Password salah!");
            }
        }

        function shareLink(title, url) {
            if (navigator.share) {
                navigator.share({ title: title, url: url }).catch(console.error);
            } else {
                navigator.clipboard.writeText(url);
                alert('Link ' + title + ' berhasil disalin!');
            }
        }
    </script>
</body>
</html>
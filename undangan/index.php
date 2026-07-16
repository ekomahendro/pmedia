<?php include 'koneksi.php'; 

$conn = mysqli_connect($host, $user, $pass, $db);

// Ambil data dari tabel settings (asumsi data id=1 adalah data utama)
// Jika database belum siap, kode ini akan menggunakan data fallback (default)
$query = mysqli_query($conn, "SELECT * FROM settings WHERE id=1");
$data = mysqli_fetch_assoc($query);

// Data Default jika database kosong
$groom = $data['groom_name'] ?? "Cowok";
$bride = $data['bride_name'] ?? "Cewek";
$tgl_acara = $data['event_date'] ?? "2026-12-31 09:00:00";
$theme_color = $data['theme_color'] ?? "#d4af37";
$bank_info = $data['bank_info'] ?? "BCA - 12345678 a/n ";
$map_iframe = $data['map_iframe'] ?? '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3944.1234!2d115.1234!3d-8.1234!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zOMKwMDcnMTIuNCJTIDExNcKwMDcnMTIuNCJF!5e0!3m2!1sid!2sid!4v123456789" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Undangan Pernikahan <?= $groom ?> & <?= $bride ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        :root { --primary: <?= $theme_color ?>; }
        body { font-family: 'Montserrat', sans-serif; overflow-x: hidden; }
        .font-wedding { font-family: 'Great Vibes', cursive; }
        .bg-gold { background-color: var(--primary); }
        .text-gold { color: var(--primary); }
        .border-gold { border-color: var(--primary); }
        
        /* Animasi Kustom */
        .reveal { opacity: 0; transition: all 1s ease; }
        .reveal.active { opacity: 1; transform: translateY(0); }
        .floating { animation: floating 3s ease-in-out infinite; }
        @keyframes floating {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
    </style>
</head>
<body class="bg-stone-50 text-stone-800">

    <div id="gatekeeper" class="fixed inset-0 z-[100] bg-stone-900 flex items-center justify-center text-white transition-all duration-1000">
        <div class="text-center p-6 animate__animated animate__fadeIn">
            <div class="mb-6 floating">
                <span class="text-6xl text-gold">💍</span>
            </div>
            <h2 class="font-wedding text-6xl mb-4 text-gold"><?= $groom ?> & <?= $bride ?></h2>
            <p class="mb-2 tracking-widest uppercase text-sm">Kepada Bapak/Ibu/Saudara/i:</p>
            <h3 class="text-2xl font-bold mb-8 uppercase" id="tamu-nama">Nama Tamu</h3>
            <button onclick="bukaUndangan()" class="bg-gold px-10 py-4 rounded-full font-bold hover:scale-110 transition-transform shadow-lg">
                Buka Undangan
            </button>
        </div>
    </div>

    <div id="main-content" class="hidden opacity-0">
        
        <section class="relative h-screen flex items-center justify-center bg-stone-200 overflow-hidden">
            <div class="absolute inset-0 z-0">
                <img src="https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=1920" class="w-full h-full object-cover opacity-40">
            </div>
            <div class="relative z-10 text-center px-4">
                <p class="uppercase tracking-[0.3em] mb-4 animate__animated animate__fadeInDown">The Wedding Of</p>
                <h1 class="font-wedding text-7xl md:text-9xl text-gold mb-6 animate__animated animate__zoomIn"><?= $groom ?> & <?= $bride ?></h1>
                <p class="text-xl font-light mb-8 italic"><?= date('l, d F Y', strtotime($tgl_acara)) ?></p>
                
                <div id="timer" class="flex justify-center gap-4 md:gap-8">
                    <div class="bg-white/80 p-4 rounded-lg min-w-[70px] shadow-sm">
                        <span id="days" class="block text-3xl font-bold">00</span>
                        <small class="uppercase text-[10px]">Hari</small>
                    </div>
                    <div class="bg-white/80 p-4 rounded-lg min-w-[70px] shadow-sm">
                        <span id="hours" class="block text-3xl font-bold">00</span>
                        <small class="uppercase text-[10px]">Jam</small>
                    </div>
                    <div class="bg-white/80 p-4 rounded-lg min-w-[70px] shadow-sm">
                        <span id="minutes" class="block text-3xl font-bold">00</span>
                        <small class="uppercase text-[10px]">Menit</small>
                    </div>
                    <div class="bg-white/80 p-4 rounded-lg min-w-[70px] shadow-sm">
                        <span id="seconds" class="block text-3xl font-bold">00</span>
                        <small class="uppercase text-[10px]">Detik</small>
                    </div>
                </div>
            </div>
        </section>
        <section class="py-20 px-6 text-center bg-white reveal">
            <div class="max-w-3xl mx-auto">
                <h2 class="text-2xl text-gold mb-6 italic">QS. Ar-Rum: 21</h2>
                <p class="text-xl md:text-2xl font-serif leading-relaxed mb-4">
                    "Dan di antara tanda-tanda (kebesaran)-Nya ialah Dia menciptakan pasangan-pasangan untukmu dari jenismu sendiri, agar kamu cenderung dan merasa tenteram kepadanya..."
                </p>
                <div class="h-1 w-20 bg-gold mx-auto"></div>
            </div>
        </section>
    <div class="flex flex-col md:flex-row gap-10 items-center justify-center p-10">
    <div class="text-center">
        <img src="uploads/<?= $data['foto_pria'] ?>" class="w-48 h-48 rounded-full object-cover border-4 border-gold shadow-xl mb-4 mx-auto">
        <h3 class="font-wedding text-4xl"><?= $data['groom_name'] ?></h3>
    </div>
    <div class="font-wedding text-5xl text-gold">&</div>
    <div class="text-center">
        <img src="uploads/<?= $data['foto_wanita'] ?>" class="w-48 h-48 rounded-full object-cover border-4 border-gold shadow-xl mb-4 mx-auto">
        <h3 class="font-wedding text-4xl"><?= $data['bride_name'] ?></h3>
    </div>
</div>
        <section class="py-20 px-6 max-w-5xl mx-auto text-center reveal transform translate-y-10">
            <h2 class="font-wedding text-5xl mb-10 text-gold">Lokasi Acara</h2>
            <div class="bg-white p-4 rounded-2xl shadow-xl border border-stone-200 mb-8">
                <?= $map_iframe ?>
            </div>
            <a href="https://maps.google.com" target="_blank" class="inline-block border-2 border-gold text-gold px-8 py-3 rounded-full hover:bg-gold hover:text-white transition font-bold">
                Buka Google Maps
            </a>
        </section>
<section class="py-20 px-6 bg-stone-50 reveal">
    <div class="max-w-3xl mx-auto text-center">
        <h2 class="font-wedding text-5xl mb-8 text-gold">Our Love Story</h2>
        <div class="prose prose-stone mx-auto italic text-gray-600 leading-relaxed">
            <?= nl2br($data['cerita_singkat']) ?>
        </div>
    </div>
</section>

<section class="py-20 px-4 bg-white reveal">
    <h2 class="font-wedding text-5xl text-center mb-12 text-gold">Pre-Wedding Gallery</h2>
    <div class="columns-2 md:columns-4 gap-4 max-w-6xl mx-auto space-y-4">
        <?php
        $galeri_res = mysqli_query($conn, "SELECT * FROM prewedding_gallery ORDER BY id DESC");
        while($g = mysqli_fetch_assoc($galeri_res)): ?>
            <div class="break-inside-avoid rounded-xl overflow-hidden shadow-lg border border-stone-100">
                <img src="uploads/<?= $g['image_path'] ?>" class="w-full h-auto hover:scale-110 transition duration-700 ease-in-out cursor-pointer" alt="Gallery">
            </div>
        <?php endwhile; ?>
    </div>
</section>
        <section class="py-20 bg-stone-900 text-white text-center reveal transform translate-y-10">
            <h2 class="font-wedding text-5xl mb-10 text-gold">Wedding Gift</h2>
            <p class="max-w-md mx-auto mb-10 px-6 opacity-70">Doa restu Anda merupakan karunia terindah, namun jika ingin memberikan tanda kasih, silakan melalui:</p>
            <div class="bg-white/10 p-8 rounded-2xl inline-block backdrop-blur-md border border-white/20">
                <p class="mb-4 tracking-widest"><?= $bank_info ?></p>
                <button onclick="copyToClipboard('<?= $bank_info ?>')" class="text-sm bg-gold text-white px-4 py-2 rounded">Salin No. Rekening</button>
            </div>
        </section>

        <section class="py-20 px-6 max-w-3xl mx-auto reveal transform translate-y-10">
            <h2 class="font-wedding text-5xl text-center mb-10 text-gold">Ucapan & RSVP</h2>
            <form id="commentForm" action="kirim_ucapan.php" method="POST" class="space-y-4">
                <input type="text" name="nama" placeholder="Nama Lengkap" class="w-full p-4 rounded-lg border focus:ring-2 focus:ring-gold outline-none" required>
                <textarea name="pesan" rows="4" placeholder="Tulis ucapan selamat & doa..." class="w-full p-4 rounded-lg border focus:ring-2 focus:ring-gold outline-none" required></textarea>
                <select name="status" class="w-full p-4 rounded-lg border outline-none">
                    <option value="Hadir">Saya Akan Hadir</option>
                    <option value="Ragu">Masih Ragu</option>
                    <option value="Tidak">Maaf, Berhalangan</option>
                </select>
                <button type="submit" class="w-full bg-gold text-white py-4 rounded-lg font-bold shadow-lg hover:brightness-110 transition">Kirim Ucapan</button>
            </form>
        </section>

        <div class="fixed bottom-6 left-6 z-50">
            <button id="musicBtn" class="w-12 h-12 bg-white rounded-full shadow-2xl flex items-center justify-center text-xl floating border border-gold">
                🎵
            </button>
        </div>

    </div>

    <script>
        // 1. Data dari PHP ke JS
        const weddingDate = new Date("<?= $tgl_acara ?>").getTime();
        const urlParams = new URLSearchParams(window.location.search);
        document.getElementById('tamu-nama').innerText = urlParams.get('to') || "Tamu Undangan";

        // 2. Fungsi Countdown
        const countdown = setInterval(() => {
            const now = new Date().getTime();
            const distance = weddingDate - now;

            const d = Math.floor(distance / (1000 * 60 * 60 * 24));
            const h = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const s = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById("days").innerText = d < 10 ? "0" + d : d;
            document.getElementById("hours").innerText = h < 10 ? "0" + h : h;
            document.getElementById("minutes").innerText = m < 10 ? "0" + m : m;
            document.getElementById("seconds").innerText = s < 10 ? "0" + s : s;

            if (distance < 0) {
                clearInterval(countdown);
                document.getElementById("timer").innerHTML = "ACARA SEDANG BERLANGSUNG";
            }
        }, 1000);

        // 3. Buka Undangan & Play Music
        const music = new Audio('https://www.bensound.com/bensound-music/bensound-love.mp3'); // Ganti URL mp3 anda
        music.loop = true;

        function bukaUndangan() {
            const gate = document.getElementById('gatekeeper');
            const main = document.getElementById('main-content');
            
            gate.style.opacity = '0';
            gate.style.transform = 'scale(1.2)';
            
            setTimeout(() => {
                gate.classList.add('hidden');
                main.classList.remove('hidden');
                setTimeout(() => main.classList.add('opacity-100'), 50);
                music.play();
                startScrollReveal();
            }, 800);
        }

        // 4. Music Toggle
        document.getElementById('musicBtn').onclick = function() {
            if (music.paused) {
                music.play();
                this.innerText = "🎵";
            } else {
                music.pause();
                this.innerText = "🔇";
            }
        };

        // 5. Scroll Reveal Logic
        function startScrollReveal() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if(entry.isIntersecting) {
                        entry.target.classList.add('active');
                        entry.target.style.transform = "translateY(0)";
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
        }

        // 6. Copy Clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text);
            alert("Nomor rekening berhasil disalin!");
        }
    </script>
</body>
</html>
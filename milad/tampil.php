<?php
require_once 'config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    // Ambil ulang data session terbaru saat pooling AJAX dipanggil
    $display_mode = $_SESSION['display_mode'] ?? 'lomba';
    
    // =========================================================================
    // JIKA MODE AKTIF ADALAH DISPLAY STRUKTUR PANITIA
    // =========================================================================
    if ($display_mode == 'panitia') {
        $stmt = $pdo->query("SELECT nama_panitia, jabatan, foto FROM mld_panitia ORDER BY urutan ASC, id DESC");
        $list_panitia = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'istirahat'     => false,
            'is_panitia'    => true,
            'is_lcc'        => false,
            'nama_kategori' => 'SUSUNAN PANITIA GEBYAR MILAD XV',
            'data'          => $list_panitia
        ]);
        exit;
    }

    // =========================================================================
    // LOGIKA SELEKSI LOMBA CERDAS CERMAT / LCC
    // =========================================================================
    $check_lcc = $pdo->query("SELECT m.id, m.nama_mt, m.is_tampil, m.skor FROM mld_majelis m WHERE m.is_tampil > 0 ORDER BY m.is_tampil ASC")->fetchAll();
    
    if (count($check_lcc) > 0) {
        $result_data = [];
        foreach($check_lcc as $mt) {
            $stmt_member = $pdo->prepare("SELECT p.nama_peserta, p.foto FROM mld_peserta p 
                                          JOIN mld_peserta_lomba pl ON p.id = pl.peserta_id
                                          JOIN mld_kategori k ON pl.kategori_id = k.id
                                          WHERE p.majelis_id = ? AND (k.nama_kategori LIKE '%cerdas cermat%' OR k.nama_kategori LIKE '%lcc%')");
            $stmt_member->execute([$mt['id']]);
            $members = $stmt_member->fetchAll(PDO::FETCH_ASSOC);
            
            $arr_nama = []; $arr_foto = [];
            foreach($members as $m) {
                $arr_nama[] = $m['nama_peserta'];
                if (!empty($m['foto'])) $arr_foto[] = 'uploads/' . $m['foto'];
            }
            $gabungan_nama = count($arr_nama) > 0 ? implode(', ', $arr_nama) : 'Rombongan Regu';

            $result_data[] = [
                'nama_peserta' => $mt['nama_mt'],
                'nama_mt'      => $gabungan_nama,
                'is_tampil'    => $mt['is_tampil'],
                'skor'         => $mt['skor'],
                'list_foto'    => $arr_foto
            ];
        }
        $kat_lcc = $pdo->query("SELECT nama_kategori FROM mld_kategori WHERE nama_kategori LIKE '%cerdas cermat%' OR nama_kategori LIKE '%lcc%' LIMIT 1")->fetchColumn();
        echo json_encode(['istirahat' => false, 'is_lcc' => true, 'is_panitia' => false, 'nama_kategori' => $kat_lcc ?: 'CERDAS CERMAT', 'data' => $result_data]);
        exit;
    } 
    
    // =========================================================================
    // LOGIKA SELEKSI MODE BIASA / INDIVIDU
    // =========================================================================
    $stmt = $pdo->query("SELECT p.nama_peserta, m.nama_mt, p.foto, k.nama_kategori, p.is_tampil, 0 as skor 
                         FROM mld_peserta p 
                         JOIN mld_majelis m ON p.majelis_id = m.id 
                         JOIN mld_peserta_lomba pl ON p.id = pl.peserta_id
                         JOIN mld_kategori k ON pl.kategori_id = k.id 
                         WHERE p.is_tampil > 0 LIMIT 1");
    $active = $stmt->fetch();
    
    if (!$active) {
        echo json_encode(['istirahat' => true, 'is_panitia' => false, 'is_lcc' => false]);
    } else {
        $single_data = [
            'nama_peserta' => $active['nama_peserta'],
            'nama_mt'      => $active['nama_mt'],
            'is_tampil'    => $active['is_tampil'],
            'skor'         => 0,
            'list_foto'    => !empty($active['foto']) ? ['uploads/' . $active['foto']] : []
        ];
        echo json_encode(['istirahat' => false, 'is_lcc' => false, 'is_panitia' => false, 'nama_kategori' => $active['nama_kategori'], 'data' => [$single_data]]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAYAR STAGE PANGGUNG - MILAD XV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(135deg, #0f5132 0%, #198754 100%); color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; overflow: hidden; height: 100vh; }
        .title-lomba { font-size: 3rem; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; text-shadow: 3px 3px 6px rgba(0,0,0,0.4); }
        .card-regu { background: rgba(255, 255, 255, 0.95); color: #333; border: none; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.3); }
        .regu-label { font-size: 2rem; font-weight: 800; padding: 8px 25px; border-radius: 50px; color: #fff; display: inline-block; }
        .regu-1 { background: #0d6efd; } .regu-2 { background: #198754; } .regu-3 { background: #dc3545; }
        .score-display { font-size: 6.5rem; font-weight: 900; line-height: 1; font-family: 'Impact', Arial, sans-serif; }
        .score-1 { color: #0d6efd; } .score-2 { color: #198754; } .score-3 { color: #dc3545; }
        .wrapper-foto { display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; margin-top: 15px; }
        .avatar-peserta { width: 130px; height: 130px; object-fit: cover; border-radius: 15px; border: 4px solid #fff; box-shadow: 0 8px 16px rgba(0,0,0,0.2); }
        .screen-istirahat { display: flex; flex-direction: column; justify-content: center; align-items: center; height: 80vh; text-align: center; }
        .logo-milad { font-size: 5rem; font-weight: 800; color: #ffc107; text-shadow: 3px 3px 0px #000; }

        /* ==========================================================
           CSS STRUKTUR MARQUEE BARU - FIX TERGENCET & MACET
           ========================================================== */
        .marquee-container {
            height: 65vh;
            overflow: hidden;
            position: relative;
            margin-top: 20px;
            width: 100%;
        }
        .marquee-content {
            display: block; /* Menggunakan block standar agar anak elemen tidak mengkerut/gepeng */
            width: 100%;
            position: absolute;
        }
        /* Class animasi murni menggunakan top transition yang sangat stabil di semua browser */
        .marquee-animated {
            animation: scrollUpMarquee 25s linear infinite;
        }
        .marquee-container:hover .marquee-animated {
            animation-play-state: paused;
        }
        
        @keyframes scrollUpMarquee {
            0% { 
                top: 65vh; 
            }
            100% { 
                top: -100%; /* Bergerak ke atas sejauh tinggi total kontennya sendiri */
            }
        }

        .panitia-row {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 15px;
            padding: 15px 30px;
            margin-bottom: 20px; /* Jarak antar baris panitia */
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 25px;
            max-width: 700px;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
        }
        .foto-panitia {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #ffc107;
            flex-shrink: 0; /* Mencegah foto bundar gepeng */
        }
    </style>
</head>
<body>

<div class="container-fluid py-4 h-100">
    <div id="displayArea"></div>
</div>

<script>
    function perbaruiLayar() {
        fetch('tampil.php?ajax=1')
            .then(response => response.json())
            .then(res => {
                const area = document.getElementById('displayArea');
                
                if (res.istirahat) {
                    area.innerHTML = `
                        <div class="screen-istirahat">
                            <div class="logo-milad mb-2"><i class="bi bi-star-fill"></i> GEBYAR MILAD XV <i class="bi bi-star-fill"></i></div>
                            <h2 class="fw-bold tracking-wide text-white-50">MT. MUALLAF TAUFIQIYAH</h2>
                            <p class="fs-4 text-warning mt-4"><i class="bi bi-clock-history"></i> Menanti Sesi Perlombaan Berikutnya...</p>
                        </div>
                    `;
                    return;
                }

                let html = `
                    <div class="text-center mb-2">
                        <div class="title-lomba text-warning">${res.nama_kategori}</div>
                        <div class="fs-4 text-white-50 fw-semibold">Gebyar Milad MT. Muallaf Taufiqiyah XV</div>
                    </div>
                `;

                // TAMPILAN JIKA MODE PANITIA AKTIF
                if (res.is_panitia) {
                    // Berikan animasi scrollUp jika data panitia lebih dari 4 orang
                    let animationClass = (res.data && res.data.length > 4) ? 'marquee-animated' : '';
                    
                    // Jika data sedikit (<=4), taruh posisi awal di top: 10vh agar rapi di tengah panggung secara statis
                    let staticStyle = (res.data && res.data.length <= 4) ? 'style="top: 10vh;"' : '';
                    
                    html += `<div class="marquee-container"><div class="marquee-content ${animationClass}" ${staticStyle}>`;
                    if(!res.data || res.data.length === 0) {
                        html += `<div class="text-center text-white-50 fs-4 mt-5">Data susunan panitia belum diisi.</div>`;
                    } else {
                        res.data.forEach(pnt => {
                            let img_src = pnt.foto ? 'uploads/' + pnt.foto : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
                            html += `
                                <div class="panitia-row shadow-sm">
                                    <img src="${img_src}" class="foto-panitia" alt="Profil">
                                    <div class="text-start">
                                        <h2 class="fw-bold mb-1 text-warning" style="font-size: 2.2rem; letter-spacing:1px;">${pnt.nama_panitia}</h2>
                                        <h4 class="text-white-50 mb-0 fw-semibold" style="font-size: 1.4rem;"><i class="bi bi-shield-check text-warning"></i> ${pnt.jabatan}</h4>
                                    </div>
                                </div>
                            `;
                        });
                    }
                    html += `</div></div>`;
                } 
                // TAMPILAN LOGIKA LCC
                else if (res.is_lcc) {
                    html += `<div class="row g-4 px-2 mt-2">`;
                    res.data.forEach(item => {
                        let namaRegu = item.is_tampil == 1 ? 'REGU A' : (item.is_tampil == 2 ? 'REGU B' : 'REGU C');
                        let fotoHTML = '';
                        if (item.list_foto && item.list_foto.length > 0) {
                            fotoHTML += `<div class="wrapper-foto">`;
                            item.list_foto.forEach(src_foto => {
                                fotoHTML += `<img src="${src_foto}" class="avatar-peserta" onerror="this.style.display='none'">`;
                            });
                            fotoHTML += `</div>`;
                        }
                        html += `
                            <div class="col-md-4">
                                <div class="card-regu card p-4 text-center h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <span class="regu-label regu-${item.is_tampil} mb-3">${namaRegu}</span>
                                        <h2 class="fw-bold text-dark mb-1 text-uppercase">${item.nama_peserta}</h2>
                                        <p class="text-muted fw-semibold px-2" style="font-size: 1.1rem;">${item.nama_mt}</p>
                                    </div>
                                    ${fotoHTML}
                                    <div class="mt-4 border-top pt-3">
                                        <div class="text-muted small fw-bold text-uppercase mb-1 tracking-wider">Perolehan Skor</div>
                                        <div class="score-display score-${item.is_tampil}">${item.skor}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    html += `</div>`;
                } 
                // TAMPILAN MODE BIASA INDIVIDU
                else {
                    const individu = res.data[0];
                    let fotoHTML = '';
                    if (individu.list_foto && individu.list_foto.length > 0) {
                        fotoHTML += `<div class="wrapper-foto mb-3">`;
                        individu.list_foto.forEach(src_foto => {
                            fotoHTML += `<img src="${src_foto}" class="avatar-peserta" style="width:260px; height:260px; border-radius:25px;">`;
                        });
                        fotoHTML += `</div>`;
                    }
                    html += `
                        <div class="row justify-content-center mt-4">
                            <div class="col-md-7">
                                <div class="card-regu card p-5 text-center shadow-lg">
                                    <div class="text-success fw-bold text-uppercase tracking-widest small mb-2"><i class="bi bi-person-check-fill"></i> Peserta Aktif Stage</div>
                                    ${fotoHTML}
                                    <h1 class="fw-black text-dark display-4 mb-2 text-uppercase" style="font-weight:800;">${individu.nama_peserta}</h1>
                                    <h3 class="text-success fw-bold mb-0"><i class="bi bi-building"></i> Kafilah: ${individu.nama_mt}</h3>
                                </div>
                            </div>
                        </div>
                    `;
                }

                area.innerHTML = html;
            })
            .catch(err => console.log("Gagal memuat panggung: ", err));
    }

    setInterval(perbaruiLayar, 1500);
    perbaruiLayar();
</script>
</body>
</html>
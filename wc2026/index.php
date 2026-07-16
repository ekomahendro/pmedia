<?php
// --- 1. LOGIKA DB & KLASEMEN OTOMATIS ---
$dataFile = 'data.json';
if (!file_exists($dataFile)) { 
    die("Error: File data.json tidak ditemukan. Pastikan file database sudah dibuat."); 
}

$data = json_decode(file_get_contents($dataFile), true);
$groups = $data['groups'];
$matches = $data['matches'];

// Inisialisasi klasemen kosong semua grup A - L
$standings = [];
foreach ($groups as $groupLabel => $teams) {
    foreach ($teams as $team) {
        $standings[$groupLabel][$team] = ['p'=>0, 'w'=>0, 'd'=>0, 'l'=>0, 'gf'=>0, 'ga'=>0, 'gd'=>0, 'pts'=>0];
    }
}

// Kalkulasi otomatis data klasemen berdasarkan skor pertandingan yang sudah terisi
foreach ($matches as $match) {
    if ($match['phase'] === 'Group' && $match['score_home'] !== null && $match['score_away'] !== null) {
        $g = $match['group']; 
        $h = $match['home']; 
        $a = $match['away'];
        $sh = (int)$match['score_home']; 
        $sa = (int)$match['score_away'];
        
        if (isset($standings[$g][$h]) && isset($standings[$g][$a])) {
            $standings[$g][$h]['p']++; 
            $standings[$g][$a]['p']++;
            $standings[$g][$h]['gf'] += $sh; 
            $standings[$g][$h]['ga'] += $sa;
            $standings[$g][$a]['gf'] += $sa; 
            $standings[$g][$a]['ga'] += $sh;
            
            if ($sh > $sa) { 
                $standings[$g][$h]['w']++; 
                $standings[$g][$h]['pts'] += 3; 
                $standings[$g][$a]['l']++; 
            } elseif ($sh < $sa) { 
                $standings[$g][$a]['w']++; 
                $standings[$g][$a]['pts'] += 3; 
                $standings[$g][$h]['l']++; 
            } else { 
                $standings[$g][$h]['d']++; 
                $standings[$g][$a]['d']++; 
                $standings[$g][$h]['pts'] += 1; 
                $standings[$g][$a]['pts'] += 1; 
            }
            $standings[$g][$h]['gd'] = $standings[$g][$h]['gf'] - $standings[$g][$h]['ga'];
            $standings[$g][$a]['gd'] = $standings[$g][$a]['gf'] - $standings[$g][$a]['ga'];
        }
    }
}

// Urutkan peringkat klasemen berdasarkan PTS -> Selisih Gol -> Produktivitas Gol
foreach ($standings as $groupLabel => $teams) {
    uasort($standings[$groupLabel], function($a, $b) {
        if ($a['pts'] != $b['pts']) return $b['pts'] <=> $a['pts'];
        if ($a['gd'] != $b['gd']) return $b['gd'] <=> $a['gd'];
        return $b['gf'] <=> $a['gf'];
    });
}

// --- 2. LOGIKA HITUNG PENGUNJUNG (COUNTER) ---
$file_counter = "counter.txt";
if (!file_exists($file_counter)) { 
    file_put_contents($file_counter, "0"); 
}
$visitor_count = (int)file_get_contents($file_counter);
$visitor_count++;
file_put_contents($file_counter, $visitor_count);
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FIFA World Cup 2026 Dashboard</title>

    <meta name="description" content="Update skor langsung, hasil pertandingan, dan klasemen otomatis Piala Dunia 2026 lengkap 102 laga.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://<?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="Live Score & Klasemen Piala Dunia 2026">
    <meta property="og:description" content="Pantau hasil pertandingan Piala Dunia 2026 langsung dari HP Anda. Desain cepat, responsif, dan elegan.">
    <meta property="og:image" content="thumbnail.jpg">

    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0b1329">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="WorldCup2026">
    <link rel="apple-touch-icon" href="icon-192.svg">

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0b1329; color: #f8fafc; }
        /* Kustom scrollbar untuk area jadwal agar rapi di mobile */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #0f172a; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
    </style>
</head>
<body class="p-4 md:p-8">
    <div class="max-w-7xl mx-auto">
        
        <header class="text-center mb-8 border-b border-slate-800 pb-6">
            <h1 class="text-3xl md:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-500 uppercase tracking-wider">FIFA World Cup 2026 Dashboard</h1>
            <p class="text-slate-400 text-xs mt-2">Sistem Pemantauan Hasil 102 Laga & Papan Klasemen Real-Time</p>
            <p class="text-slate-400 text-xs mt-2">Media Koding 0819-9319-1161</p>
            <div class="mt-4">
                <!--<a href="update.php" class="inline-block px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-bold text-xs uppercase rounded-lg transition tracking-wider shadow-md">-->
                <!--    ⚙️ Masuk Panel Input Skor-->
                <!--</a>-->
            </div>
        </header>

        <div class="flex flex-wrap justify-center gap-2 mb-8 bg-slate-900/60 p-2 rounded-xl border border-slate-800">
            <a href="#klasemen-section" class="px-3 py-1.5 hover:bg-slate-800 text-amber-400 font-semibold rounded-lg text-xs uppercase tracking-wider">🏆 Lihat Klasemen</a>
            <a href="#grup-section" class="px-3 py-1.5 hover:bg-slate-800 font-semibold rounded-lg text-xs uppercase tracking-wider text-slate-300">⚽ Babak Grup</a>
            <a href="#knockout-section" class="px-3 py-1.5 hover:bg-slate-800 font-semibold rounded-lg text-xs uppercase tracking-wider text-orange-400">🔥 Fase Gugur</a>
        </div>

        <section id="klasemen-section" class="mb-12 scroll-mt-6">
            <h2 class="text-lg font-bold tracking-tight mb-4 text-amber-400 flex items-center gap-2">🏆 Papan Klasemen Grup Resmi (A - L)</h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php foreach ($standings as $groupLabel => $teams): ?>
                    <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-lg">
                        <div class="bg-slate-800/40 px-4 py-2 font-extrabold text-xs text-amber-400 border-b border-slate-800/60">GRUP <?=$groupLabel?></div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs whitespace-nowrap">
                                <thead class="bg-slate-950 text-slate-400 uppercase tracking-wider text-[10px]">
                                    <tr>
                                        <th class="p-2.5 text-center w-8">Pos</th>
                                        <th class="p-2.5">Negara</th>
                                        <th class="p-2.5 text-center">P</th>
                                        <th class="p-2.5 text-center text-emerald-400">M</th>
                                        <th class="p-2.5 text-center text-amber-400">S</th>
                                        <th class="p-2.5 text-center text-rose-400">K</th>
                                        <th class="p-2.5 text-center">SG</th>
                                        <th class="p-2.5 text-center font-bold text-amber-400 bg-slate-950/80">PTS</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/40">
                                    <?php $pos = 1; foreach ($teams as $teamName => $stats): ?>
                                        <tr class="hover:bg-slate-800/30 transition">
                                            <td class="p-2.5 text-center font-bold text-slate-400"><?=$pos++?></td>
                                            <td class="p-2.5 font-semibold text-slate-200"><?=$teamName?></td>
                                            <td class="p-2.5 text-center"><?=$stats['p']?></td>
                                            <td class="p-2.5 text-center text-emerald-400"><?=$stats['w']?></td>
                                            <td class="p-2.5 text-center text-amber-400"><?=$stats['d']?></td>
                                            <td class="p-2.5 text-center text-rose-400"><?=$stats['l']?></td>
                                            <td class="p-2.5 text-center font-mono text-[11px] <?= $stats['gd'] >= 0 ? 'text-emerald-400' : 'text-rose-400'?>">
                                                <?= $stats['gd'] > 0 ? '+'.$stats['gd'] : $stats['gd'] ?>
                                            </td>
                                            <td class="p-2.5 text-center font-extrabold bg-slate-950/30 text-amber-400"><?=$stats['pts']?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="grup-section" class="mb-12 scroll-mt-6">
            <h2 class="text-lg font-bold tracking-tight mb-4 text-slate-300">⚽ Jadwal & Hasil Lengkap Laga Babak Grup</h2>
            <div class="bg-slate-900/60 rounded-xl border border-slate-800 overflow-hidden divide-y divide-slate-800/60 max-h-[500px] overflow-y-auto custom-scrollbar">
                <?php foreach ($matches as $match): if ($match['phase'] === 'Group'): 
                    $isFinished = ($match['score_home'] !== null && $match['score_away'] !== null);
                ?>
                    <div class="p-3 flex flex-col sm:flex-row items-center justify-between gap-3 hover:bg-slate-800/20 transition text-xs">
                        <div class="text-center sm:text-left min-w-[150px]">
                            <span class="inline-block px-1.5 py-0.5 mb-1 text-[9px] font-bold uppercase rounded bg-indigo-500/20 text-indigo-400">Grup <?=$match['group']?></span>
                            <div class="text-slate-300 font-semibold text-[11px]"><?=$match['date']?></div>
                            <div class="text-[10px] text-slate-500 truncate max-w-[170px]"><?=$match['stadium']?></div>
                        </div>
                        <div class="flex items-center justify-center gap-3 w-full sm:w-auto my-1 sm:my-0">
                            <div class="w-28 text-right font-bold text-slate-200 truncate text-[11px]"><?=$match['home']?></div>
                            <div class="bg-slate-950 px-2.5 py-1 rounded border border-slate-800 font-extrabold text-amber-400 min-w-[55px] text-center text-[12px]">
                                <?=$isFinished ? $match['score_home']." : ".$match['score_away'] : "VS"?>
                            </div>
                            <div class="w-28 text-left font-bold text-slate-200 truncate text-[11px]"><?=$match['away']?></div>
                        </div>
                        <div class="text-center sm:text-right">
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-semibold <?=$isFinished ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400'?>">
                                <?=$isFinished ? 'Selesai' : 'Belum Mulai'?>
                            </span>
                        </div>
                    </div>
                <?php endif; endforeach; ?>
            </div>
        </section>

        <section id="knockout-section" class="scroll-mt-6">
            <h2 class="text-lg font-bold tracking-tight mb-4 text-orange-500">🔥 Skema Pertandingan Babak Gugur</h2>
            <?php 
            $phases = ['Round of 32', 'Round of 16', 'Quarter-Final', 'Semi-Final', 'Perebutan Juara 3', 'Final'];
            foreach ($phases as $phase): 
            ?>
                <div class="mb-6">
                    <h3 class="text-xs font-bold text-amber-500 mb-2 border-l-4 border-amber-500 pl-2 uppercase tracking-wide"><?=$phase?></h3>
                    <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden divide-y divide-slate-800/60">
                        <?php foreach ($matches as $match): if ($match['phase'] === $phase): 
                            $isFinished = ($match['score_home'] !== null && $match['score_away'] !== null);
                        ?>
                            <div class="p-3 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs hover:bg-slate-800/10 transition">
                                <div class="text-slate-400 text-center sm:text-left">
                                    <div class="font-semibold text-slate-300 text-[11px]"><?=$match['date']?></div>
                                    <div class="text-[10px] text-slate-500"><?=$match['stadium']?></div>
                                </div>
                                <div class="flex items-center justify-center gap-3 my-1 sm:my-0">
                                    <div class="w-32 text-right font-bold text-slate-200 text-[11px] truncate"><?=$match['home']?></div>
                                    <div class="bg-slate-950 px-2.5 py-1 rounded border border-slate-800 font-extrabold text-amber-400 min-w-[55px] text-center text-[11px]">
                                        <?=$isFinished ? $match['score_home']." : ".$match['score_away'] : "VS"?>
                                    </div>
                                    <div class="w-32 text-left font-bold text-slate-200 text-[11px] truncate"><?=$match['away']?></div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[9px] <?=$isFinished ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400'?>">
                                    <?=$isFinished ? 'Selesai' : 'Belum Laga'?>
                                </span>
                            </div>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <footer class="mt-12 pt-6 border-t border-slate-800 text-center text-xs text-slate-500">
            <p class="mb-3">© 2026 World Cup Dashboard System. All Rights Reserved.</p>
            <div class="inline-block bg-slate-900 px-4 py-2 rounded-xl border border-slate-800 text-slate-300 shadow-md">
                👥 Total Akses Halaman: <span class="text-amber-400 font-bold font-mono text-sm"><?=number_format($visitor_count, 0, ',', '.')?></span> Kali
            </div>
        </footer>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('PWA Service Worker Aktif!', reg))
                    .catch(err => console.error('PWA Gagal Di-load:', err));
            });
        }
    </script>
</body>
</html>
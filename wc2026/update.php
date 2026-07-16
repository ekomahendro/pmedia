<?php
$dataFile = 'data.json';
if (!file_exists($dataFile)) { die("Error: File data tidak ditemukan."); }

$data = json_decode(file_get_contents($dataFile), true);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_scores'])) {
    foreach ($data['matches'] as &$match) {
        $id = $match['id'];
        if (isset($_POST["score_home_$id"]) && isset($_POST["score_away_$id"])) {
            $sh = $_POST["score_home_$id"]; $sa = $_POST["score_away_$id"];
            $match['score_home'] = ($sh === '') ? null : (int)$sh;
            $match['score_away'] = ($sa === '') ? null : (int)$sa;
        }
    }
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
    $message = '✅ Seluruh data 102 hasil pertandingan berhasil diperbarui!';
    $data = json_decode(file_get_contents($dataFile), true);
}
$matches = $data['matches'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Panel Pembaruan Skor Piala Dunia 2026</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0b1329] text-slate-200 p-4 md:p-8">
    <div class="max-w-4xl mx-auto">
        <header class="mb-6 flex justify-between items-center border-b border-slate-800 pb-4">
            <div>
                <h1 class="text-xl font-bold text-amber-400">⚙️ Operator Dashboard</h1>
                <p class="text-xs text-slate-500">Perubahan skor akan langsung mengubah peringkat klasemen utama.</p>
            </div>
            <a href="index.php" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 font-bold text-xs rounded-lg">⬅️ Hubungkan Ke Dashboard</a>
        </header>

        <?php if ($message): ?>
            <div class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs rounded-lg"><?=$message?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <?php 
            $grouped = [];
            foreach ($matches as $m) {
                $label = ($m['phase'] === 'Group') ? 'Babak Grup ' . $m['group'] : $m['phase'];
                $grouped[$label][] = $m;
            }
            foreach ($grouped as $phaseLabel => $list):
            ?>
                <div class="mb-8">
                    <h2 class="text-xs font-bold text-slate-400 bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-800 uppercase mb-2"><?=$phaseLabel?></h2>
                    <div class="bg-slate-900/40 border border-slate-800 rounded-xl divide-y divide-slate-800/40 overflow-hidden">
                        <?php foreach ($list as $match): ?>
                            <div class="p-2.5 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs">
                                <span class="text-slate-500 text-[10px] sm:w-1/4">Laga #<?=$match['id']?> &bull; <?=$match['date']?></span>
                                <div class="flex items-center gap-2 justify-center w-full sm:w-auto">
                                    <div class="w-28 text-right font-medium text-slate-300 truncate"><?=$match['home']?></div>
                                    <input type="number" name="score_home_<?=$match['id']?>" value="<?=$match['score_home']?>" placeholder="-" min="0" class="w-10 h-8 bg-slate-950 border border-slate-700 rounded text-center text-amber-400 font-bold text-xs focus:outline-none focus:border-amber-500">
                                    <span class="text-slate-600 font-bold">:</span>
                                    <input type="number" name="score_away_<?=$match['id']?>" value="<?=$match['score_away']?>" placeholder="-" min="0" class="w-10 h-8 bg-slate-950 border border-slate-700 rounded text-center text-amber-400 font-bold text-xs focus:outline-none focus:border-amber-500">
                                    <div class="w-28 text-left font-medium text-slate-300 truncate"><?=$match['away']?></div>
                                </div>
                                <span class="text-[10px] text-slate-500"><?=($match['score_home'] !== null) ? '✅ Terisi' : '⏳ -'?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="sticky bottom-4 flex justify-end">
                <button type="submit" name="update_scores" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 font-extrabold rounded-xl shadow-xl text-xs uppercase tracking-wide">💾 Simpan Semua Skor</button>
            </div>
        </form>
    </div>
</body>
</html>
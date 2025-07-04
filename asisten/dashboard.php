<?php
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once '../config.php';
include_once 'templates/header.php';

// --- LOGIKA PENGAMBILAN DATA STATISTIK ---
$total_modul = $conn->query("SELECT COUNT(id) as total FROM modul_praktikum")->fetch_assoc()['total'];
$total_laporan = $conn->query("SELECT COUNT(id) as total FROM laporan_praktikum")->fetch_assoc()['total'];
$laporan_pending = $conn->query("SELECT COUNT(id) as total FROM laporan_praktikum WHERE nilai IS NULL")->fetch_assoc()['total'];

// 4. Aktivitas Laporan Terbaru (5 terakhir)
$sql_recent = "SELECT u.nama as nama_mahasiswa, m.nama_modul, lp.submitted_at
               FROM laporan_praktikum lp
               JOIN users u ON lp.mahasiswa_id = u.id
               JOIN modul_praktikum m ON lp.modul_id = m.id
               ORDER BY lp.submitted_at DESC
               LIMIT 5";
$recent_activities = $conn->query($sql_recent);

// --- FUNGSI BANTU (HELPER FUNCTIONS) ---
function getInitials($name) {
    $words = explode(' ', $name, 2);
    $initials = '';
    foreach ($words as $w) { $initials .= strtoupper($w[0]); }
    return $initials;
}

function time_ago($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    $string = ['y' => 'tahun', 'm' => 'bulan', 'w' => 'minggu', 'd' => 'hari', 'h' => 'jam', 'i' => 'menit', 's' => 'detik'];
    foreach ($string as $k => &$v) {
        if ($diff->$k) { $v = $diff->$k . ' ' . $v; } else { unset($string[$k]); }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' yang lalu' : 'baru saja';
}
?>

<p class="text-gray-700 mb-6">Selamat datang kembali! Berikut adalah ringkasan aktivitas terbaru di sistem.</p>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-purple-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Total Modul Diajarkan</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $total_modul; ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-purple-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Total Laporan Masuk</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $total_laporan; ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-yellow-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Laporan Belum Dinilai</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $laporan_pending; ?></p>
        </div>
    </div>
</div>

<div class="bg-white p-6 rounded-lg shadow-md mt-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Aktivitas Laporan Terbaru</h3>
    <div class="space-y-4">
        <?php if ($recent_activities && $recent_activities->num_rows > 0): ?>
            <?php while($activity = $recent_activities->fetch_assoc()): ?>
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center mr-4 shrink-0">
                        <span class="font-bold text-purple-700"><?php echo getInitials($activity['nama_mahasiswa']); ?></span>
                    </div>
                    <div>
                        <p class="text-gray-800">
                            <strong class="text-purple-800"><?php echo htmlspecialchars($activity['nama_mahasiswa']); ?></strong> mengumpulkan laporan untuk <strong class="font-medium text-gray-700"><?php echo htmlspecialchars($activity['nama_modul']); ?></strong>
                        </p>
                        <p class="text-sm text-gray-500"><?php echo time_ago($activity['submitted_at']); ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">Belum ada aktivitas laporan.</p>
        <?php endif; ?>
    </div>
</div>
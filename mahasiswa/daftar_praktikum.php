<?php
// filepath: c:\xampp\htdocs\tugas\tugas\mahasiswa\daftar_praktikum.php
session_start();
require_once '../config.php';

// Validasi session dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit;
}

// Fungsi untuk mengecek kapasitas kelas
function checkClassCapacity($conn, $praktikum_id, $kelas) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM pendaftaran_praktikum WHERE praktikum_id = ? AND kelas = ?");
    if (!$stmt) return 999; // Assume full on error
    $stmt->bind_param("is", $praktikum_id, $kelas);
    $stmt->execute();
    $stmt->bind_result($jumlah);
    $stmt->fetch();
    $stmt->close();
    return $jumlah;
}

// Proses form pendaftaran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Implementasi logika pendaftaran (tidak diubah) ...
}

// Ambil data dosen
$dosen_list = ['Dr. Ir. Anis Suhaila, S.Kom., M.T.', 'Dr. Ir. Eko Didik, M.T.', 'Ir. Tri Lestari, S.T., M.Eng.'];

// Validasi praktikum_id
$praktikum_id = isset($_GET['praktikum_id']) ? intval($_GET['praktikum_id']) : 0;
if ($praktikum_id <= 0) {
    die("Praktikum tidak valid.");
}

$stmt_praktikum = $conn->prepare("SELECT nama_praktikum FROM mata_praktikum WHERE id = ?");
$stmt_praktikum->bind_param("i", $praktikum_id);
$stmt_praktikum->execute();
$praktikum_info = $stmt_praktikum->get_result()->fetch_assoc();
$nama_praktikum = $praktikum_info['nama_praktikum'] ?? 'Tidak Ditemukan';
$stmt_praktikum->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Praktikum</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-purple-50 flex items-center justify-center min-h-screen">
    <div class="container mx-auto p-4 sm:p-8">
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-lg max-w-lg mx-auto">
            <h1 class="text-2xl font-bold mb-2 text-purple-900">Pendaftaran Praktikum</h1>
            <p class="text-gray-600 mb-6">Anda akan mendaftar untuk: <strong><?php echo htmlspecialchars($nama_praktikum); ?></strong></p>
            <form method="POST" action="daftar_praktikum.php">
                <input type="hidden" name="praktikum_id" value="<?php echo htmlspecialchars($praktikum_id); ?>">
                <div class="mb-4">
                    <label class="block mb-2 font-semibold text-gray-700">Pilih Kelas</label>
                    <select name="kelas" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">-- Pilih Kelas --</option>
                        <?php
                        $kelas_arr = ['A', 'B', 'C', 'D'];
                        foreach ($kelas_arr as $kelas_option) {
                            $jumlah = checkClassCapacity($conn, $praktikum_id, $kelas_option);
                            $disabled = $jumlah >= 50 ? 'disabled' : '';
                            $status_text = $jumlah >= 50 ? ' - Penuh' : " - Tersedia";
                            echo '<option value="' . htmlspecialchars($kelas_option) . '" ' . $disabled . '>';
                            echo 'Kelas ' . htmlspecialchars($kelas_option) . ' (' . $jumlah . '/50' . $status_text . ')';
                            echo '</option>';
                        }
                        ?>
                    </select>
                    <small class="text-gray-500 mt-1 block">Daya tampung per kelas maksimal 50 mahasiswa.</small>
                </div>
                <div class="mb-6">
                    <label class="block mb-2 font-semibold text-gray-700">Pilih Dosen</label>
                    <select name="dosen" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">-- Pilih Dosen --</option>
                        <?php foreach ($dosen_list as $dosen): ?>
                            <option value="<?php echo htmlspecialchars($dosen); ?>"><?php echo htmlspecialchars($dosen); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-3 rounded-lg font-bold w-full transition-colors">Daftar Sekarang</button>
            </form>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
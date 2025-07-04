<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Daftar Praktikum';
$activePage = 'my_courses'; // Disesuaikan

$header_path = __DIR__ . '/templates/header_mahasiswa.php';
$footer_path = __DIR__ . '/templates/footer_mahasiswa.php';

require_once __DIR__ . '/../config.php';

$message = '';
$message_type = '';

// Handle pendaftaran
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['daftar'])) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
        $message = "Anda harus login sebagai mahasiswa untuk mendaftar praktikum.";
        $message_type = 'error';
    } else {
        $mahasiswa_id = $_SESSION['user_id'];
        $praktikum_id = $_POST['praktikum_id'];

        $sql_check = "SELECT id FROM pendaftaran_praktikum WHERE mahasiswa_id = ? AND praktikum_id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ii", $mahasiswa_id, $praktikum_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $message = "Anda sudah terdaftar pada praktikum ini.";
            $message_type = 'info';
        } else {
            $sql_insert = "INSERT INTO pendaftaran_praktikum (mahasiswa_id, praktikum_id) VALUES (?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ii", $mahasiswa_id, $praktikum_id);
            if ($stmt_insert->execute() && $stmt_insert->affected_rows > 0) {
                 $message = "Berhasil mendaftar praktikum!";
                 $message_type = 'success';
            } else {
                 $message = "Gagal mendaftar. Coba lagi.";
                 $message_type = 'error';
            }
        }
    }
}

// Ambil semua mata praktikum
$sql = "SELECT id, nama_praktikum, deskripsi, created_at FROM mata_praktikum ORDER BY created_at DESC";
$result = $conn->query($sql);

// Ambil data praktikum yang sudah diikuti oleh mahasiswa
$praktikum_diikuti = [];
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'mahasiswa') {
    $mahasiswa_id = $_SESSION['user_id'];
    $sql_diikuti = "SELECT praktikum_id FROM pendaftaran_praktikum WHERE mahasiswa_id = ?";
    $stmt_diikuti = $conn->prepare($sql_diikuti);
    $stmt_diikuti->bind_param("i", $mahasiswa_id);
    $stmt_diikuti->execute();
    $result_diikuti = $stmt_diikuti->get_result();

    while ($row = $result_diikuti->fetch_assoc()) {
        $praktikum_diikuti[] = $row['praktikum_id'];
    }
}

if (file_exists($header_path)) {
    include_once $header_path;
}
?>

<div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">📝 Daftar Mata Praktikum</h2>
    <p class="text-gray-600">Silakan pilih mata praktikum yang ingin kamu ikuti dari katalog.</p>
</div>

<?php if (!empty($message)): 
    $color_class = 'bg-blue-100 border-blue-400 text-blue-700'; // default
    if ($message_type == 'success') {
        $color_class = 'bg-green-100 border-green-400 text-green-700';
    } elseif ($message_type == 'error') {
        $color_class = 'bg-red-100 border-red-400 text-red-700';
    }
?>
    <div class="<?php echo $color_class; ?> px-4 py-3 rounded mb-6" role="alert">
        <?php echo $message; ?>
    </div>
<?php endif; ?>


<?php if ($result->num_rows > 0): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition flex flex-col">
                <div class="flex-grow">
                    <h3 class="text-lg font-semibold text-purple-700 mb-2"><?php echo htmlspecialchars($row['nama_praktikum']); ?></h3>
                    <p class="text-gray-600 mb-3 text-sm"><?php echo htmlspecialchars($row['deskripsi']); ?></p>
                </div>
                <p class="text-xs text-gray-400 mb-4 mt-2">Dibuat pada: <?php echo date('d M Y', strtotime($row['created_at'])); ?></p>

                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'mahasiswa'): ?>
                    <?php if (in_array($row['id'], $praktikum_diikuti)): ?>
                        <a href="detail_praktikum.php" class="w-full text-center bg-green-500 text-white py-2 px-4 rounded-lg cursor-pointer">
                            Sudah Terdaftar (Lihat Detail)
                        </a>
                    <?php else: ?>
                        <form method="POST" action="my_courses.php">
                            <input type="hidden" name="praktikum_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="daftar" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg transition-colors">
                                Daftar Praktikum
                            </button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="../login.php" class="w-full text-center bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg transition-colors">Login untuk Mendaftar</a>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="text-center text-gray-500 bg-white p-6 rounded-xl shadow">
        <p>Belum ada mata praktikum yang tersedia di katalog.</p>
    </div>
<?php endif; ?>

<?php
$conn->close();
if (file_exists($footer_path)) {
    include_once $footer_path;
}
?>
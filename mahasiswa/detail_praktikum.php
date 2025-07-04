<?php
// filepath: c:\xampp\htdocs\tugas\tugas\mahasiswa\detail_praktikum.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';

$pageTitle = 'Detail Praktikum';
$activePage = 'lihat detail';

$header_path = __DIR__ . '/templates/header_mahasiswa.php';
$footer_path = __DIR__ . '/templates/footer_mahasiswa.php';

// Redirect jika bukan mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit;
}

$mahasiswa_id = $_SESSION['user_id'];

// Ambil semua praktikum yang diikuti mahasiswa
$stmt = $conn->prepare("SELECT mp.id, mp.nama_praktikum, mp.deskripsi 
                        FROM mata_praktikum mp
                        JOIN pendaftaran_praktikum pp ON mp.id = pp.praktikum_id
                        WHERE pp.mahasiswa_id = ?");
$stmt->bind_param("i", $mahasiswa_id);
$stmt->execute();
$praktikum_result = $stmt->get_result();
$stmt->close();

$praktikum_list = [];
while ($praktikum = $praktikum_result->fetch_assoc()) {
    $praktikum_list[] = $praktikum;
}

// Ambil semua modul untuk semua praktikum
$modul_map = [];
foreach ($praktikum_list as $praktikum) {
    $stmt = $conn->prepare("SELECT id, nama_modul, file_materi FROM modul_praktikum WHERE praktikum_id = ? ORDER BY created_at ASC");
    $stmt->bind_param("i", $praktikum['id']);
    $stmt->execute();
    $modul_result = $stmt->get_result();
    while ($modul = $modul_result->fetch_assoc()) {
        $modul_map[$praktikum['id']][] = $modul;
    }
    $stmt->close();
}

// Ambil semua laporan mahasiswa untuk semua modul
$stmt = $conn->prepare("SELECT modul_id, file_laporan, nilai, feedback FROM laporan_praktikum WHERE mahasiswa_id = ?");
$stmt->bind_param("i", $mahasiswa_id);
$stmt->execute();
$laporan_result = $stmt->get_result();

$laporan_data = [];
while ($row = $laporan_result->fetch_assoc()) {
    $laporan_data[$row['modul_id']] = $row;
}
$stmt->close();

if (file_exists($header_path)) {
    include_once $header_path;
}
?>

<?php if (isset($_GET['status'])): 
    $status = $_GET['status'];
    $message = '';
    $type = '';
    if ($status === 'laporan_deleted') {
        $message = 'Pengumpulan laporan berhasil dibatalkan.'; $type = 'success';
    } elseif ($status === 'laporan_edited') {
        $message = 'Laporan berhasil diperbarui.'; $type = 'success';
    } elseif ($status === 'laporan_uploaded') {
        $message = 'Laporan berhasil diunggah.'; $type = 'success';
    }
?>
    <div class="mb-4 p-4 rounded-md <?php echo $type === 'success' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<?php if (count($praktikum_list) === 0): ?>
    <div class="text-center p-8 bg-white rounded-xl shadow">
        <h3 class="text-xl font-bold text-gray-700">Anda Belum Terdaftar</h3>
        <p class="text-gray-500 mt-2">Anda belum terdaftar pada praktikum apapun. Silakan daftar melalui katalog.</p>
        <a href="my_courses.php" class="mt-4 inline-block bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded-lg transition-colors">
            Lihat Katalog Praktikum
        </a>
    </div>
<?php else: ?>
    <?php foreach ($praktikum_list as $praktikum): ?>
        <div class="mb-10 bg-white p-6 rounded-2xl shadow-lg">
            <div class="mb-6 border-b border-gray-200 pb-4">
                <h2 class="text-3xl font-bold text-purple-900 mb-2">📘 <?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h2>
                <p class="text-gray-700"><?php echo htmlspecialchars($praktikum['deskripsi']); ?></p>
            </div>

            <div class="space-y-6">
                <h3 class="text-xl font-semibold text-purple-800 mb-2">Daftar Modul</h3>
                <?php if (!empty($modul_map[$praktikum['id']])): ?>
                    <?php foreach ($modul_map[$praktikum['id']] as $modul): ?>
                        <div class="bg-gray-50 p-6 rounded-xl border border-gray-200">
                            <h4 class="text-lg font-bold text-purple-800 mb-2"><?php echo htmlspecialchars($modul['nama_modul']); ?></h4>
                            <p class="text-gray-600 mb-4">
                                Materi:
                                <?php if (!empty($modul['file_materi'])): ?>
                                    <a href="../uploads/materi/<?php echo htmlspecialchars($modul['file_materi']); ?>" class="text-purple-600 underline hover:text-purple-800" download>
                                       Unduh Materi
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">Belum ada file materi</span>
                                <?php endif; ?>
                            </p>

                            <?php if (isset($laporan_data[$modul['id']])): 
                                $laporan = $laporan_data[$modul['id']];
                            ?>
                                <div class="bg-green-50 border border-green-200 p-4 rounded-lg">
                                    <div class="font-medium text-green-800 mb-2">✔️ Laporan Telah Dikumpulkan</div>
                                    <p class="text-gray-700">Nilai: <strong class="text-lg"><?php echo $laporan['nilai'] ?? 'Belum dinilai'; ?></strong></p>
                                    <?php if(!empty($laporan['feedback'])): ?>
                                        <p class="text-gray-700 mt-1">Feedback: <em class="text-gray-600">"<?php echo htmlspecialchars($laporan['feedback']); ?>"</em></p>
                                    <?php endif; ?>
                                    <p class="text-sm text-gray-500 mt-2">File: <?php echo htmlspecialchars($laporan['file_laporan']); ?></p>
                                    <div class="flex gap-2 mt-4">
                                        <button type="button" onclick="showEditForm('<?php echo $modul['id']; ?>','<?php echo $praktikum['id']; ?>')" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md font-bold text-sm">Edit</button>
                                        <button type="button" onclick="showBatalModal('<?php echo $modul['id']; ?>')" class="bg-red-500 hover:bg-red-700 text-white px-4 py-2 rounded-md font-bold text-sm">Batal</button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <form method="POST" action="upload_laporan.php" enctype="multipart/form-data" class="mt-4 space-y-3">
                                    <input type="hidden" name="modul_id" value="<?php echo $modul['id']; ?>">
                                    <input type="hidden" name="praktikum_id" value="<?php echo $praktikum['id']; ?>">
                                    <input type="file" name="file_laporan" required class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100"/>
                                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold px-5 py-2 rounded-lg transition-colors text-sm">Upload Laporan</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-gray-500 text-center py-4">Belum ada modul pada praktikum ini.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (count($praktikum_list) > 0): foreach ($praktikum_list as $praktikum): if (!empty($modul_map[$praktikum['id']])): foreach ($modul_map[$praktikum['id']] as $modul): ?>
<div id="modal-batal-<?php echo $modul['id']; ?>" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 shadow-lg w-full max-w-md">
        <h3 class="text-lg font-bold mb-4 text-red-700">Konfirmasi Batal Pengumpulan</h3>
        <p class="mb-6">Apakah Anda yakin ingin membatalkan pengumpulan laporan untuk modul <strong><?php echo htmlspecialchars($modul['nama_modul']); ?></strong>? Tindakan ini akan menghapus file dan nilai Anda.</p>
        <form method="POST" action="batal_laporan.php">
            <input type="hidden" name="modul_id" value="<?php echo $modul['id']; ?>">
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeBatalModal('<?php echo $modul['id']; ?>')" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400 text-gray-800">Tidak</button>
                <button type="submit" class="px-4 py-2 rounded bg-red-600 hover:bg-red-700 text-white font-bold">Iya, Batalkan</button>
            </div>
        </form>
    </div>
</div>
<div id="modal-edit-<?php echo $modul['id']; ?>" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 shadow-lg w-full max-w-md">
        <h3 class="text-lg font-bold mb-4 text-purple-700">Edit Laporan untuk <?php echo htmlspecialchars($modul['nama_modul']); ?></h3>
        <form method="POST" action="upload_laporan.php" enctype="multipart/form-data">
            <input type="hidden" name="modul_id" value="<?php echo $modul['id']; ?>">
            <input type="hidden" name="praktikum_id" value="<?php echo $praktikum['id']; ?>">
            <div class="mb-4">
                <label class="block mb-2 font-semibold text-gray-700">File Laporan Baru (PDF/DOC/DOCX)</label>
                <input type="file" name="file_laporan" required class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100"/>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeEditModal('<?php echo $modul['id']; ?>')" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400 text-gray-800">Batal</button>
                <button type="submit" class="px-4 py-2 rounded bg-purple-600 hover:bg-purple-700 text-white font-bold">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; endif; endforeach; endif; ?>

<script>
function showBatalModal(modulId) { document.getElementById('modal-batal-' + modulId).classList.remove('hidden'); }
function closeBatalModal(modulId) { document.getElementById('modal-batal-' + modulId).classList.add('hidden'); }
function showEditForm(modulId, praktikumId) { document.getElementById('modal-edit-' + modulId).classList.remove('hidden'); }
function closeEditModal(modulId) { document.getElementById('modal-edit-' + modulId).classList.add('hidden'); }
</script>

<?php
if (file_exists($footer_path)) { include_once $footer_path; }
$conn->close();
?>
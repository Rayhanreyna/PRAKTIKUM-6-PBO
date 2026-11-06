<?php
declare(strict_types=1);
session_start();

// ==========================================
// 1. Helper Functions (Didefinisikan Di Awal)
// ==========================================
if (!function_exists('h')) {
    function h(string $value): string { 
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); 
    }
}

if (!function_exists('money')) {
    function money(float $value): string { 
        return 'Rp ' . number_format($value, 0, ',', '.'); 
    }
}

// ==========================================
// 2. Definisi Class & Validation Logic
// ==========================================

abstract class Pegawai
{
    public function __construct(
        private string $id,
        private string $nama
    ) {
        $this->validateProfile($id, $nama);
        $this->id = trim($id);
        $this->nama = trim($nama);
    }

    private function validateProfile(string $id, string $nama): void
    {
        if (trim($id) === '' || trim($nama) === '') {
            throw new InvalidArgumentException("ID dan Nama pegawai wajib diisi.");
        }
    }

    public function getId(): string { return $this->id; }
    public function getNama(): string { return $this->nama; }

    abstract public function hitungGaji(): float;
}

final class PegawaiTetap extends Pegawai
{
    public function __construct(
        string $id,
        string $nama,
        private float $gajiPokok
    ) {
        self::validateAngka($gajiPokok, "Gaji pokok");
        parent::__construct($id, $nama);
    }

    private static function validateAngka(float $nilai, string $namaField): void
    {
        if (!is_finite($nilai) || $nilai < 0) {
            throw new InvalidArgumentException("{$namaField} tidak boleh negatif.");
        }
    }

    public function getGajiPokok(): float { return $this->gajiPokok; }
    public function hitungGaji(): float { return $this->gajiPokok; }
}

final class PegawaiKontrak extends Pegawai
{
    public function __construct(
        string $id,
        string $nama,
        private float $upahPerJam,
        private int $jamKerja
    ) {
        self::validateAngka($upahPerJam, "Upah per jam");
        if ($jamKerja < 0) {
            throw new InvalidArgumentException("Jam kerja tidak boleh negatif.");
        }
        parent::__construct($id, $nama);
    }

    private static function validateAngka(float $nilai, string $namaField): void
    {
        if (!is_finite($nilai) || $nilai < 0) {
            throw new InvalidArgumentException("{$namaField} tidak boleh negatif.");
        }
    }

    public function getUpahPerJam(): float { return $this->upahPerJam; }
    public function getJamKerja(): int { return $this->jamKerja; }
    public function hitungGaji(): float { return $this->upahPerJam * $this->jamKerja; }
}

// ==========================================
// 3. Inisialisasi Data Default Session
// ==========================================
if (!isset($_SESSION['pegawai_list'])) {
    $_SESSION['pegawai_list'] = [
        'EMP-001' => new PegawaiTetap("EMP-001", "Budi Santoso", 8500000),
        'EMP-002' => new PegawaiKontrak("EMP-002", "Siti Aminah", 50000, 160)
    ];
}

$error = '';
$success = '';
$selected = null;

// ==========================================
// 4. Controller / Handling Request (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'delete') {
            $id = trim((string) ($_POST['pegawai_id'] ?? ''));
            if (!isset($_SESSION['pegawai_list'][$id])) {
                throw new InvalidArgumentException('Pegawai tidak ditemukan.');
            }
            unset($_SESSION['pegawai_list'][$id]);
            $success = 'Data pegawai berhasil dihapus.';
        } elseif ($action === 'save') {
            $id = trim((string) ($_POST['pegawai_id'] ?? ''));
            $nama = trim((string) ($_POST['nama'] ?? ''));
            $type = (string) ($_POST['type'] ?? '');
            $mode = (string) ($_POST['mode'] ?? 'add');

            if ($mode === 'add' && isset($_SESSION['pegawai_list'][$id])) {
                throw new InvalidArgumentException('ID Pegawai sudah ada.');
            }

            if ($type === 'tetap') {
                $gajiPokok = filter_var($_POST['gaji_pokok'] ?? null, FILTER_VALIDATE_FLOAT);
                if ($gajiPokok === false) {
                    throw new InvalidArgumentException('Gaji pokok harus berupa angka valid.');
                }
                $pegawai = new PegawaiTetap($id, $nama, $gajiPokok);
            } elseif ($type === 'kontrak') {
                $upahPerJam = filter_var($_POST['upah_per_jam'] ?? null, FILTER_VALIDATE_FLOAT);
                $jamKerja = filter_var($_POST['jam_kerja'] ?? null, FILTER_VALIDATE_INT);
                if ($upahPerJam === false || $jamKerja === false) {
                    throw new InvalidArgumentException('Upah per jam dan jam kerja harus berupa angka valid.');
                }
                $pegawai = new PegawaiKontrak($id, $nama, $upahPerJam, $jamKerja);
            } else {
                throw new InvalidArgumentException('Pilih tipe pegawai yang valid.');
            }

            $_SESSION['pegawai_list'][$id] = $pegawai;
            $success = ($mode === 'edit') ? 'Data pegawai berhasil diperbarui.' : 'Pegawai baru berhasil ditambahkan.';
        }

        $_SESSION['flash'] = $success;
        header('Location: pegawai.php');
        exit;
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    }
}

// Flash Message Handling
if (isset($_SESSION['flash'])) {
    $success = (string) $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// Handling Edit Mode (GET)
if (isset($_GET['edit'])) {
    $editId = (string) $_GET['edit'];
    $selected = $_SESSION['pegawai_list'][$editId] ?? null;
}

// Handling Search Mode (GET)
$search = trim((string) ($_GET['q'] ?? ''));
$rows = array_filter($_SESSION['pegawai_list'], static function (Pegawai $p) use ($search): bool {
    return $search === '' || stripos($p->getId() . ' ' . $p->getNama(), $search) !== false;
});
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Pegawai CRUD — Polymorphism</title>
    <style>
        :root{font-family:system-ui,Arial,sans-serif;color:#172c4c;background:#f3f7fc}
        body{max-width:1120px;margin:35px auto;padding:0 16px}
        h1{margin-bottom:4px}
        p{color:#5b6980}
        .panel{background:#fff;border-radius:14px;padding:22px;margin:20px 0;box-shadow:0 6px 24px #102b5010}
        .fields{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px}
        label{display:block;font-size:13px;font-weight:650;margin-bottom:5px}
        input,select{box-sizing:border-box;width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:7px}
        button,.btn{background:#1764b5;color:white;padding:10px 15px;border:0;border-radius:7px;cursor:pointer;text-decoration:none;display:inline-block}
        .muted{background:#e8eff7;color:#183a61}
        .danger{background:#c33849}
        .actions{display:flex;gap:7px;align-items:center}
        .alert{padding:12px;border-radius:8px;background:#e4f8e9;margin-bottom:15px}
        .error{background:#ffe7e7;color:#c33849}
        table{width:100%;border-collapse:collapse}
        th,td{text-align:left;padding:10px;border-bottom:1px solid #e4eaf2}
        th{background:#e9f2fd}
        small{color:#65758b}
        .scroll{overflow-x:auto}
        .badge{display:inline-block;padding:4px 8px;border-radius:4px;font-size:12px;font-weight:600}
        .badge-tetap{background:#e0f2fe;color:#0369a1}
        .badge-kontrak{background:#fef3c7;color:#92400e}
    </style>
</head>
<body>

    <h1>👔 CRUD Pegawai — Polymorphism</h1>
    <p>OOP Session Task · Pengelolaan Gaji Pegawai Tetap dan Pegawai Kontrak</p>
    
    <p style="margin-bottom: 20px;">
        <a class="btn muted" href="index.php">← Kembali ke Data Mahasiswa</a>
    </p>

    <?php if ($success !== ''): ?><div class="alert"><?= h($success) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>

    <!-- Form Add / Edit -->
    <section class="panel">
        <h2><?= $selected ? 'Edit Pegawai' : 'Tambah Pegawai' ?></h2>
        <form method="post">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="mode" value="<?= $selected ? 'edit' : 'add' ?>">
            
            <div class="fields">
                <div>
                    <label>ID Pegawai (Immutable)</label>
                    <input name="pegawai_id" required value="<?= h($selected?->getId() ?? '') ?>" <?= $selected ? 'readonly' : '' ?>>
                </div>
                <div>
                    <label>Nama Pegawai</label>
                    <input name="nama" required value="<?= h($selected?->getNama() ?? '') ?>">
                </div>
                <div>
                    <label>Tipe Pegawai</label>
                    <select name="type" id="type">
                        <option value="tetap" <?= !($selected instanceof PegawaiKontrak) ? 'selected' : '' ?>>Pegawai Tetap</option>
                        <option value="kontrak" <?= $selected instanceof PegawaiKontrak ? 'selected' : '' ?>>Pegawai Kontrak</option>
                    </select>
                </div>
                
                <!-- Field Khusus Pegawai Tetap -->
                <div id="field-tetap">
                    <label>Gaji Pokok (IDR)</label>
                    <input name="gaji_pokok" type="number" min="0" step="1000" value="<?= h((string)($selected instanceof PegawaiTetap ? $selected->getGajiPokok() : 5000000)) ?>">
                </div>
                
                <!-- Field Khusus Pegawai Kontrak -->
                <div id="field-kontrak-upah">
                    <label>Upah / Jam (IDR)</label>
                    <input name="upah_per_jam" type="number" min="0" step="1000" value="<?= h((string)($selected instanceof PegawaiKontrak ? $selected->getUpahPerJam() : 50000)) ?>">
                </div>
                <div id="field-kontrak-jam">
                    <label>Total Jam Kerja</label>
                    <input name="jam_kerja" type="number" min="0" value="<?= h((string)($selected instanceof PegawaiKontrak ? $selected->getJamKerja() : 160)) ?>">
                </div>
            </div>
            
            <p style="margin-top: 15px;">
                <button type="submit"><?= $selected ? 'Simpan Perubahan' : 'Tambah Pegawai' ?></button>
                <a class="btn muted" href="pegawai.php">Reset Form</a>
            </p>
        </form>
    </section>

    <!-- Table Read / Search / Delete -->
    <section class="panel">
        <h2>Daftar Pegawai (<?= count($rows) ?>)</h2>
        <form method="get" class="actions" style="margin-bottom:16px">
            <input name="q" placeholder="Cari ID atau Nama Pegawai" value="<?= h($search) ?>">
            <button type="submit">Cari</button>
            <a class="btn muted" href="pegawai.php">Tampilkan Semua</a>
        </form>

        <div class="scroll">
            <table>
                <thead>
                    <tr>
                        <th>ID Pegawai</th>
                        <th>Nama Pegawai</th>
                        <th>Tipe</th>
                        <th>Rincian Gaji / Upah</th>
                        <th>Total Gaji (hitungGaji)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $p): ?>
                        <tr>
                            <td><strong><?= h($p->getId()) ?></strong></td>
                            <td><?= h($p->getNama()) ?></td>
                            <td>
                                <?php if ($p instanceof PegawaiTetap): ?>
                                    <span class="badge badge-tetap">Pegawai Tetap</span>
                                <?php else: ?>
                                    <span class="badge badge-kontrak">Pegawai Kontrak</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p instanceof PegawaiTetap): ?>
                                    Gaji Pokok: <?= money($p->getGajiPokok()) ?>
                                <?php elseif ($p instanceof PegawaiKontrak): ?>
                                    Upah/Jam: <?= money($p->getUpahPerJam()) ?> (<?= $p->getJamKerja() ?> Jam)
                                <?php endif; ?>
                            </td>
                            <td><strong><?= money($p->hitungGaji()) ?></strong></td>
                            <td>
                                <div class="actions">
                                    <a class="btn muted" href="?edit=<?= urlencode($p->getId()) ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Yakin ingin menghapus pegawai ini?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="pegawai_id" value="<?= h($p->getId()) ?>">
                                        <button class="danger">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?>
                        <tr><td colspan="6">Data pegawai tidak ditemukan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p><small>Data disimpan sementara dalam PHP Session.</small></p>
    </section>

    <script>
        const typeSelect = document.getElementById('type');
        const fieldTetap = document.getElementById('field-tetap');
        const fieldKontrakUpah = document.getElementById('field-kontrak-upah');
        const fieldKontrakJam = document.getElementById('field-kontrak-jam');

        function toggleFields() {
            const isTetap = typeSelect.value === 'tetap';
            fieldTetap.style.display = isTetap ? 'block' : 'none';
            fieldKontrakUpah.style.display = isTetap ? 'none' : 'block';
            fieldKontrakJam.style.display = isTetap ? 'none' : 'block';
        }

        typeSelect.addEventListener('change', toggleFields);
        toggleFields();
    </script>
</body>
</html>
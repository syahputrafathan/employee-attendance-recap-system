<?php
/**
 * Controller: Impor Data
 * - Menangani download template & proses upload (tanpa layout, karena perlu header/output khusus)
 * - Untuk tampilan halaman impor, memakai layout.php agar sidebar konsisten
 */

session_start();
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/db.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// === Fungsi bantu ===
function logError($msg) {
    file_put_contents(__DIR__ . '/import_errors.log', '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}


function normalizeDate($v) {
    if (!$v) return null;
    if (is_numeric($v)) {
        return date('Y-m-d', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($v));
    }

    $s = trim((string)$v);
    // Format umum (sering dipakai di Indonesia): dd/mm/yyyy atau dd-mm-yyyy
    if (preg_match('/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4}$/', $s)) {
        $sep = (strpos($s, '/') !== false) ? '/' : '-';
        $fmt = 'd' . $sep . 'm' . $sep . 'Y';
        $dt = \DateTime::createFromFormat($fmt, $s);
        if ($dt) return $dt->format('Y-m-d');
    }

    $ts = strtotime($s);
    return $ts ? date('Y-m-d', $ts) : null;
}
function boolValue($v) {
    $v = strtolower(trim((string)$v));
    return ($v === 'ya' || $v === '1' || $v === 'true') ? 1 : 0;
}

// === Download Template (harus sebelum layout, karena pakai header + output file) ===
if (isset($_GET['download_template'])) {
    $type = $_GET['download_template'];
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '007BFF']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
    ];

    if ($type === 'karyawan') {
        $sheet->fromArray(['No','NIP','Nama','Golongan','Bagian','Jabatan'], null, 'A1');
    } elseif ($type === 'cuti') {
        $sheet->fromArray(['No','NIP','Jenis Cuti','Tanggal Mulai','Tanggal Selesai','Lama Cuti','Cuti Setengah Hari','Alasan'], null, 'A1');
    } elseif ($type === 'surat_tugas') {
        $sheet->fromArray(['No','NIP','Nomor ST','Tanggal ST','Perihal','Tanggal Mulai','Tanggal Selesai','Lokasi','SPD'], null, 'A1');
    } else {
        die('Jenis template tidak valid.');
    }

    $sheet->getStyle('A1:Z1')->applyFromArray($headerStyle);
    foreach (range('A', 'Z') as $c) {
        $sheet->getColumnDimension($c)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"template_$type.xlsx\"");
    IOFactory::createWriter($spreadsheet, 'Xlsx')->save('php://output');
    exit;
}

// === Proses Upload (harus sebelum layout, karena ada redirect/alert) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $type = $_POST['import_type'] ?? '';
    $file = $_FILES['file']['tmp_name'] ?? '';
    if (!$type || !$file) {
        echo "<script>alert('Jenis impor / file belum dipilih.'); window.location='import.php';</script>";
        exit;
    }

    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);

    // --- Map kolom berdasarkan header (biar tidak tergantung huruf kolom) ---
    // Normalisasi header: lowercase, buang spasi/simbol
    $headerRow = $rows[1] ?? [];
    $colByKey = [];
    foreach ($headerRow as $col => $val) {
        $key = strtolower(trim((string)$val));
        $key = preg_replace('/[^a-z0-9]+/i', '', $key);
        if ($key !== '') $colByKey[$key] = $col;
    }

    // helper ambil nilai berdasarkan beberapa kandidat header, fallback ke kolom default
    $get = function(array $row, array $keys, ?string $fallbackCol = null) use ($colByKey) {
        foreach ($keys as $k) {
            $kk = strtolower(trim($k));
            $kk = preg_replace('/[^a-z0-9]+/i', '', $kk);
            if (isset($colByKey[$kk])) {
                $col = $colByKey[$kk];
                return $row[$col] ?? null;
            }
        }
        return $fallbackCol ? ($row[$fallbackCol] ?? null) : null;
    };

    $processed = 0;
    foreach ($rows as $i => $r) {
        if ($i === 1) continue; // skip header
        $nip = trim((string)$get($r, ['NIP'], 'B'));
        if (!$nip) continue;

        try {
            switch ($type) {
                // ====================================
                // 🧍 DATA KARYAWAN
                // ====================================
                case 'karyawan':
                    $nama = trim((string)$get($r, ['Nama'], 'C'));
                    $golongan = trim((string)$get($r, ['Golongan'], 'D'));
                    $bagian = trim((string)$get($r, ['Bagian'], 'E'));
                    $jabatan = trim((string)$get($r, ['Jabatan'], 'F'));
                    if (!$nama) continue 2;

                    $stmt = $conn->prepare("INSERT IGNORE INTO karyawan (nip,nama,golongan,bagian,jabatan) VALUES (?,?,?,?,?)");
                    $stmt->bind_param('sssss', $nip, $nama, $golongan, $bagian, $jabatan);
                    $stmt->execute();
                    $processed++;
                    break;

                // ====================================
                // 🌴 DATA CUTI
                // ====================================
                case 'cuti':
                    $jenis = trim((string)$get($r, ['Jenis Cuti', 'JenisCuti'], 'C'));
                    $tglMulai = normalizeDate($get($r, ['Tanggal Mulai', 'Tgl Mulai', 'TanggalMulai'], 'D'));
                    $tglSelesai = normalizeDate($get($r, ['Tanggal Selesai', 'Tgl Selesai', 'TanggalSelesai'], 'E'));
                    $lama = (int)($get($r, ['Lama Cuti', 'LamaCuti'], 'F') ?? 0);
                    $stgh = boolValue($get($r, ['Cuti Setengah Hari', 'Setengah Hari', 'CutiSetengahHari'], 'G') ?? 0);
                    $alasan = trim((string)$get($r, ['Alasan'], 'H'));

                    if (!$jenis || !$tglMulai || !$tglSelesai) {
                        logError("Row $i skip: field wajib kosong (jenis/tgl mulai/tgl selesai). NIP=$nip");
                        continue 2;
                    }

                    // pastikan jenis cuti ada
                    $stmt = $conn->prepare("SELECT id_cuti FROM cuti WHERE jenis_cuti=?");
                    $stmt->bind_param('s', $jenis);
                    $stmt->execute();
                    $res = $stmt->get_result()->fetch_assoc();

                    if ($res) {
                        $idCuti = (int)$res['id_cuti'];
                    } else {
                        $stmt2 = $conn->prepare("INSERT INTO cuti (jenis_cuti) VALUES (?)");
                        $stmt2->bind_param('s', $jenis);
                        $stmt2->execute();
                        $idCuti = (int)$conn->insert_id;
                    }

                    // insert detail_cuti
                    $stmt3 = $conn->prepare(
                        "INSERT INTO detail_cuti (nip,id_cuti,alasan,lama_cuti,stgh_hari,tgl_mulai,tgl_selesai)
                         VALUES (?,?,?,?,?,?,?)"
                    );
                    $stmt3->bind_param('sisiiss', $nip, $idCuti, $alasan, $lama, $stgh, $tglMulai, $tglSelesai);
                    $stmt3->execute();

                    // insert ke presensi
                    $stmt4 = $conn->prepare("INSERT INTO presensi (nip,id_cuti,status,timestamp) VALUES (?,?, 'Cuti', NOW())");
                    $stmt4->bind_param('si', $nip, $idCuti);
                    $stmt4->execute();

                    $processed++;
                    break;

                // ====================================
// 📄 DATA SURAT TUGAS
// Master jenis: st(id_st, jenis_st)
// Transaksi: detail_st(...)
// ====================================
case 'surat_tugas':
    // Template kamu: ['No','NIP','Nomor ST','Tanggal ST','Perihal','Tanggal Mulai','Tanggal Selesai','Lokasi','SPD']
    // Jadi mappingnya:
    $noST      = trim((string)$get($r, ['Nomor ST','NomorST','No ST','NoST','nomor_st'], 'C'));
    $tglST     = normalizeDate($get($r, ['Tanggal ST','Tgl ST','TanggalST','tgl_st'], 'D'));
    $perihal   = trim((string)$get($r, ['Perihal','perihal'], 'E'));
    $tglMulai  = normalizeDate($get($r, ['Tanggal Mulai','Tgl Mulai','TanggalMulai','tgl_mulai'], 'F'));
    $tglSelesai= normalizeDate($get($r, ['Tanggal Selesai','Tgl Selesai','TanggalSelesai','tgl_selesai'], 'G'));
    $lokasi    = trim((string)$get($r, ['Lokasi','lokasi'], 'H'));
    $spd       = trim((string)$get($r, ['SPD','spd'], 'I'));

    // Validasi wajib (tanpa jenis_st)
    if ($noST === '' || !$tglST || $perihal === '' || !$tglMulai || !$tglSelesai) {
        logError("Row $i skip: field wajib kosong (nomor_st/tgl_st/perihal/tgl_mulai/tgl_selesai). NIP=$nip");
        continue 2;
    }

    // 1) Pastikan master st id=1 ada
    $cek = $conn->query("SELECT id_st FROM st WHERE id_st = 1")->fetch_assoc();
    if (!$cek) {
        // sesuaikan kolom st kamu kalau beda (misal 'jenis_st')
        $conn->query("INSERT INTO st (id_st, jenis_st) VALUES (1, 'Surat Tugas')");
    }
    $idJenisST = 1;

    // 2) Insert transaksi surat tugas
    $stmt2 = $conn->prepare("
        INSERT INTO detail_st (nip, id_st, nomor_st, tgl_st, perihal, tgl_mulai, tgl_selesai, lokasi, spd)
        VALUES (?,?,?,?,?,?,?,?,?)
    ");
    $stmt2->bind_param(
        'sisssssss',
        $nip, $idJenisST, $noST, $tglST, $perihal, $tglMulai, $tglSelesai, $lokasi, $spd
    );
    $stmt2->execute();

    // 3) Insert presensi (konsisten status = 'ST')
    $stmt3 = $conn->prepare("
        INSERT INTO presensi (nip, id_st, status, `timestamp`)
        VALUES (?, ?, 'ST', NOW())
    ");
    $stmt3->bind_param('si', $nip, $idJenisST);
    $stmt3->execute();

    $processed++;
    break;


                default:
                    throw new Exception('Jenis impor tidak valid.');
            }
        } catch (Exception $e) {
            logError("Row $i error: " . $e->getMessage());
        }
    }

    echo "<script>alert('Impor selesai. Data berhasil: $processed'); window.location='dashboard.php';</script>";
    exit;
}

// === Tampilan halaman (pakai sidebar via layout.php) ===
$title = 'Impor Data';
$content = 'import_content.php';
include __DIR__ . '/layout.php';

<?php
require __DIR__.'/vendor/autoload.php';
include 'db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// filter (opsional)
$mulai   = $_GET['mulai']   ?? null;
$selesai = $_GET['selesai'] ?? null;

// ambil data sesuai skema baru
$sql = "
  SELECT
    k.nip,
    k.nama,
    k.golongan,
    k.bagian,
    k.jabatan,
    c.jenis_cuti,
    dc.tgl_mulai,
    dc.tgl_selesai,
    YEAR(dc.tgl_mulai) AS tahun_cuti,
    dc.lama_cuti,
    dc.stgh_hari,
    dc.alasan
  FROM detail_cuti dc
  JOIN karyawan k ON k.nip = dc.nip
  JOIN cuti c     ON c.id_cuti = dc.id_cuti
  WHERE 1=1
";
$params=[]; $types='';
if (!empty($mulai))   { $sql.=" AND dc.tgl_mulai   >= ?"; $params[]=$mulai;   $types.='s'; }
if (!empty($selesai)) { $sql.=" AND dc.tgl_selesai <= ?"; $params[]=$selesai; $types.='s'; }
$sql .= " ORDER BY dc.tgl_mulai DESC";

$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$res = $stmt->get_result();

// ===== Build spreadsheet =====
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Riwayat Cuti');

// header
$headers = [
  'No','NIP','Nama Karyawan','Pangkat/Golongan','Bagian/Bidang','Jabatan',
  'Jenis Cuti','Tanggal Mulai','Tanggal Selesai','Tahun Cuti','Lama Cuti','Cuti Setengah','Alasan'
];
$sheet->fromArray($headers, NULL, 'A1');

// isi
$row = 2; $no = 1;
while ($r = $res->fetch_assoc()) {
    $sheet->setCellValue("A{$row}", $no++);
    // NIP sebagai teks agar tidak jadi notasi ilmiah
    $sheet->setCellValueExplicit("B{$row}", (string)$r['nip'], DataType::TYPE_STRING);
    $sheet->setCellValue("C{$row}", $r['nama']);
    $sheet->setCellValue("D{$row}", $r['golongan']);
    $sheet->setCellValue("E{$row}", $r['bagian']);
    $sheet->setCellValue("F{$row}", $r['jabatan']);
    $sheet->setCellValue("G{$row}", $r['jenis_cuti']);

    // tanggal Excel (bukan string)
    if (!empty($r['tgl_mulai'])) {
        $sheet->setCellValue("H{$row}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(strtotime($r['tgl_mulai'])));
    }
    if (!empty($r['tgl_selesai'])) {
        $sheet->setCellValue("I{$row}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(strtotime($r['tgl_selesai'])));
    }

    $sheet->setCellValue("J{$row}", (int)$r['tahun_cuti']);
    $sheet->setCellValue("K{$row}", (int)$r['lama_cuti']);
    $sheet->setCellValue("L{$row}", ((int)$r['stgh_hari'] === 1 ? 'Ya' : 'Tidak'));
    $sheet->setCellValue("M{$row}", $r['alasan']);
    $row++;
}
$lastRow = max($row - 1, 2);

// style header
$sheet->getStyle("A1:M1")->applyFromArray([
  'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
  'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
  'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
  'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);

// border isi + align
$sheet->getStyle("A2:M{$lastRow}")->applyFromArray([
  'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
  'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
]);

// zebra striping (baris genap)
for ($r = 2; $r <= $lastRow; $r++) {
    if ($r % 2 === 0) {
        $sheet->getStyle("A{$r}:M{$r}")
              ->getFill()->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB('F7F7F7');
    }
}

// format kolom: tanggal & angka
$sheet->getStyle("H2:I{$lastRow}")
      ->getNumberFormat()->setFormatCode('yyyy-mm-dd');
$sheet->getStyle("K2:K{$lastRow}")
      ->getNumberFormat()->setFormatCode('0');

// auto width
foreach (range('A','M') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// freeze header & AutoFilter
$sheet->freezePane('A2');
$sheet->setAutoFilter("A1:M{$lastRow}");

// output
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="riwayat_cuti.xlsx"');
IOFactory::createWriter($spreadsheet, 'Xlsx')->save('php://output');
exit;

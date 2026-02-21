<?php
require 'vendor/autoload.php';
include 'db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

// Ambil filter tanggal
$mulai   = $_GET['mulai']   ?? null;
$selesai = $_GET['selesai'] ?? null;

// Query baru: ambil data dari detail_st + st + karyawan
$sql = "
  SELECT
    k.nip,
    k.nama,
    k.golongan,
    k.bagian,
    k.jabatan,
    s.jenis_st,
    ds.nomor_st,
    ds.tgl_st,
    ds.perihal,
    ds.tgl_mulai,
    ds.tgl_selesai,
    ds.lokasi,
    ds.spd
  FROM detail_st ds
  JOIN karyawan k ON k.nip = ds.nip
  JOIN st s ON s.id_st = ds.id_st
  WHERE 1=1
";

$params = [];
$types  = '';
if (!empty($mulai))   { $sql .= " AND ds.tgl_mulai   >= ?"; $params[] = $mulai;   $types .= 's'; }
if (!empty($selesai)) { $sql .= " AND ds.tgl_selesai <= ?"; $params[] = $selesai; $types .= 's'; }

$sql .= " ORDER BY ds.tgl_mulai DESC";
$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

// Buat Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Riwayat Surat Tugas');

// Header kolom
$headers = [
  'No','NIP','Nama Karyawan','Pangkat/Golongan','Bagian/Bidang','Jabatan',
  'Jenis ST','Nomor ST','Tanggal ST','Perihal','Tanggal Mulai','Tanggal Selesai','Lokasi','SPD'
];
$sheet->fromArray($headers, null, 'A1');

// Style header
$sheet->getStyle('A1:N1')->applyFromArray([
  'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
  'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
  'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
  'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);

// Isi data
$row = 2; $no = 1;
while ($r = $result->fetch_assoc()) {
    $sheet->setCellValue("A{$row}", $no++);
    $sheet->setCellValueExplicit("B{$row}", (string)$r['nip'], DataType::TYPE_STRING);
    $sheet->setCellValue("C{$row}", $r['nama']);
    $sheet->setCellValue("D{$row}", $r['golongan']);
    $sheet->setCellValue("E{$row}", $r['bagian']);
    $sheet->setCellValue("F{$row}", $r['jabatan']);
    $sheet->setCellValue("G{$row}", $r['jenis_st']);
    $sheet->setCellValue("H{$row}", $r['nomor_st']);
    if (!empty($r['tgl_st'])) $sheet->setCellValue("I{$row}", ExcelDate::PHPToExcel(strtotime($r['tgl_st'])));
    $sheet->setCellValue("J{$row}", $r['perihal']);
    if (!empty($r['tgl_mulai']))   $sheet->setCellValue("K{$row}", ExcelDate::PHPToExcel(strtotime($r['tgl_mulai'])));
    if (!empty($r['tgl_selesai'])) $sheet->setCellValue("L{$row}", ExcelDate::PHPToExcel(strtotime($r['tgl_selesai'])));
    $sheet->setCellValue("M{$row}", $r['lokasi']);
    $sheet->setCellValue("N{$row}", $r['spd']);

    $rowColor = ($row % 2 === 0) ? 'F7F7F7' : 'FFFFFF';
    $sheet->getStyle("A{$row}:N{$row}")->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rowColor]],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    ]);
    $row++;
}
$lastRow = max($row - 1, 2);

// Format tanggal
$sheet->getStyle("I2:L{$lastRow}")
      ->getNumberFormat()->setFormatCode('yyyy-mm-dd');

// Auto width
foreach (range('A', 'N') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Freeze header dan filter
$sheet->freezePane('A2');
$sheet->setAutoFilter("A1:N{$lastRow}");

// Output Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="riwayat_surat_tugas.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

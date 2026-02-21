<?php
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Google\Client;
use Google\Service\Drive;

// Konfigurasi koneksi database
$host = 'localhost';
$dbname = 'presensi';
$username = 'root';
$password = '';

// Path file JSON kredensial untuk Google Drive
$credentialsPath = __DIR__ . '/credentials.json';

$currentDate = strftime("%d %B %Y");
$fileName = "presensi_" . $currentDate . ".xlsx";
$filePath = __DIR__ . '/' . $fileName;

try {
    // Koneksi ke database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query untuk mendapatkan data Cuti (hanya yang mencakup hari ini)
    $queryCuti = "SELECT 
                    p.karyawan_nip,
                    k.golongan, 
                    k.nama, 
                    k.bagian, 
                    k.jabatan, 
                    p.tanggal_mulai, 
                    p.tanggal_selesai, 
                    p.jenis_cuti,
                    p.tahun, 
                    p.alasan 
                  FROM presensi p 
                  JOIN karyawan k ON p.karyawan_nip = k.nip 
                  WHERE p.status = 'Cuti' 
                  AND p.tanggal_mulai <= CURDATE() 
                  AND p.tanggal_selesai >= CURDATE()";

    // Query untuk mendapatkan data Surat Tugas (hanya yang mencakup hari ini)
    $querySuratTugas = "SELECT 
                          p.karyawan_nip, 
                          k.nama, 
                          k.bagian, 
                          k.jabatan, 
                          p.tanggal_mulai, 
                          p.tanggal_selesai,
                          k.golongan, 
                          p.nomor_st,
                          p.tanggal_st,
                          p.lokasi,
                          p.spd, 
                          p.perihal 
                        FROM presensi p 
                        JOIN karyawan k ON p.karyawan_nip = k.nip 
                        WHERE p.status = 'Surat Tugas' 
                        AND p.tanggal_mulai <= CURDATE() 
                        AND p.tanggal_selesai >= CURDATE()";

    $stmtCuti = $pdo->query($queryCuti);
    $stmtSuratTugas = $pdo->query($querySuratTugas);

    // Membuat Spreadsheet
    $spreadsheet = new Spreadsheet();

    // Definisi gaya
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4CAF50']],
        'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
    ];
    $borderStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000'],
            ],
        ],
    ];

    // Sheet 1: Karyawan Cuti
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Pegawai Cuti');
    $sheet->fromArray(['No', 'Nama', 'NIP', 'Pangkat/Golongan', 'Bagian/Bidang', 'Jabatan', 'Jenis Cuti', 'Tanggal Mulai', 'Tanggal Selesai', 'Tahun Cuti', 'Alasan'], null, 'A1');
    $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

    $rowNumber = 2;
    $no = 1;
    while ($row = $stmtCuti->fetch(PDO::FETCH_ASSOC)) {
        $sheet->setCellValue('A' . $rowNumber, $no++);
        $sheet->setCellValue('B' . $rowNumber, $row['nama']);
        $sheet->setCellValueExplicit('C' . $rowNumber, $row['karyawan_nip'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('D' . $rowNumber, $row['golongan']);
        $sheet->setCellValue('E' . $rowNumber, $row['bagian']);
        $sheet->setCellValue('F' . $rowNumber, $row['jabatan']);
        $sheet->setCellValue('G' . $rowNumber, $row['jenis_cuti']);
        $sheet->setCellValue('H' . $rowNumber, $row['tanggal_mulai']);
        $sheet->setCellValue('I' . $rowNumber, $row['tanggal_selesai']);
        $sheet->setCellValue('J' . $rowNumber, $row['tahun']);
        $sheet->setCellValue('K' . $rowNumber, $row['alasan']);
        $rowNumber++;
    }
    $sheet->getStyle("A1:K" . ($rowNumber - 1))->applyFromArray($borderStyle);
    foreach (range('A', 'K') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Sheet 2: Karyawan Surat Tugas
    $sheet2 = $spreadsheet->createSheet();
    $sheet2->setTitle('Pegawai Surat Tugas');
    $sheet2->fromArray(['No', 'Nama', 'NIP', 'Pangkat/Golongan', 'Bagian/Bidang', 'Jabatan', 'Nomor ST', 'Tanggal ST', "Perihal", 'Tanggal Mulai', 'Tanggal Selesai', 'Lokasi', 'SPD'], null, 'A1');
    $sheet2->getStyle('A1:M1')->applyFromArray($headerStyle);

    $rowNumber = 2;
    $no = 1;
    while ($row = $stmtSuratTugas->fetch(PDO::FETCH_ASSOC)) {
        $sheet2->setCellValue('A' . $rowNumber, $no++);
        $sheet2->setCellValue('B' . $rowNumber, $row['nama']);
        $sheet2->setCellValueExplicit('C' . $rowNumber, $row['karyawan_nip'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet2->setCellValue('D' . $rowNumber, $row['golongan']);
        $sheet2->setCellValue('E' . $rowNumber, $row['bagian']);
        $sheet2->setCellValue('F' . $rowNumber, $row['jabatan']);
        $sheet2->setCellValue('G' . $rowNumber, $row['nomor_st']);
        $sheet2->setCellValue('H' . $rowNumber, $row['tanggal_st']);
        $sheet2->setCellValue('I' . $rowNumber, $row['perihal']);
        $sheet2->setCellValue('J' . $rowNumber, $row['tanggal_mulai']);
        $sheet2->setCellValue('K' . $rowNumber, $row['tanggal_selesai']);
        $sheet2->setCellValue('L' . $rowNumber, $row['lokasi']);
        $sheet2->setCellValue('M' . $rowNumber, $row['spd']);
        $rowNumber++;
    }
    $sheet2->getStyle("A1:M" . ($rowNumber - 1))->applyFromArray($borderStyle);
    foreach (range('A', 'M') as $col) {
        $sheet2->getColumnDimension($col)->setAutoSize(true);
    }

    // Simpan file Excel
    $writer = new Xlsx($spreadsheet);
    $writer->save($filePath);

    // Konfigurasi Google API
    $client = new Client();
    $client->setApplicationName("Upload File ke Google Drive");
    $client->setScopes([Drive::DRIVE_FILE]);
    $client->setAuthConfig($credentialsPath);
    $client->setAccessType('offline');

    $service = new Drive($client);

    $fileMetadata = new Drive\DriveFile([
        'name' => $fileName,
        'parents' => ['1uC7HMh5YXvDA411wZrnHp5HdHZyCTWNK'] // Folder ID
    ]);

    $content = file_get_contents($filePath);

    $file = $service->files->create($fileMetadata, [
        'data' => $content,
        'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'uploadType' => 'multipart',
        'fields' => 'id'
    ]);
    
    
    // Hapus file lokal setelah diunggah
    unlink($filePath);

    echo "File berhasil diunggah ke Google Drive" ;
} catch (PDOException $e) {
    echo "Error saat koneksi database: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error saat membuat atau mengunggah XLSX: " . $e->getMessage();
}
?>

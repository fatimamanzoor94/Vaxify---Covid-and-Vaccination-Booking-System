<?php
include '../includes/auth.php';
include '../includes/db_connect.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

$report_type = isset($_GET['type']) ? $_GET['type'] : 'daily';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

try {
    if ($report_type == 'weekly') {
        $start_date = date('Y-m-d', strtotime($date . ' -6 days'));
        $query = "SELECT p.name, ct.test_date, ct.result FROM covid_tests ct 
                  JOIN patients p ON ct.patient_id = p.id 
                  WHERE ct.test_date BETWEEN ? AND ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$start_date, $date]);
    } elseif ($report_type == 'monthly') {
        $start_date = date('Y-m-01', strtotime($date));
        $query = "SELECT p.name, ct.test_date, ct.result FROM covid_tests ct 
                  JOIN patients p ON ct.patient_id = p.id 
                  WHERE ct.test_date BETWEEN ? AND ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$start_date, $date]);
    } else {
        $query = "SELECT p.name, ct.test_date, ct.result FROM covid_tests ct 
                  JOIN patients p ON ct.patient_id = p.id 
                  WHERE ct.test_date = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$date]);
    }
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error generating report: " . $e->getMessage());
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Patient Name');
$sheet->setCellValue('B1', 'Test Date');
$sheet->setCellValue('C1', 'Result');

$row = 2;
foreach ($reports as $report) {
    $sheet->setCellValue('A' . $row, $report['name']);
    $sheet->setCellValue('B' . $row, $report['test_date']);
    $sheet->setCellValue('C' . $row, $report['result']);
    $row++;
}

$writer = new Xls($spreadsheet);
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="covid_report_' . $report_type . '_' . $date . '.xls"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit;
<?php
// google-sheets-proxy.php
// Proxy untuk Google Sheets API dengan Service Account

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load library dari vendor
require_once __DIR__ . '/vendor/autoload.php';

// Tentukan lokasi file kredensial
putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/absensi-siswa.json');

// Inisialisasi Google Client
$client = new Google_Client();
$client->useApplicationDefaultCredentials();
$client->addScope(Google_Service_Sheets::SPREADSHEETS);

$service = new Google_Service_Sheets($client);

// Ambil parameter
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$spreadsheetId = $_POST['spreadsheetId'] ?? $_GET['spreadsheetId'] ?? '';
$range = $_POST['range'] ?? $_GET['range'] ?? '';
$values = json_decode($_POST['values'] ?? '[]', true);

// Log untuk debugging
error_log("Action: $action, SpreadsheetId: $spreadsheetId, Range: $range");

try {
    if ($action === 'append') {
        // Menambah data baru
        $body = new Google_Service_Sheets_ValueRange([
            'values' => [$values]
        ]);
        $params = ['valueInputOption' => 'USER_ENTERED'];
        $result = $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'updates' => $result->getUpdates(),
                'spreadsheetId' => $spreadsheetId,
                'tableRange' => $result->getTableRange()
            ]
        ]);
        
    } elseif ($action === 'update') {
        // Mengupdate data yang sudah ada
        $body = new Google_Service_Sheets_ValueRange([
            'values' => [$values]
        ]);
        $params = ['valueInputOption' => 'USER_ENTERED'];
        $result = $service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'updates' => $result->getUpdates(),
                'spreadsheetId' => $spreadsheetId
            ]
        ]);
        
    } elseif ($action === 'get') {
        // Membaca data
        $result = $service->spreadsheets_values->get($spreadsheetId, $range);
        
        echo json_encode([
            'success' => true,
            'values' => $result->getValues()
        ]);
        
    } elseif ($action === 'batchUpdate') {
        // Update banyak data sekaligus
        $body = new Google_Service_Sheets_ValueRange([
            'values' => $values
        ]);
        $params = ['valueInputOption' => 'USER_ENTERED'];
        $result = $service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'updates' => $result->getUpdates()
            ]
        ]);
        
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Action tidak dikenal: ' . $action
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
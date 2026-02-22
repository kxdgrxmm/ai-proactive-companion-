<?php
// add_patient.php
header('Content-Type: application/json');

try {
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid data received');
    }
    
    // Here you would save to your database
    // For now, just log it
    $logFile = 'patients.log';
    $logEntry = date('Y-m-d H:i:s') . ' - ' . json_encode($data) . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    // You can also save to a JSON file
    $patientsFile = 'patients.json';
    $patients = [];
    
    if (file_exists($patientsFile)) {
        $patients = json_decode(file_get_contents($patientsFile), true) ?: [];
    }
    
    $patients[] = $data;
    file_put_contents($patientsFile, json_encode($patients, JSON_PRETTY_PRINT));
    
    echo json_encode(['success' => true, 'message' => 'Patient saved successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
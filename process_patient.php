<?php
// process_patient.php
session_start();

$uploadDir = "uploads/";

// Absolute paths (no PATH, no admin needed) - keep as is from your code
$magick    = '"C:\\xampp\\imagemagick\\magick.exe"';
$tesseract = '"C:\\Program Files\\Tesseract-OCR\\tesseract.exe"';

// Get file from session or URL parameter
$filename = $_GET['file'] ?? ($_SESSION['last_uploaded']['saved_name'] ?? null);

if (!$filename) {
    die("No file specified");
}

$filePath = $uploadDir . $filename;

if (!file_exists($filePath)) {
    die("File not found");
}

$ocrResult = null;
$extractedText = "";

if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === "pdf") {

    // Use unique temp names per file
    $baseName  = pathinfo($filename, PATHINFO_FILENAME);
    $imagePath = "uploads/{$baseName}.png";
    $textBase  = "uploads/{$baseName}_ocr";

    // Cleanup old files
    @unlink($imagePath);
    @unlink($textBase . ".txt");

    // 1. PDF → Image (300 DPI = better OCR)
    $cmd1 = "$magick -density 300 \"$filePath\" \"$imagePath\" 2>&1";
    $out1 = shell_exec($cmd1);

    // Check image creation
    if (file_exists($imagePath)) {
        // 2. Image → Text (OCR)
        $cmd2 = "$tesseract \"$imagePath\" \"$textBase\" 2>&1";
        $out2 = shell_exec($cmd2);

        // 3. Read OCR text
        if (file_exists($textBase . ".txt")) {
            $extractedText = file_get_contents($textBase . ".txt");
            $ocrResult = true;
        } else {
            $ocrResult = false;
            $error = "OCR failed";
        }
    } else {
        $ocrResult = false;
        $error = "PDF to image conversion failed";
    }
}

// Store extracted data in session for summary page
$_SESSION['patient_data'] = [
    'filename' => $filename,
    'original_name' => $_SESSION['last_uploaded']['original_name'] ?? $filename,
    'extracted_text' => $extractedText,
    'ocr_success' => $ocrResult
];

// Return as JSON if requested
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $ocrResult,
        'filename' => $filename,
        'extracted_text' => $extractedText
    ]);
    exit;
}
?>
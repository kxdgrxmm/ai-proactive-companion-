<?php
// upload_handler.php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = ['success' => false, 'error' => 'Unknown error'];

try {
    // Check if file was uploaded
    if (!isset($_FILES['patient_file'])) {
        throw new Exception('No file received. Make sure form has enctype="multipart/form-data"');
    }

    $file = $_FILES['patient_file'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        $errorMsg = $uploadErrors[$file['error']] ?? 'Unknown upload error';
        throw new Exception('Upload error: ' . $errorMsg);
    }

    $originalName = $file['name'];
    $tmpName = $file['tmp_name'];
    $fileSize = $file['size'];

    // Validate file type
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($ext !== "pdf") {
        throw new Exception('Only PDF files are allowed. Got: ' . $ext);
    }

    // Validate file size (5MB)
    $maxSize = 5 * 1024 * 1024;
    if ($fileSize > $maxSize) {
        throw new Exception('File too large. Max 5MB. Size: ' . round($fileSize / 1024 / 1024, 2) . 'MB');
    }

    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/uploads/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }

    // Check if directory is writable
    if (!is_writable($uploadDir)) {
        throw new Exception('Upload directory is not writable');
    }

    // Generate unique filename
    $newName = uniqid("patient_", true) . ".pdf";
    $destination = $uploadDir . $newName;

    if (move_uploaded_file($tmpName, $destination)) {
        // Start session to store file info
        session_start();
        $_SESSION['last_upload'] = [
            'filename' => $newName,
            'original' => $originalName,
            'path' => $destination,
            'timestamp' => time()
        ];
        
        $response = [
            'success' => true,
            'message' => 'File uploaded successfully',
            'filename' => $newName,
            'original' => $originalName
        ];
    } else {
        throw new Exception('Failed to move uploaded file. Check permissions.');
    }

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>
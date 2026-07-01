<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\NameSimplifier;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pasted_image'])) {
    
    $rawData  = $_POST['pasted_image'];
    $canId    = $_GET['can_id'] ?? '';
    $name     = $_GET['name']   ?? '';
    $name     = NameSimplifier::makeFilenameFrom($name);

    // Validate that it is a proper base64 data URI image
    if (preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $rawData, $matches)) {
        
        $extension = $matches[1]; // Get extension (e.g., png)
        
        // Remove the metadata header to leave only the raw base64 string
        $filteredData = substr($rawData, strpos($rawData, ',') + 1);
        
        // Decode string back into binary image file
        $decodedData = base64_decode($filteredData);
        
        // Save the file with a unique name in your directory
        $fileName = $canId . "-" . $name . "-pasted." . $extension;
        
        if (file_put_contents("PHOTOS_CAN/$fileName", $decodedData)) {
            echo json_encode(['success' => true, 'file' => $fileName]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Could not save file.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid image format.']);
    }
    exit;
}

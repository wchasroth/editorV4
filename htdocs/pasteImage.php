<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\NameSimplifier;
use CharlesRothDotNet\Alfred\DumbFileLogger;
use CharlesRothDotNet\Alfred\Html;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\HttpGet;

require_once('../vendor/autoload.php');

$env     = new EnvFile("_env");
$pdo     = PdoHelper::makePdo($env);
$logger  = new DumbFileLogger($env->get('logFile'));
$email   = EnvHelper::getEmail($env);
$row     = EnvHelper::getUserPermissions($pdo, $email);
if (count($row) === 0)  exit();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pasted_image'])) {
    $canId    = HttpGet::number('can_id');
    $name     = Html::removeHtmlTags($_GET['name']   ?? '');
    $name     = NameSimplifier::makeFilenameFrom($name);
    $rawData  = $_POST['pasted_image'];

    // Validate that it is a proper base64 data URI image
    if (preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,/', $rawData, $matches)) {
        
        $extension = $matches[1]; // Get extension (e.g., png)
        
        // Remove the metadata header to leave only the raw base64 string
        $filteredData = substr($rawData, strpos($rawData, ',') + 1);
        
        // Decode string back into binary image file
        $decodedData = base64_decode($filteredData);
        
        // Save the file with a unique name in your directory
        $fileName = $canId . "-" . $name . "-pasted." . $extension;
        
        error_clear_last();
        if (file_put_contents("PHOTOS_CAN/$fileName", $decodedData)) {
           $logger->log("success");
            echo json_encode(['success' => true, 'file' => $fileName]);
        } else {
           $error = error_get_last();
           $logger->log("could not save: " . $error['message'] ?? 'none');
           echo json_encode(['success' => false, 'error' => 'Could not save file.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid image format.']);
        $logger->log("invalid image format");
    }
}
exit;

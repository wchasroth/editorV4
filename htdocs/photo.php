<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\FieldFormatFixer;
use CharlesRothDotNet\Alfred\NameSimplifier;
use CharlesRothDotNet\Alfred\PdoRunResult;
use CharlesRothDotNet\Alfred\SqlFields;
use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\Alfred\HttpGet;
use CharlesRothDotNet\Alfred\HttpPost;
use CharlesRothDotNet\Alfred\SmartyPage;
use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\DumbFileLogger;

require_once('../vendor/autoload.php');

date_default_timezone_set("America/New_York");

$env       = new EnvFile("_env");
$email     = EnvHelper::getEmail($env);
$pdo       = PdoHelper::makePdo($env);
$logger    = new DumbFileLogger($env->get('logFile'));
$parent    = $env->get('parent');
$photosDir = $env->get('photosCanDir');

$canId      = $_GET['canId']      ?? '';
$name       = $_GET['name']       ?? '';
$headshot   = $_GET['headshot']   ?? '';
$usecropped = $_GET['usecropped'] ?? '0';
$photoChanged = 0;

$headcropped = 0;
$cropshot = "";
if (intval($usecropped) === 1) {
   $photoChanged = 1;
}
else {
   $sql = "SELECT headcropped FROM v4candidates WHERE id=$canId";
   $headcropped = $pdo->run($sql)->getSingleValue('headcropped');
}
if ($headcropped == 1) {
   $base = Str::substringBeforeLast($headshot, ".");
   $ext  = Str::substringAfterLast ($headshot, ".");
   $cropshot = "$base-cropped.$ext";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'  &&  isset($_FILES['uploadphoto'])) {
   $uploadedFile = $_FILES['uploadphoto'];
   $logger->log("Uploaded tmp file: " . $uploadedFile['tmp_name']);
   if ($uploadedFile['error'] === UPLOAD_ERR_OK) {
      $filename = $uploadedFile['name'];
      $filename = NameSimplifier::makeFilenameFrom($filename);
      $target   = $canId . "-" . $filename;
      $got = move_uploaded_file($uploadedFile["tmp_name"], "$photosDir/$target");
      $logger->log("Move status: " . ($got ? 'T' : 'F') . "  " . $uploadedFile['tmp_name'] . " to $photosDir/$target");
      if ($got) {
         $headshot = $target;
         $photoChanged = 1;
         $usecropped   = 1;
      }
   }
}

$smarty = new SmartyPage();
$smarty->assign('canId',    $canId);
$smarty->assign('headcropped', $headcropped);
$smarty->assign('usecropped',  $usecropped);
$smarty->assign('name',     $name);
$smarty->assign('encodedName', rawurlencode($name));
$smarty->assign('headshot', $headshot);
$smarty->assign('cropshot', $cropshot);
$smarty->assign('parent',   $parent);
$smarty->assign('photoChanged',   $photoChanged);
$smarty->display('photo.tpl');

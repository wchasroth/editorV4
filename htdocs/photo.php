<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\AlfredPDO;
use CharlesRothDotNet\Alfred\FieldFormatFixer;
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
$photosDir = $env->get('photosDir');

$canId    = $_GET['canId']    ?? '';
$name     = $_GET['name']     ?? '';
$headshot = $_GET['headshot'] ?? '';
$photoChanged = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST'  &&  isset($_FILES['uploadphoto'])) {
   $uploadedFile = $_FILES['uploadphoto'];
   $logger->log("Uploaded tmp file: " . $uploadedFile['tmp_name']);
   if ($uploadedFile['error'] === UPLOAD_ERR_OK) {
      $filename = $uploadedFile['name'];
      $badChars = str_split("` ~!@#$%^&*()_+=-[]{}\\\"|;:'?,<>");
      $filename = str_replace($badChars, '_', $filename);
      $target   = $canId . "-" . $filename;
      $got = move_uploaded_file($uploadedFile["tmp_name"], "$photosDir/$target");
      $logger->log("Move status: " . ($got ? 'T' : 'F') . "  " . $uploadedFile['tmp_name'] . " to $photosDir/$target");
      if ($got) {
         $headshot = $target;
         $photoChanged = 1;
      }
   }
}

$smarty = new SmartyPage();
$smarty->assign('canId',    $canId);
$smarty->assign('name',     $name);
$smarty->assign('encodedName', rawurlencode($name));
$smarty->assign('headshot', $headshot);
$smarty->assign('parent',   $parent);
$smarty->assign('photoChanged',   $photoChanged);
$smarty->display('photo.tpl');

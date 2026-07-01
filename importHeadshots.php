<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\Alfred\NameSimplifier;

require_once('vendor/autoload.php');
$name = Str::replaceAll($name, ' ', '_');

// importHeadshots.php
//    Get the candidate headshots from the internet, and put them in our local file directory.
//
//    For each v4candidates entry that has a headshot_url, but DOES NOT have a headshot file,
//    copy the image file from headshot_url to our local directory, and fill in the
//    headshot value in that row.

$env      = new EnvFile("_env");
$pdo      = PdoHelper::makePdo($env);
$dir      = $env->get('photosCanDir');
$python   = $env->get('python');
$cropface = $env->get('cropface');

$grabber = new PhotoGrabber($dir, $python, $cropface);

$sql = "SELECT id, name, headshot_url FROM v4candidates WHERE headshot='' AND headshot_url != ''";
$queryResult = $pdo->run($sql);
if ($queryResult->failed())  fwrite (STDERR, "Headshot query failed: $sql\n");

foreach ($queryResult->getRows() as $row) {
   $name = NameSimplifier::makeFilenameFrom($row['name']);
   $nameBase = $row['id'] . "-$name";
   $photo = $grabber->downloadPhoto($row['headshot_url'], $nameBase, "$nameBase-cropped", true);
   if (empty($photo->getName())) {
      fwrite(STDERR, "$nameBase {$row['headshot_url']} " . $photo->getError() . "\n");
      continue;
   }

   $sql = "UPDATE v4candidates SET headshot='{$photo->getName()}' "
        . (! empty($photo->getCroppedName()) ? ", headcropped=1 " : "")
        . " WHERE id={$row['id']}";
   $result = $pdo->run($sql);
   if ($result->failed()) fwrite (STDERR, "Error updating headshot: $sql\n");
}

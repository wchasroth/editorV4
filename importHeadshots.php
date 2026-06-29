<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\Str;

require_once('vendor/autoload.php');

// importHeadshots.php
//    Get the candidate headshots from the internet, and put them in our local file directory.
//
//    For each v4candidates entry that has a headshot_url, but DOES NOT have a headshot file,
//    copy the image file from headshot_url to our local directory, and fill in the
//    headshot value in that row.

$env  = new EnvFile("_env");
$pdo  = PdoHelper::makePdo($env);
$dir  = $env->get('photosCanDir');

$sql = "SELECT id, name, headshot_url FROM v4candidates WHERE headshot='' AND headshot_url != ''";
$queryResult = $pdo->run($sql);
if ($queryResult->failed())  fwrite (STDERR, "Headshot query failed: $sql\n");

foreach ($queryResult->getRows() as $row) {
   $name = NameSimplifier::simplify($row['name']);
   $name = Str::replaceAll($name, ' ', '_');
   echo "$name  " . $row['headshot_url'] . "\n";
   $photoResult = PhotoGrabber::downloadPhoto(strval($row['id']), $name, $dir, $row['headshot_url'], false);
   if (! Str::startsWith($photoResult, "OK ")) {
      fwrite(STDERR, "$name $photoResult " . $row['headshot_url'] . "\n");
      continue;
   }

   $filename = Str::substringAfter($photoResult, "OK ");
   $sql = "UPDATE v4candidates SET headshot='$filename' WHERE id={$row['id']}";
   $result = $pdo->run($sql);
   if ($result->failed()) fwrite (STDERR, "Error updating headshot: $sql\n");
}

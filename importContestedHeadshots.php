<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\EnvFile;
use CharlesRothDotNet\Alfred\PdoHelper;
use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\Alfred\NameSimplifier;

require_once('vendor/autoload.php');

// importContestedHeadshots.php
//    Get the candidate headshots FOR CONTESTED races, from the internet, and put them in our local file directory.
//
//    Note that importedHeadshots.php only handles the UNCONTESTED races.  This is because only they have
//    a v4candidates id value.
//
//    But we still need the headshots for the contested races -- so this script grabs them, and
//    then stores them in the same directory -- but using the v4filings id field instead, which is
//    a non-numeric key that is easily recognizable.

$env      = new EnvFile("_env");
$pdo      = PdoHelper::makePdo($env);
$dir      = $env->get('photosCanDir');
$python   = $env->get('python');
$cropface = $env->get('cropface');

$grabber = new PhotoGrabber($dir, $python, $cropface);

$sql = "SELECT id, name, headshot_url FROM v4filings WHERE headshot = '' AND headshot_url != '' AND contested=1";
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

   $sql = "UPDATE v4filings SET headshot='{$photo->getName()}' "
        . (! empty($photo->getCroppedName()) ? ", headcropped=1 " : "")
        . " WHERE id='{$row['id']}'";
   $result = $pdo->run($sql);
   if ($result->failed()) fwrite (STDERR, "Error updating headshot: $sql\n");
}

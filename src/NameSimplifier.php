<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\Str;

class NameSimplifier {
   public static function simplify (string $name): string {
      $name = strtolower($name);
      $name = preg_replace('/[,()]/', '', $name);
      $name = Str::replaceAll($name, '-', ' ');

      $words = Str::splitIntoTokens($name, ' ');
      $keep  = [];
      foreach ($words as $word) {
         if (Str::endsWith($word, '.')  &&  strlen($word) <= 3)  continue;
         $word = Str::replaceAll($word, '.', '');
         if ($word === 'iii'  ||  $word === 'iv')  continue;
         $keep[] = $word;
      }

      sort($keep);
      return Str::join($keep, ' ');
   }

}
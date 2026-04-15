<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\CookieBoss;
use CharlesRothDotNet\Alfred\CookieVerifier;
use CharlesRothDotNet\Alfred\EnvFile;

class EnvHelper {
   public static function getEmail(EnvFile $env): string {
      if ($env->get('isLocal') == "1") return "wchasroth@gmail.com";
      $boss = new CookieBoss($env->get('domain'), $env->get('cookie_path'), $env->get('securekey'));
      return CookieVerifier::getEmail($boss, $env->get('cookie'));
   }

   public static function getEditableCounties(EnvFile $env): string {
      static $allCounties = "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,"
              . "45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,999";
      if ($env->get('isLocal') == "1") return $allCounties;

      $boss = new CookieBoss($env->get('domain'), $env->get('cookie_path'), $env->get('securekey'));
      return $boss->getValueFromHashedCookie($env->get('counties'));
   }
//
//   public static function getReadall(EnvFile $env): string {
//      $boss = new CookieBoss($env->get('domain'), $env->get('cookie_path'), $env->get('securekey'));
//      return $boss->getValueFromHashedCookie('readall');
//   }

}
<?php

declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

use CharlesRothDotNet\Alfred\MichiganCounties;
use CharlesRothDotNet\Alfred\Str;
use CharlesRothDotNet\Alfred\AlfredPDO;

class EntityNamer {
   public static function getName(AlfredPDO $pdo, array $orgs, string $district): string {
      switch ($orgs[0]) {
         case "'us'":      return "United States";
         case "'mi'":      return "State of Michigan";
         case "'mi-sen'":  return "Michigan Senate";
         case "'mi-hou'":  return "Michigan House";
         case "'mi-boe'":  return "State and University Education Boards";
      }
      if (str::startswith($orgs[0], "'vil'")) {
         $sql = "select name from s4villages where id=$district";
         $result = $pdo->run($sql);
         $name = self::correctCase($result->getsinglevalue('name'));
         if (! str::contains(strtolower($name), "village")) $name = "village of $name";
         return $name;
      }
      if (str::startswith($orgs[0], "'comcol")) {
         $sql = "select name from s4commcolleges where id=$district";
         $result = $pdo->run($sql);
         $name = $result->getsinglevalue('name');
         return self::correctCase($name);
      }
      if (str::startswith($orgs[0], "'town")) {
         $sql = "select name from s4jurisdictions where type='t' and id=$district";
         $result = $pdo->run($sql);
         $name = $result->getsinglevalue('name');
         return self::correctCase($name);
      }
      if (str::startswith($orgs[0], "'city")) {
         $sql = "select name from s4jurisdictions where type='c' and id=$district";
         $result = $pdo->run($sql);
         $name = $result->getsinglevalue('name');
         return self::correctCase($name);
      }
      if (Str::startsWith($orgs[0], "'schl")) {
         $sql = "SELECT name FROM s4schools WHERE  id=$district";
         $result = $pdo->run($sql);
         $name = $result->getSingleValue('name');
         return self::correctCase($name);
      }
      if (Str::startsWith($orgs[0], "'cnty")) {
         $mc = new MichiganCounties();
         $name = $mc->getName(intval($district));
         return ucwords($name) . " County";
      }
      if ($orgs[0] === "'crt-a'") return "#$district Appeals Court";
      if ($orgs[0] === "'crt-c'") return "#$district Circuit Court";
      if ($orgs[0] === "'crt-d'") return "#$district District Court";
      if ($orgs[0] === "'crt-p'") return "#$district Probate Court";

      return "No Name Found";
   }

   public static function correctCase(?string $name): string {
      if (Str::isReallyEmpty($name))  return "";
      $upper = strtoupper($name);
      if ($upper != $name)  return $name;
      return ucwords(strtolower($name));
   }

}

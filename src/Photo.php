<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

class Photo {
   private string $name;
   private string $croppedName;
   private string $error;

   function __construct(string $name, string $croppedName, string $error) {
      $this->name = $name;
      $this->croppedName = $croppedName;
      $this->error = $error;
   }

   public function getName(): string {
      return $this->name;
   }

   public function getCroppedName(): string {
      return $this->croppedName;
   }

   public function getError(): string {
      return $this->error;
   }

}
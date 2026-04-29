<?php

namespace Drupal\product_interactions\TwigExtension;

use Twig\Sandbox\SecurityPolicyInterface;

class ProductInteractionsSandboxPolicy implements SecurityPolicyInterface {

  public function checkSecurity($tags, $filters, $functions): void {
    // Allow all - Drupal's own policy handles the rest
  }

  public function checkMethodAllowed($obj, $method): void {}

  public function checkPropertyAllowed($obj, $property): void {}
}
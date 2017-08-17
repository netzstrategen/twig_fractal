<?php

namespace Drupal\twig_fractal;

use Drupal\twig_fractal\TokenParser\Render;
use Twig_Extension;

class TwigFractal extends Twig_Extension {

  public function getName() {
    return 'twig_fractal';
  }

  public function getTokenParsers() {
    return [
      new Render(),
    ];
  }

}

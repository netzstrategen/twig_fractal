<?php

/**
 * @file
 * Contains \Drupal\twig_fractal\TwigFractal.
 */

namespace Drupal\twig_fractal;

use Drupal\twig_fractal\TokenParser\Render;
use Twig_Extension;

/**
 * Registers a new `render` token parser to make Fractal components reusable in Drupal.
 *
 * @see \Drupal\twig_fractal\TokenParser\Render
 *
 * @implements Twig_ExtensionInterface
 */
class TwigFractal extends Twig_Extension {

  /**
   * Adds a new Render token parser instance to the list of parsers.
   *
   * @return array
   *   The token parsers.
   */
  public function getTokenParsers() {
    return [
      new Render(),
    ];
  }

  /**
   * Returns the extension name.
   *
   * @return string
   *   The extension name.
   */
  public function getName() {
    return 'twig_fractal';
  }

}

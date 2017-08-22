<?php
/**
 * @file
 * Contains Drupal\twig_fractal\TwigFractal.
 */

namespace Drupal\twig_fractal;

use Drupal\twig_fractal\TokenParser\Render;
use Twig_Extension;

/**
 * This class registers a custom `render` token parser to make Fractal components reusable in Drupal 8.
 *
 * @implements Twig_ExtensionInterface
 *
 * @see \Drupal\twig_fractal\TokenParser\Render
 */
class TwigFractal extends Twig_Extension {

  /**
   * Returns the `Render` token parser instance and add it to the existing list.
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

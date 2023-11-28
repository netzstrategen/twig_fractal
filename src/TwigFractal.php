<?php

/**
 * @file
 * Contains \Drupal\twig_fractal\TwigFractal.
 */

namespace Drupal\twig_fractal;

use Drupal\twig_fractal\TokenParser\Render;
use Drupal\twig_fractal\NodeVisitors\Attributes;
use Twig\Extension\AbstractExtension;

/**
 * Registers a new `render` token parser to make Fractal components reusable in Drupal.
 *
 * @see \Drupal\twig_fractal\TokenParser\Render
 *
 * @implements Twig\Extension\ExtensionInterface
 */
class TwigFractal extends AbstractExtension {

  /**
   * Adds a new Render token parser instance to the list of parsers.
   *
   * @return array
   *   The token parsers.
   */
  public function getTokenParsers(): array {
    return [
      new Render(),
    ];
  }

  public function getNodeVisitors() {
    return [
      new Attributes(),
    ];
  }

  /**
   * Returns the extension name.
   *
   * @return string
   *   The extension name.
   */
  public function getName(): string {
    return 'twig_fractal';
  }

}

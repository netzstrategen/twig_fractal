<?php

/**
 * @file
 * Contains \Drupal\twig_fractal\TwigFractal.
 */

namespace Drupal\twig_fractal;

use Drupal\Core\Template\Attribute;
use Drupal\twig_fractal\TokenParser\Render;
use Drupal\twig_fractal\NodeVisitors\Attributes;
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

  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('create_attribute', [$this, 'createAttribute']),
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

  /**
   * Creates an Attribute object.
   *
   * @param array $attributes
   *   (optional) An associative array of key-value pairs to be converted to
   *   HTML attributes.
   *
   * @return \Drupal\Core\Template\Attribute
   *   An attributes object that has the given attributes.
   */
  public function createAttribute(array $attributes = []) {
    return new Attribute($attributes);
  }

}

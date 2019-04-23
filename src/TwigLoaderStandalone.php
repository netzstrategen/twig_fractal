<?php

/**
 * @file
 * Contains \Drupal\twig_fractal\TwigLoader.
 */

namespace Drupal\twig_fractal;

use Twig\Loader\FilesystemLoader;

/**
 * Allows a subtheme to override Fractal components of its base theme.
 *
 * The filesystem path of a component that is overridden by the subtheme
 * is prepended to the lookup paths of the component, so that it is found
 * first.
 *
 * @see http://symfony.com/doc/current/templating/namespaced_paths.html#multiple-paths-per-namespace
 */
class TwigLoaderStandalone extends FilesystemLoader {

  public static $instance;

  /**
   * {@inheritdoc}
   */
  public function __construct($templateDir = '') {
    parent::__construct();
    static::$instance = $this;

    $component_libraries = [
      'atoms' => [$templateDir . '/components/1-atoms'],
      'molecules' => [$templateDir . '/components/2-molecules'],
      'organisms' => [$templateDir . '/components/3-organisms'],
      'templates' => [$templateDir . '/components/4-templates'],
      'pages' => [$templateDir . '/components/9-pages'],
      'assets' => [$templateDir . '/dist'],
    ];
    foreach ($component_libraries as $namespace => $component_paths) {
      foreach ($component_paths as $path) {
        $this->prependPath($path, $namespace);
      }
    }
    return $this;
  }

  public static function getInstance() {
    return static::$instance;
  }

  /**
   * {@inheritdoc}
   */
  public function prependPath($path, $namespace = self::MAIN_NAMESPACE) {
    $this->cache = $this->errorCache = [];

    $path = rtrim($path, '/\\');
    if (!isset($this->paths[$namespace])) {
      $this->paths[$namespace][] = $path;
    }
    else {
      array_unshift($this->paths[$namespace], $path);
    }
  }

}

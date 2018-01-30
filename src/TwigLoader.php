<?php

/**
 * @file
 * Contains \Drupal\twig_fractal\Component.
 */

namespace Drupal\twig_fractal;

use Drupal;
use Drupal\components\Template\Loader\ComponentLibraryLoader;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;

/**
 * Prepares paths to make fractal components overridable by a sub theme.
 *
 * To make components overridable by a sub theme, the existing namespace paths
 * are getting prepended with adjusted sub theme paths.
 *
 * @see http://symfony.com/doc/current/templating/namespaced_paths.html#multiple-paths-per-namespace
 */
class TwigLoader extends ComponentLibraryLoader {

  /**
   * Prepends sub theme component paths to the existing base theme path namespaces.
   *
   * {@inheritdoc}
   */
  public function __construct($paths = [], ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    parent::__construct($paths, $module_handler, $theme_handler);
    $default_theme = $theme_handler->getTheme($theme_handler->getDefault());
    if (!$default_theme->base_theme || !$base_theme = $theme_handler->getTheme($default_theme->base_theme)) {
      return;
    }
    foreach ($base_theme->info['component-libraries'] as $namespace => $component) {
      $paths = isset($component['paths']) ? $component['paths'] : [];
      foreach ($paths as $path) {
        $this->prependPath($default_theme->getPath() . '/' . $path, $namespace);
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function prependPath($path, $namespace = self::MAIN_NAMESPACE) {
    $this->cache = $this->errorCache = [];
    $path = rtrim($path, '/\\');
    if (!isset($this->paths[$namespace])) {
      $this->paths[$namespace][] = $path;
    } else {
      array_unshift($this->paths[$namespace], $path);
    }
  }

}

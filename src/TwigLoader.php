<?php

/**
 * @file
 * Contains \Drupal\twig_fractal\TwigLoader.
 */

namespace Drupal\twig_fractal;

use Drupal;
use Drupal\components\Template\ComponentsInfo;
use Drupal\components\Template\Loader\ComponentsLoader;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Allows a subtheme to override Fractal components of its base theme.
 *
 * The filesystem path of a component that is overridden by the subtheme
 * is prepended to the lookup paths of the component, so that it is found
 * first.
 *
 * @see http://symfony.com/doc/current/templating/namespaced_paths.html#multiple-paths-per-namespace
 */
class TwigLoader extends ComponentsLoader {

  /**
   * {@inheritdoc}
   */
  public function __construct(ThemeHandlerInterface $theme_handler,ComponentsInfo $components_info,
                              ThemeManagerInterface $theme_manager) {

    parent::__construct($components_info, $theme_manager);

    $default_theme = $theme_handler->getTheme($theme_handler->getDefault());
    if ($base_theme = $default_theme->base_theme) {
      $base_theme = $theme_handler->getTheme($base_theme);
    }
    if (!$base_theme || !isset($base_theme->info['component-libraries'])) {
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
    }
    else {
      array_unshift($this->paths[$namespace], $path);
    }
  }

}

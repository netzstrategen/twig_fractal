<?php

/**
 * @file
 * Contains \Drupal\twig_fractal\Component.
 */

namespace Drupal\twig_fractal;

use Drupal;
use Drupal\Component\Utility\Html;
use Symfony\Component\Yaml\Yaml;

/**
 * Prepares context variables for a Twig `render` node.
 *
 * Default component variables are recieved from a Fractal component definition
 * file and merged with the existing `render` node context variables.
 */
class Component {

  protected $env;

  /**
   * The pathname of the component to render.
   *
   * @var string
   */
  protected $pathname;

  /**
   * The pathname of the component template to render (possibly including
   * variant).
   *
   * @var string
   */
  protected $templatePathname;

  /**
   * The name of the component to render.
   *
   * @var string
   */
  protected $name;

  /**
   * The names of the variants to render.
   *
   * @var array
   */
  protected $variants = [];

  /**
   * Constructs component class properties from a given Twig file handle.
   *
   * @param string $handle
   *   A component name handle from which to extract the group, name,
   *   and variant; e.g., '@foo/bar.twig' or '@foo/bar--baz.twig'.
   */
  public function __construct(\Twig_Environment $env, string $handle) {
    $this->env = $env;
    [
      $this->pathname,
      $this->templatePathname,
      $this->name,
      $this->variants,
    ] = $this->extractParts($handle);
  }

  /**
   * Returns the default variables from the component's definition file.
   *
   * @return array
   *   The default variables of the component.
   */
  public function getDefaultVariables(): array {
    $defaults = [];
    if (!$definition_pathname = $this->getDefinitionFilePath($this->getPathname())) {
      return $defaults;
    }
    $component_definition = $this->loadDefinition($definition_pathname);
    if (isset($component_definition['context'])) {
      $defaults = $this->mergeContext($defaults, $component_definition['context']);
    }
    if (!empty($this->getVariants()) && isset($component_definition['variants'])) {
      foreach ($this->getVariants() as $variant_modifier) {
        if ($variant_defaults = $this->getVariantDefaultVariables($variant_modifier, $component_definition['variants'])) {
          $defaults = $this->mergeContext($defaults, $variant_defaults);
        }
      }
    }
    return $defaults;
  }

  /**
   * Merges a context default variables, with support for attributes.
   *
   * Note: The initial set of default variables should not be set directly to
   * $base but should also merged with this helper function.
   *
   * @param array $base
   *   The context default variables (result) array to merge into.
   * @param array $override
   *   The context variables of a certain scope; i.e., the components base
   *   context variables or the context variables of a variant.
   *
   * @return array
   *   The $base array merged with $override variables,
   */
  protected function mergeContext(array $base, array $override): array {
    foreach ($override as $name => $value) {
      // Any context variable containing "attributes" is considered a data array
      // for Drupal Attributes; e.g., title_attributes, author_attributes, etc.
      if (strpos($name, 'attributes') === FALSE) {
        $base[$name] = $value;
      }
      else {
        // Handle the special merging of classes first.
        // Also ensure that the 'class' attribute on $base is always an array.
        if (isset($value['class'])) {
          $base[$name]['class'] = array_merge($base[$name]['class'] ?? [], is_array($value['class']) ? $value['class'] : explode(' ', $value['class']));
          unset($value['class']);
        }
        if (!isset($base[$name])) {
          $base[$name] = [];
        }
        $base[$name] = array_merge($base[$name], $value);
      }
    }
    return $base;
  }

  /**
   * Loads the Fractal YAML component definition file.
   *
   * @param string $definition_file_path
   *   The file path of the component's definition file.
   *
   * @return array
   *   The loaded and parsed component definition.
   */
  protected function loadDefinition(string $definition_file_path) {
    $component_data = [];
    if (file_exists($definition_file_path)) {
      $component_data = Yaml::parse(file_get_contents($definition_file_path));
    }
    return $component_data;
  }

  /**
   * Returns the array of variants from the component configuration file.
   *
   * @return array
   *   The loaded component variants.
   */
  public function getConfigVariants(): array {
    $definition_pathname = $this->getDefinitionFilePath($this->getPathname());
    $component_definition = $this->loadDefinition($definition_pathname);
    return array_column($component_definition['variants'], 'name', 'name');
  }

  /**
   * Returns the relative file path of the Fractal YAML configuration file for
   * a given component name.
   *
   * Like Fractal, this implementation does not support separate configuration
   * files per variant.
   *
   * The file extension must be `.config.yml`.
   *
   * @param string $pathname
   *   The pathname of the component's template file.
   *
   * @return string
   *   The relative path for the component definition file.
   */
  public function getDefinitionFilePath($pathname): ?string {
    $loader = $this->env->getLoader();
    $pathinfo = pathinfo($pathname);
    $config_path = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '.config.yml';
    if ($loader->exists($config_path)) {
      $filepath = $loader->getCacheKey($config_path);
    }
    else {
      $filepath = NULL;
    }

    return $filepath;
  }

  /**
   * Returns the variant default variables from the parsed component definition.
   *
   * @param string $modifier
   *   The variant modifier (the part after the double-hyphen).
   * @param array $defined_variants
   *   A list of variant definitions of the base component.
   *
   * @return array
   *   The variant default variables.
   */
  protected function getVariantDefaultVariables(string $modifier, array $defined_variants): array {
    // Check for a direct match in 'name' or the custom property 'modifier'.
    foreach ($defined_variants as $variant) {
      if ((isset($variant['name']) && $variant['name'] === $modifier) || (isset($variant['modifier']) && $variant['modifier'] === $modifier)) {
        if (isset($variant['context'])) {
          return $variant['context'];
        }
        break;
      }
    }
    // Slugify the variant name to identify it from the component name.
    // E.g. `foo--baz-bar` will look for `Baz Bar` or `baz-bar` in
    // the `variants` key in `foo.config.yml`.
    foreach ($defined_variants as $variant) {
      if (strtolower(Html::cleanCssIdentifier($variant['name'])) === $modifier) {
        if (isset($variant['context'])) {
          return $variant['context'];
        }
        break;
      }
    }
    return [];
  }

  /**
   * Returns the component's pathname, template, name, and a list of variants.
   *
   * @param string $compound_name
   *   A compound name including the component and optionally variants,
   *   delimited by double-hyphens (`--`).
   *
   * @return array
   *   An array with four elements:
   *   1. the component's pathname
   *   2. the component's template pathname
   *   3. the component basename without variants
   *   4. a list of variants, if any.
   */
  protected function extractParts(string $compound_name): array {
    $loader = $this->env->getLoader();
    if (stripos($compound_name, '@') !== FALSE && stripos($compound_name, '.twig') === FALSE) {
      $exploded_paths = explode('/', $compound_name);
      $exploded_paths = array_filter($exploded_paths);
      $last_part = $exploded_paths[count($exploded_paths) - 1];
      $last_part = str_replace('@', '', $last_part);
      $compound_name .= '/' . $last_part . '.twig';
    }

    $pathname = preg_replace('@--[^.]+@', '', $compound_name);
    $template_pathname = $loader->exists($compound_name) ? $compound_name : $pathname;

    $variants = explode('--', basename(basename($compound_name, '.twig'), '.html'));
    $component = array_shift($variants);
    return [$pathname, $template_pathname, $component, $variants];
  }

  /**
   * @return string
   */
  public function getPathname(): string {
    return $this->pathname;
  }

  /**
   * @return string
   */
  public function getTemplatePathname(): string {
    return $this->templatePathname;
  }

  /**
   * @return string
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * @return array
   */
  public function getVariants(): array {
    return $this->variants;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\twig_fractal\Component.
 */

namespace Drupal\twig_fractal;

use Drupal\Component\Utility\Html;
use Symfony\Component\Yaml\Yaml;

/**
 * Prepares context variables for a Twig `render` node.
 *
 * Default component variables are recieved from a Fractal component definition
 * file and merged with the existing `render` node context variables.
 */
class Component {

  /**
   * The pathname of the component to render.
   *
   * @var string
   */
  protected $pathname;

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
   * @param $handle
   *   The component handle to extract the parts for.
   *   Example values:
   *   - @foo/baz.twig
   *   - @foo/baz--bar.twig
   */
  public function __construct($handle) {
    list($this->pathname, $this->name, $this->variants) = $this->extractParts($handle);
  }

  /**
   * Returns the default variables from the component's definition file.
   *
   * @return array
   *   The default variables of the component.
   */
  public function getDefaultVariables() {
    $component_definition = $this->loadDefinition($this->getPathname());

    $defaults = [];
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
  protected function mergeContext(array $base, array $override) {
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
   * @param string $pathname
   *   The pathname of the component's template file.
   *
   * @return array
   *   The parsed component definition.
   */
  protected function loadDefinition($pathname) {
    $definition_pathname = $this->getDefinitionFilePath($pathname);
    return Yaml::parse(file_get_contents($definition_pathname));
  }

  /**
   * Returns the relative file path of the Fractal YAML configuration file for a given component name.
   *
   * The file extension must be `.config.yml`.
   *
   * @param string $pathname
   *   The pathname of the component's template file.
   *
   * @return string
   *   The relative path for the component definition file.
   */
  protected function getDefinitionFilePath($pathname) {
    $library = \Drupal::service('twig.loader.componentlibrary');
    $path = pathinfo($library->getCacheKey($pathname));
    return $path['dirname'] . '/' . $path['filename'] . '.config.yml';
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
  protected function getVariantDefaultVariables($modifier, array $defined_variants) {
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
   * Returns the component's pathname, name, and a list of variants.
   *
   * @param string $compound_name
   *   A compound name including the component and optionally variants, delimited
   *   by double-hyphens (`--`).
   *
   * @return array
   *   An array with three elements:
   *   1. the component's pathname
   *   2. the component basename without variants
   *   3. a list of variants, if any.
   */
  protected function extractParts($compound_name) {
    $pathname = preg_replace('@--[^.]+@', '', $compound_name);
    $variants = explode('--', basename(basename($compound_name, '.twig'), '.html'));
    $component = array_shift($variants);
    return [$pathname, $component, $variants];
  }

  /**
   * @return string
   */
  public function getPathname() {
    return $this->pathname;
  }

  /**
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @return array
   */
  public function getVariants() {
    return $this->variants;
  }

}

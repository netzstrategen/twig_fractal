<?php

/**
 * @file
 * Contains \Drupal\twig_fractal\Node\Render.
 */

namespace Drupal\twig_fractal\Node;

use Drupal\Component\Utility\Html;
use Symfony\Component\Yaml\Yaml;
use Twig_Compiler;
use Twig_Node_Expression;
use Twig_Node_Expression_Constant;
use Twig_Node_Include;

/**
 * Compiles `render` nodes.
 *
 * Unlike `include` nodes, the default values for template variables are
 * automatically retrieved from the corresponding component definition
 * file in the Fractal component library and added to the compiled Twig PHP
 * template string.
 *
 * The resulting template variables consist of:
 *
 * 1. the default `context` properties in the component's definition file,
 *    which may be overridden by
 * 2. the `context` properties of the requested variants (if any) in the
 *    component's definition file, which may be overridden by
 * 3. the variables passed to the `render` tag itself.
 */
class Render extends Twig_Node_Include {

  /**
   * The name of the component to render.
   */
  protected $component;

  /**
   * Constructs a Twig template to render.
   */
  public function __construct(Twig_Node_Expression $expr, Twig_Node_Expression $variables = NULL, $only = FALSE, $ignoreMissing = FALSE, $lineno, $tag = NULL) {
    $this->component = $expr->getAttribute('value');

    // Remove any variant suffixes from the template name as there are no
    // template files for variants, only for components.
    $expr->setAttribute('value', $this->extractComponentName($this->component));

    parent::__construct($expr, $variables, $only, $ignoreMissing, $lineno);
  }

  /**
   * Adds the template variables to the compiled Twig PHP template string.
   *
   * Variables consist of
   *
   * 1. the default `context` properties in the component's definition file,
   *    which may be overridden by
   * 2. the `context` properties of the requested variants (if any) in the
   *    component's definition file, which may be overridden by
   * 3. the variables passed to the `render` tag itself.
   *
   * @param \Twig_Compiler $compiler
   */
  protected function addTemplateArguments(Twig_Compiler $compiler) {
    $defaults = $this->getComponentDefaults($this->component);

    $compiler->raw('array_merge($context,')->repr($defaults);
    if ($this->hasNode('variables')) {
      $compiler->raw(',')->subcompile($this->getNode('variables'));
    }
    $compiler->raw(')');
  }

  /**
   * Returns the default variables from a component's definition file.
   *
   * @param string $component
   *   The component name.
   *
   * @return array
   *   The default variables.
   */
  public function getComponentDefaults($component) {
    $component_name = $this->extractComponentName($component);
    $component_definition = Yaml::parse(file_get_contents($this->getComponentDefinitionFile($component_name)));
    $component = $this->getComponentParts($component);

    $defaults = [];
    if ($component['variant'] && isset($component_definition['variants'])) {
      $defaults += $this->getVariantDefaults($component['variant'], $component_definition['variants']);
    }
    else {
      $defaults += (array) $component_definition['context'];
    }
    return $defaults;
  }

  /**
   * Returns the relative file path of the Fractal YAML configuration file for a given component name.
   *
   * The file extension must be `.config.yml`.
   *
   * @param string $component
   *   The component name.
   *
   * @return string
   *   The relative path for the component definition file.
   */
  protected function getComponentDefinitionFile($component) {
    $components = \Drupal::service('twig.loader.componentlibrary');
    $path = pathinfo($components->getCacheKey($component));
    return $path['dirname'] . '/' . $path['filename'] . '.config.yml';
  }

  /**
   * Splits the component and returns the parts.
   *
   * @param string $component
   *   The component name.
   *
   * @return array
   *   The base and variant parts of the component.
   */
  public function getComponentParts($component) {
    $parts = explode('--', basename($component, '.twig'));
    return [
      'base' => $parts[0] ?? NULL,
      'variant' => $parts[1] ?? NULL,
    ];
  }

  /**
   * Returns the variant default variables from the parsed component definition.
   *
   * @param string $variantName
   *   The variant name.
   * @param array $variants
   *   The parsed variants of the base component.
   *
   * @return array
   *   The variant default variables.
   */
  public function getVariantDefaults($variantName, $variants) {
    $defaults = [];
    foreach ($variants as $index => $variant) {
      // Slugify the variant name to identify it from the component name.
      // E.g. `foo--baz-bar` will look for `Baz Bar` or `baz-bar` in
      // the `variants` key in `foo.config.yml`.
      if (strtolower(Html::cleanCssIdentifier($variant['name'])) === $variantName) {
        $defaults += (array) $variants[$index]['context'];
        break;
      }
    }
    return $defaults;
  }

  /**
   * Returns the extracted base component name without its variant.
   *
   * @param string $component
   *   The component name.
   *
   * @return string
   *   The base component name without its variant.
   */
  public function extractComponentName($component) {
    return preg_replace('@--[a-z0-9-]+@i', '', $component);
  }

}

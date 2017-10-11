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
  protected $component;

  /**
   * The names of the variants to render.
   *
   * @var array
   */
  protected $variants = [];

  /**
   * Constructs a Twig template to render.
   */
  public function __construct(Twig_Node_Expression $expr, Twig_Node_Expression $variables = NULL, $only = FALSE, $ignoreMissing = FALSE, $lineno, $tag = NULL) {
    list($this->pathname, $this->component, $this->variants) = $this->extractComponentParts($expr->getAttribute('value'));
    // Remove any variant suffixes from the template name as there are no
    // template files for variants, only for components.
    $expr->setAttribute('value', $this->pathname);

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
  protected function addGetTemplate(Twig_Compiler $compiler) {
    $defaults = $this->getComponentDefaultVariables();
    $compiler->raw('$defaults = ')->repr($defaults)->raw(";\n");

    if (!$this->hasNode('variables')) {
      $compiler->raw('$variables = $defaults')->raw(";\n");
    }
    else {
      $compiler->raw('$passed_variables = ')->subcompile($this->getNode('variables'))->raw(";\n");
      $compiler->raw('$variables = array_merge($defaults, $passed_variables)')->raw(";\n");
      $compiler->raw(<<<'EOD'

        foreach ($variables as $name => $value) {
          if (FALSE === strpos($name, 'attributes')) {
            continue;
          }
          unset($variables[$name]);
          if (!isset($defaults[$name])) {
            continue;
          }
          if (!isset($passed_variables[$name])) {
            $variables[$name] = new \Drupal\Core\Template\Attribute($defaults[$name]);
          }
          else {
            $variables[$name] = $passed_variables[$name];
            if (!$variables[$name] instanceof \Drupal\Core\Template\Attribute) {
              $variables[$name] = new \Drupal\Core\Template\Attribute($variables[$name]);
            }
            foreach ($defaults[$name] as $default_key => $default_value) {
              if (!isset($variables[$name][$default_key])) {
                $variables[$name]->setAttribute($default_key, $default_value);
              }
            }
          }
          if ($name === 'attributes' && isset($defaults[$name]['class'])) {
            $variables[$name]->addClass($defaults[$name]['class']);
          }
        }
EOD
      );
    }
    parent::addGetTemplate($compiler);
  }

  /**
   * Passes the precompiled template variables to the Twig PHP template display method.
   *
   * @param \Twig_Compiler $compiler
   */
  protected function addTemplateArguments(Twig_Compiler $compiler) {
    $compiler->raw('$variables');
  }

  /**
   * Returns the default variables from the component's definition file.
   *
   * @return array
   *   The default variables of the component.
   */
  protected function getComponentDefaultVariables() {
    $component_definition = $this->loadComponentDefinition($this->pathname);

    $defaults = [];
    if (isset($component_definition['context'])) {
      $defaults = $this->mergeContext($defaults, $component_definition['context']);
    }
    if (!empty($this->variants) && isset($component_definition['variants'])) {
      foreach ($this->variants as $variant_modifier) {
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
  protected function loadComponentDefinition($pathname) {
    $definition_pathname = $this->getComponentDefinitionFilePath($pathname);
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
  protected function getComponentDefinitionFilePath($pathname) {
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
  protected function extractComponentParts($compound_name) {
    $pathname = preg_replace('@--[^.]+@', '', $compound_name);
    $variants = explode('--', basename(basename($compound_name, '.twig'), '.html'));
    $component = array_shift($variants);
    return [$pathname, $component, $variants];
  }

}

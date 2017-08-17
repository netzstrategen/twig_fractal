<?php

namespace Drupal\twig_fractal\Node;

use Drupal\Component\Utility\Html;
use Symfony\Component\Yaml\Yaml;
use Twig_Compiler;
use Twig_Node_Expression;
use Twig_Node_Expression_Constant;
use Twig_Node_Include;

class Render extends Twig_Node_Include {

  /**
   * Holds the given component name from the render tag.
   */
  protected $component;

  public function __construct(Twig_Node_Expression $expr, Twig_Node_Expression $variables = NULL, $only = FALSE, $ignoreMissing = FALSE, $lineno, $tag = NULL) {
    $this->component = $expr->getAttribute('value');
    // Remove variant appendix from component name as we do not have variant files.
    // @todo check if variant files are available before sanitize the name
    $expr->setAttribute('value', $this->sanitizeComponentName($this->component));
    // Add an 'not_used' node if we don't have any variables to render correctly.
    if (NULL === $variables) {
      $variables = new Twig_Node_Expression_Constant(['not_used'], $lineno);
    }
    parent::__construct($expr, $variables, $only, $ignoreMissing, $lineno);
  }

  /**
   * Adds the template arguments including the component (variant) context.
   */
  protected function addTemplateArguments(Twig_Compiler $compiler) {
    $context = $this->getComponentContext($this->component);
    $compiler
      ->raw('array_merge($context,')
      ->repr($context)
      ->raw(',')
      ->subcompile($this->getNode('variables'))
      ->raw(')')
    ;
    return $compiler;
  }

  /**
   * Returns the component context from the component config file.
   *
   * @todo Merge specific keys to prevent config duplication.
   */
  public function getComponentContext($component) {
    $context = [];
    $sanitizedComponent = $this->sanitizeComponentName($component);
    $config = Yaml::parse(file_get_contents($this->getComponentConfig($sanitizedComponent)));
    $component = $this->getComponentParts($component);
    if ($component['variant'] && isset($config['variants'])) {
      $context = $context + $this->getVariantContext($component['variant'], $config);
    }
    else {
      $context = $context + (array) $config['context'];
    }
    return $context;
  }

  /**
   * Returns the component config path.
   */
  protected function getComponentConfig($component) {
    $components = \Drupal::service('twig.loader.componentlibrary');
    $path = pathinfo($components->getCacheKey($component));
    return $path['dirname'] . '/' . $path['filename'] . '.config.yml';
  }

  /**
   * Splits the component and return its parts.
   */
  public function getComponentParts($component) {
    list($component, $variant) = explode('--', basename($component, '.twig'));
    $parts = [
      'base' => $component,
      'variant' => $variant,
    ];
    return $parts;
  }

  /**
   * Returns the variant context from the config file.
   */
  public function getVariantContext($variant, $config) {
    $context = [];
    foreach ($config['variants'] as $index => $variantInfo) {
      // Slugify the variant name to identify it from the component name.
      // E.g. 'foo--baz-bar' will look for a the variant 'Baz Bar' in foo.config.yml
      if (strtolower(Html::cleanCssIdentifier($variantInfo['name'])) === $variant) {
        $context = $context + (array) $config['variants'][$index]['context'];
        break;
      }
    }
    return $context;
  }

  /**
   * Returns the sanitized component name
   */
  public function sanitizeComponentName($component) {
    return preg_replace('@--[a-z0-9-]+@', '', $component);
  }

}

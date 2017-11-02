<?php

/**
 * @file
 * Contains \Drupal\twig_fractal\Node\Render.
 */

namespace Drupal\twig_fractal\Node;

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
   * Constructs a Twig template to render.
   */
  public function __construct(Twig_Node_Expression $expr, Twig_Node_Expression $variables = NULL, $only = FALSE, $ignoreMissing = FALSE, $lineno, $tag = NULL) {

    // Remove any variant suffixes from the template name as there are no
    // template files for variants, only for components.
//    $expr->setAttribute('value', $this->pathname);

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
    $compiler->raw('$component = new \Drupal\twig_fractal\Component(');
    $compiler->subcompile($this->getNode('expr'));
    $compiler->raw(')')->raw(";\n");
    $compiler->raw('$defaults = $component->getDefaultVariables();');
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
    $compiler
      ->write('$this->loadTemplate(')
      ->raw('$component->pathname')
      ->raw(', ')
      ->repr($this->getTemplateName())
      ->raw(', ')
      ->repr($this->getTemplateLine())
      ->raw(')')
    ;
  }

  /**
   * Passes the precompiled template variables to the Twig PHP template display method.
   *
   * @param \Twig_Compiler $compiler
   */
  protected function addTemplateArguments(Twig_Compiler $compiler) {
    $compiler->raw('$variables');
  }

}

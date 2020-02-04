<?php

/**
 * @file
 * Contains \Drupal\twig_fractal\Node\Render.
 */

namespace Drupal\twig_fractal\Node;

use Drupal\Core\Template\Attribute;
use Twig_Compiler;
use Twig_Node_Expression;
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

  protected static $env;

  /**
   * Constructs a Twig template to render.
   */
  public function __construct(Twig_Node_Expression $expr, Twig_Node_Expression $variables = NULL, $only = FALSE, $ignoreMissing = FALSE, $lineno, $tag = NULL) {
    parent::__construct($expr, $variables, $only, $ignoreMissing, $lineno);
  }

  protected function setEnvironment(\Twig_Environment $env) {
    static::$env = $env;
  }

  public static function getEnvironment(): \Twig_Environment {
    return static::$env;
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
    $this->setEnvironment($compiler->getEnvironment());
    $compiler->raw('$handles = (array) ')->subcompile($this->getNode('expr'))->raw(';');
    $compiler->raw('$templates = [];');
    $compiler->raw('foreach($handles as $handle):');
      $compiler->raw('$passed_variables = $defaults = [];');
      $compiler->raw('$component = new \Drupal\twig_fractal\Component(');
        $compiler->raw('\Drupal\twig_fractal\Node\Render::getEnvironment(),');
        $compiler->raw('$handle');
      $compiler->raw(')')->raw(";\n");
      $compiler->raw('$templates[] = $component->getTemplatePathname();');
      // Exit loop when component is found to not look further.
      $compiler->raw('if ($component->getDefinitionFilePath($component->getPathname())):');
        $compiler->raw('$defaults = $component->getDefaultVariables();');
        $compiler->raw('break;');
      $compiler->raw('endif;');
    $compiler->raw('endforeach;');
    if (!$this->hasNode('variables')) {
      $compiler->raw('$variables = $defaults')->raw(";\n");
    }
    else {
      $compiler->raw('$passed_variables = ')->subcompile($this->getNode('variables'))->raw(";\n");
      $compiler->raw('$variables = array_merge($defaults, $passed_variables)')->raw(";\n");
    }
    $compiler->raw('$variables = \Drupal\twig_fractal\Node\Render::convertAttributes($variables, $defaults, $passed_variables)')->raw(";\n");
    $compiler
      ->write('$this->loadTemplate(')
        ->raw('$templates')
        ->raw(', ')
        ->repr($this->getTemplateName())
        ->raw(', ')
        ->repr($this->getTemplateLine())
      ->raw(')')
    ;
  }

  /**
   * Recursively converts attributes variables into Attribute objects in context variables.
   *
   * @param array $variables
   *   The pre-merged component variables.
   * @param array $defaults
   *   The default variables defined by the component.
   * @param array $passed_variables
   *   The custom variables passed to the component as render tag arguments.
   *
   * @return array
   *   The $variables with recursively converted attributes variables.
   */
  public static function convertAttributes(array $variables, array $defaults, array $passed_variables) {
    foreach ($variables as $name => $value) {
      if (FALSE === strpos($name, 'attributes')) {
        // Non-attributes variables do not need further processing as they have
        // been merged recursively already, but they can contain attributes
        // variables in nested keys.
        if (is_array($value)) {
          // The component may have defined an empty string as a placeholder for
          // more complex dynamic content.
          if (!isset($defaults[$name]) || !is_array($defaults[$name])) {
            $defaults[$name] = [];
          }
          $variables[$name] = static::convertAttributes($value, $defaults[$name], $passed_variables[$name] ?? []);
        }
        continue;
      }
      // Remove variables that are not defined by the component.
      // @todo This accidentally removes nested keys (including attributes).
      //unset($variables[$name]);
      if (!isset($defaults[$name])) {
        continue;
      }
      if (!isset($passed_variables[$name])) {
        $variables[$name] = new Attribute($defaults[$name]);
      }
      else {
        $variables[$name] = $passed_variables[$name];
        if (!$variables[$name] instanceof Attribute) {
          $variables[$name] = new Attribute($variables[$name]);
        }
        foreach ($defaults[$name] as $default_key => $default_value) {
          if (!isset($variables[$name][$default_key])) {
            $variables[$name]->setAttribute($default_key, $default_value);
          }
        }
      }
      if (FALSE !== strpos($name, 'attributes') && isset($defaults[$name]['class'])) {
        $variables[$name]->addClass($defaults[$name]['class']);
      }
    }
    return $variables;
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

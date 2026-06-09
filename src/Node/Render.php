<?php

/**
 * @file
 * Contains \Drupal\twig_fractal\Node\Render.
 */

namespace Drupal\twig_fractal\Node;

use Drupal\Core\Template\Attribute;
use Twig\Compiler;
use Twig\Error\LoaderError;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\IncludeNode;

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
class Render extends IncludeNode {

  /**
   * Pre-render callback.
   *
   * @var Callable
   */
  private static $preRenderCallback;

  /**
   * Constructs a Twig template to render.
   */
  public function __construct(AbstractExpression $expr, ?AbstractExpression $variables = null, bool $only = false, bool $ignoreMissing = false, int $lineno = 0) {
    parent::__construct($expr, $variables, $only, $ignoreMissing, $lineno);
  }

  /**
   * Compiles the render node.
   *
   * Overrides IncludeNode::compile() entirely to inject component-lookup and
   * variable-merging statements before the yield-based template dispatch
   * introduced in Twig 3.9.
   */
  public function compile(Compiler $compiler): void {
    $compiler->addDebugInfo($this);

    // Resolve handle(s), collect candidate template paths, and load defaults
    // from the first component definition file found.
    $compiler
      ->write('$handles = (array) ')->subcompile($this->getNode('expr'))->raw(";\n")
      ->write('$templates = [];')->raw("\n")
      ->write('$defaults = [];')->raw("\n")
      ->write('$passed_variables = [];')->raw("\n")
      ->write('foreach ($handles as $handle) {')->raw("\n")
      ->indent()
        ->write('$component = new \Drupal\twig_fractal\Component($this->env, $handle);')->raw("\n")
        ->write('$templates[] = $component->getTemplatePathname();')->raw("\n")
        ->write('if ($component->getDefinitionFilePath($component->getPathname())) {')->raw("\n")
        ->indent()
          ->write('$defaults = $component->getDefaultVariables();')->raw("\n")
          ->write('break;')->raw("\n")
        ->outdent()
        ->write('}')->raw("\n")
      ->outdent()
      ->write('}')->raw("\n");

    if ($this->hasNode('variables')) {
      $compiler
        ->write('$passed_variables = ')->subcompile($this->getNode('variables'))->raw(";\n")
        ->write('$variables = array_merge($defaults, $passed_variables);')->raw("\n");
    }
    else {
      $compiler->write('$variables = $defaults;')->raw("\n");
    }

    $compiler
      ->write('$variables = \Drupal\twig_fractal\Node\Render::convertAttributes($variables, $defaults, $passed_variables);')->raw("\n")
      ->write('\Drupal\twig_fractal\Node\Render::doPreRenderCallback($component->getPathname(), $component->getName());')->raw("\n");

    if ($this->getAttribute('ignore_missing')) {
      $t = $compiler->getVarName();
      $compiler
        ->write("try {\n")
        ->indent()
          ->write("\$$t = \$this->load(\$templates, ")->repr($this->getTemplateLine())->raw(");\n")
        ->outdent()
        ->write('} catch (\Twig\Error\LoaderError $e) {')->raw("\n")
        ->indent()
          ->write("\$$t = null;\n")
        ->outdent()
        ->write("}\n")
        ->write("if (\$$t !== null) {\n")
        ->indent()
          ->write("yield from \$$t->unwrap()->yield(\$variables);\n")
        ->outdent()
        ->write("}\n");
    }
    else {
      $compiler
        ->write('yield from $this->load($templates, ')->repr($this->getTemplateLine())->raw(')->unwrap()->yield($variables);')->raw("\n");
    }
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
   * Sets the pre-render callback function.
   *
   * @param callable $callback
   *   The callable must accept 2 arguments.
   */
  public static function setPreRenderCallback(callable $callback): void {
    static::$preRenderCallback = $callback;
  }

  /**
   * Execute the pre-render callback function if present.
   *
   * @param string $path
   *   Component path name, including alias.
   * @param string $name
   *   Component name.
   */
  public static function doPreRenderCallback(string $path, string $name): void {
    $callback = static::$preRenderCallback ?? NULL;
    if (!$callback) {
      return;
    }
    $callback($path, $name);
  }

}

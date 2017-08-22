<?php
/**
 * @file
 * Contains Drupal\twig_fractal\TokenParser\Render.
 */

namespace Drupal\twig_fractal\TokenParser;

use Drupal\twig_fractal\Node;
use Twig_Token;
use Twig_TokenParser;

/**
 * Class to register and parse a new `render` tag for Twig templates.
 *
 * This tag is a derivation of the `include` tag but scopes the included file
 * by default. There is no need to use the `only` attribute and will be ignored if so.
 * In addition the render tag passes default template variables to the included
 * file which are set from a Fractal component definition file if not
 * explicitly given via the `with` attribute. Component variants must be referenced with
 * a double hyphen and definied in the `variants` key in the component definition file.
 *
 * Example usage:
 * @code
 * {% render '@foo/baz.twig' %}
 * {% render '@foo/baz.twig' with { qux: 'foobar' } %}
 * {% render '@foo/baz--bar.twig' with { qux: 'foobar' } %}
 * @endcode
 *
 * @see Twig_TokenParser_Include
 * @see http://fractal.build/guide/components/variants
 */
class Render extends Twig_TokenParser {

  /**
   * Parses a Twig token and returns a new `Render` node.
   *
   * @param \Twig_Token $token
   *   The Twig_Token to parse
   *
   * @see Twig_TokenParser_Include::parse()
   *
   * @return \Drupal\twig_fractal\Node\Render
   *   The `Render` node.
   */
  public function parse(Twig_Token $token) {
    $expr = $this->parser->getExpressionParser()->parseExpression();
    list($variables, $only, $ignoreMissing) = $this->parseArguments();
    return new Node\Render($expr, $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
  }

  /**
   * Returns the parsed token arguments.
   *
   * @see Twig_TokenParser_Include::parseArguments()
   *
   * @return array
   *   The extracted arguments.
   */
  protected function parseArguments() {
    $stream = $this->parser->getStream();
    $ignoreMissing = FALSE;
    if ($stream->nextIf(Twig_Token::NAME_TYPE, 'ignore')) {
      $stream->expect(Twig_Token::NAME_TYPE, 'missing');
      $ignoreMissing = TRUE;
    }
    $variables = NULL;
    if ($stream->nextIf(Twig_Token::NAME_TYPE, 'with')) {
      $variables = $this->parser->getExpressionParser()->parseExpression();
    }
    // Scope included file context.
    $only = TRUE;

    $stream->expect(Twig_Token::BLOCK_END_TYPE);
    return [$variables, $only, $ignoreMissing];
  }

  /**
   * {@inheritdoc}
   */
  public function getTag() {
    return 'render';
  }

}

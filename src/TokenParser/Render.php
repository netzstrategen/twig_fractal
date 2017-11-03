<?php

/**
 * @file
 * Contains \Drupal\twig_fractal\TokenParser\Render.
 */

namespace Drupal\twig_fractal\TokenParser;

use Drupal\twig_fractal\Node;
use Twig_Token;
use Twig_TokenParser;

/**
 * Registers and parses a new `render` tag for Twig templates.
 *
 * This tag is a derivation of the `include` tag but scopes the included file
 * by default. There is no need to use the `only` attribute, it is always enabled.
 *
 * In addition the render tag passes default template variables to the included
 * file which are set from a Fractal component definition file unless explicitly
 * specified in the `with` attribute. Component variants can be requested by
 * specifying them with a double hyphen in the template name. A variant needs to
 * be defined in the `variants` key of the component's definition file.
 *
 * Usage:
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
   * Parses a Twig token and returns a new Render node.
   *
   * @param \Twig_Token $token
   *   The Twig_Token to parse.
   *
   * @return \Drupal\twig_fractal\Node\Render
   *   The Render node.
   *
   * @see Twig_TokenParser_Include::parse()
   */
  public function parse(Twig_Token $token): Node\Render {
    $expr = $this->parser->getExpressionParser()->parseExpression();
    list($variables, $only, $ignoreMissing) = $this->parseArguments();
    return new Node\Render($expr, $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
  }

  /**
   * Returns the parsed token arguments.
   *
   * @return array
   *   The extracted arguments.
   *
   * @see Twig_TokenParser_Include::parseArguments()
   */
  protected function parseArguments(): array {
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

    // Do not inherit any variables from parent template or execution context,
    // as there might be variables with the same names in the rendered component
    // template.
    $only = TRUE;

    $stream->expect(Twig_Token::BLOCK_END_TYPE);

    return [$variables, $only, $ignoreMissing];
  }

  /**
   * {@inheritdoc}
   */
  public function getTag(): string {
    return 'render';
  }

}

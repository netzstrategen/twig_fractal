<?php

namespace Drupal\twig_fractal\TokenParser;

use Twig_Token;
use Twig_TokenParser;

class Render extends Twig_TokenParser {

  public function parse(Twig_Token $token) {
    $expr = $this->parser->getExpressionParser()->parseExpression();
    list($variables, $only, $ignoreMissing) = $this->parseArguments();
    return new Twig_Node_Include($expr, $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
  }

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
    $only = TRUE;
    $stream->expect(Twig_Token::BLOCK_END_TYPE);
    return [$variables, $only, $ignoreMissing];
  }

  public function getTag() {
    return 'render';
  }

}

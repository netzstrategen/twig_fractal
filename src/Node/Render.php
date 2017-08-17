<?php

namespace Drupal\twig_fractal\Node;

use Twig_Node_Include;
use Twig_Node_Expression;
use Twig_Compiler;

class Render extends Twig_Node_Include {

  public function __construct(Twig_Node_Expression $expr, Twig_Node_Expression $variables = NULL, $only = FALSE, $ignoreMissing = FALSE, $lineno, $tag = NULL) {
    $nodes = ['expr' => $expr];
    if (NULL !== $variables) {
      $nodes['variables'] = $variables;
    }
    parent::__construct($nodes, ['only' => (bool) $only, 'ignore_missing' => (bool) $ignoreMissing], $lineno, $tag);
  }

  protected function addTemplateArguments(Twig_Compiler $compiler) {
    if (!$this->hasNode('variables')) {
      $compiler->raw(FALSE === $this->getAttribute('only') ? '$context' : '[]');
    }
    elseif (FALSE === $this->getAttribute('only')) {
      $compiler
        ->raw('array_merge($context, ')
        ->subcompile($this->getNode('variables'))
        ->raw(')')
      ;
    }
    else {
      $compiler->subcompile($this->getNode('variables'));
    }
  }

}

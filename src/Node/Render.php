<?php

namespace Drupal\twig_fractal\Node;

use Twig_Node_Include;
use Twig_Node_Expression;
use Twig_Compiler;

class Render extends Twig_Node_Include {

  public function __construct(Twig_Node_Expression $expr, Twig_Node_Expression $variables = null, $only = false, $ignoreMissing = false, $lineno, $tag = null)
  {
    $nodes = array('expr' => $expr);
    if (null !== $variables) {
      $nodes['variables'] = $variables;
    }

    parent::__construct($nodes, array('only' => (bool) $only, 'ignore_missing' => (bool) $ignoreMissing), $lineno, $tag);
  }

  protected function addTemplateArguments(Twig_Compiler $compiler)
  {
    if (!$this->hasNode('variables')) {
      $compiler->raw(false === $this->getAttribute('only') ? '$context' : 'array()');
    } elseif (false === $this->getAttribute('only')) {
      $compiler
        ->raw('array_merge($context, ')
        ->subcompile($this->getNode('variables'))
        ->raw(')')
      ;
    } else {
      $compiler->subcompile($this->getNode('variables'));
    }
  }

}

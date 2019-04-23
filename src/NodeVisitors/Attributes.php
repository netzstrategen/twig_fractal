<?php

/**
 * @file
 * Contains \Drupal\twig_fractal\TokenParser\Render.
 */

namespace Drupal\twig_fractal\NodeVisitors;

use Twig_BaseNodeVisitor;
use Twig_Environment;
use Twig_Node;
use Twig_Node_Expression_Filter;

class Attributes extends Twig_BaseNodeVisitor {

  protected $isAttribute = TRUE;

  /**
   * Checks if a node contains an attributes name expression.
   *
   * @return Twig_Node The modified node
   */
  public function doEnterNode(Twig_Node $node, Twig_Environment $env): object {
    if ($node->hasAttribute('name') && $node->getAttribute('name') === 'attributes') {
      $this->isAttribute = TRUE;
    }
    return $node;
  }

  /**
   * Removes the escape filter for attribute nodes.
   *
   * @return Twig_Node The modified node
   */
  public function doLeaveNode(Twig_Node $node, Twig_Environment $env): object {
    if ($this->isAttribute && $node instanceof Twig_Node_Expression_Filter && $node->hasNode('filter')) {
      $filter = $node->getNode('filter');
      if ($filter->hasAttribute('value') && $filter->getAttribute('value') === 'escape') {
        $filter->setAttribute('value', 'default');
      }
      $this->isAttribute = FALSE;
    }
    return $node;
  }

  /**
   * Returns the priority for this visitor.
   */
  public function getPriority(): int {
    return 0;
  }

}

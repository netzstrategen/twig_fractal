<?php

/**
 * @file
 * Contains \Drupal\twig_fractal\TokenParser\Render.
 */

namespace Drupal\twig_fractal\NodeVisitors;

use Twig_BaseNodeVisitor;
use Twig_Environment;
use Twig_Node;

class Attributes extends Twig_BaseNodeVisitor {

  /**
   * {@inheritdoc}
   */
  public function doEnterNode(Twig_Node $node, Twig_Environment $env): Twig_Node {
    return $node;
  }

  /**
   * Changes the filter for attributes nodes to avoid quote escaping.
   *
   * @return Twig_Node The modified node
   */
  public function doLeaveNode(Twig_Node $node, Twig_Environment $env): Twig_Node {
    if (!$node->hasNode('expr')) {
      return $node;
    }
    $expr = $node->getNode('expr');
    if (!$this->isAttributes($expr)) {
      return $node;
    }
    if ($expr->hasNode('filter')) {
      $filter = $expr->getNode('filter');
      $filter->setAttribute('value', 'raw');
      $expr->setNode('filter', $filter);
    }
    $node->setNode('expr', $expr);
    return $node;
  }

  /**
   * Checks if the passed node is an attributes node.
   *
   * @return \Twig_Node|null
   */
  protected function isAttributes($node): ?Twig_Node {
    if ($node->hasAttribute('name') && strpos($node->getAttribute('name'), 'attributes') !== FALSE) {
      return $node;
    }
    elseif ($node->hasNode('node')) {
      return $this->isAttributes($node->getNode('node'));
    }
    return NULL;
  }

  /**
   * Returns the priority for this visitor.
   */
  public function getPriority(): int {
    return 0;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\twig_fractal\TokenParser\Render.
 */

namespace Drupal\twig_fractal\NodeVisitors;

use Twig\NodeVisitor\AbstractNodeVisitor;
use Twig\Environment;
use Twig\Node\Node;

class Attributes extends AbstractNodeVisitor {

  /**
   * {@inheritdoc}
   */
  public function doEnterNode(Node $node, Environment $env): Node {
    return $node;
  }

  /**
   * Changes the filter for attributes nodes to avoid quote escaping.
   *
   * @return Node The modified node
   */
  public function doLeaveNode(Node $node, Environment $env): Node {
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
   * @return \Node|null
   */
  protected function isAttributes($node): ?Node {
    if ($node->hasAttribute('name')) {
      $name = (string) $node->getAttribute('name'); // Ensure it's a string
      if (strpos($name, 'attributes') !== false) {
        return $node;
      }
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

<?php

namespace Drupal\product_interactions\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class InteractionsTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('product_interactions_get_counts', [
        $this,
        'getCounts',
      ], ['is_safe' => ['html']]),
    ];
  }

  /**
   * Get interaction counts for a node.
   */
  public function getCounts($nid): array {
    $database = \Drupal::database();
    $counts = [];

    foreach (['like', 'share'] as $type) {
      $counts[$type] = (int) $database->select('product_interactions', 'pi')
        ->condition('nid', $nid)
        ->condition('type', $type)
        ->countQuery()
        ->execute()
        ->fetchField();
    }

    // Comment count
    $counts['comment'] = (int) \Drupal::entityQuery('comment')
      ->condition('entity_id', $nid)
      ->condition('entity_type', 'node')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    return $counts;
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'product_interactions';
  }

}
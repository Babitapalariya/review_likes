<?php

namespace Drupal\merchant_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DebateController extends ControllerBase {

  public function getComments(int $rebuttal_id, Request $request): JsonResponse {
    $offset = (int) $request->query->get('offset', 0);
    $limit  = 10;

    \Drupal::logger('debate')->debug('getComments — rebuttal_id:@r  offset:@o', [
      '@r' => $rebuttal_id,
      '@o' => $offset,
    ]);

    // Total top-level count.
    $total_top_level = (int) \Drupal::entityQuery('node')
      ->condition('type', 'rebuttal_debate')
      ->condition('status', 1)
      ->condition('field_debate_rebuttal', $rebuttal_id)
      ->notExists('field_debate_parent')
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    \Drupal::logger('debate')->debug('getComments — total_top_level:@t', ['@t' => $total_top_level]);

    // Fetch paginated top-level comments.
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'rebuttal_debate')
      ->condition('status', 1)
      ->condition('field_debate_rebuttal', $rebuttal_id)
      ->notExists('field_debate_parent')
      ->sort('created', 'DESC')
      ->range($offset, $limit)
      ->accessCheck(FALSE)
      ->execute();

    \Drupal::logger('debate')->debug('getComments — nids: @n', [
      '@n' => implode(', ', $nids ?: ['NONE']),
    ]);

    $form_class = new \Drupal\merchant_dashboard\Form\DebateCommentForm();
    $comments   = [];

    foreach (Node::loadMultiple($nids) as $node) {
      $comments[] = $form_class->renderCommentCard($node, 0);
    }

    $has_more = ($offset + $limit) < $total_top_level;

    \Drupal::logger('debate')->debug('getComments — returning count:@c  has_more:@h', [
      '@c' => count($comments),
      '@h' => $has_more ? 'YES' : 'NO',
    ]);

    return new JsonResponse([
      'comments' => $comments,
      'total'    => $total_top_level,
      'offset'   => $offset + $limit,
      'has_more' => $has_more,
    ]);
  }
}
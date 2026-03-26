<?php

namespace Drupal\interactions\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;

/**
 * Controller for interaction actions.
 */
class InteractionController extends ControllerBase {

  public function ajaxLike($nid) {
    $current_user = $this->currentUser();
    $uid = $current_user->id();

    if (!$current_user->isAuthenticated()) {
      return new JsonResponse(['error' => 'You must be logged in to like.'], 403);
    }

    $node = Node::load($nid);
    if (!$node) {
      return new JsonResponse(['error' => 'Node not found.'], 404);
    }

    // Check if user already liked.
    $query = \Drupal::entityTypeManager()
      ->getStorage('interaction')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('user_id', $uid)
      ->condition('node_id', $nid)
      ->condition('action', 'like')
      ->execute();

    if (empty($query)) {
      // Create like.
      $interaction = \Drupal::entityTypeManager()
        ->getStorage('interaction')
        ->create([
          'user_id' => $uid,
          'node_id' => $nid,
          'action' => 'like',
        ]);
      $interaction->save();
    }

    // Count updated likes.
    $like_count = \Drupal::entityTypeManager()
      ->getStorage('interaction')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('node_id', $nid)
      ->condition('action', 'like')
      ->count()
      ->execute();

    return new JsonResponse(['likes' => $like_count]);
  }

}

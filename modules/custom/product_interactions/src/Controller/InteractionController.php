<?php

namespace Drupal\product_interactions\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class InteractionController extends ControllerBase {

  public function like(Request $request) {
    // Validate CSRF token
    $token = $request->request->get('token');
    if (!\Drupal::csrfToken()->validate($token, 'product-interactions')) {
      return new JsonResponse(['error' => 'Invalid token'], 403);
    }

    $nid = (int) $request->request->get('nid');
    $uid = \Drupal::currentUser()->id();

    if (!$uid) {
      return new JsonResponse(['error' => 'Login required to like'], 403);
    }

    $database = \Drupal::database();

    // Toggle like
    $existing = $database->select('product_interactions', 'pi')
      ->fields('pi', ['id'])
      ->condition('nid', $nid)
      ->condition('uid', $uid)
      ->condition('type', 'like')
      ->execute()
      ->fetchField();

    if ($existing) {
      // Unlike
      $database->delete('product_interactions')
        ->condition('id', $existing)
        ->execute();
      $liked = FALSE;
    } else {
      // Like
      $database->insert('product_interactions')
        ->fields([
          'nid'     => $nid,
          'uid'     => $uid,
          'type'    => 'like',
          'created' => time(),
        ])
        ->execute();
      $liked = TRUE;
    }

    $count = (int) $database->select('product_interactions', 'pi')
      ->condition('nid', $nid)
      ->condition('type', 'like')
      ->countQuery()
      ->execute()
      ->fetchField();

    return new JsonResponse([
      'likes' => $count,
      'liked' => $liked,
    ]);
  }

  public function share(Request $request) {
    $nid = (int) $request->request->get('nid');

    \Drupal::database()->insert('product_interactions')
      ->fields([
        'nid'     => $nid,
        'uid'     => \Drupal::currentUser()->id(),
        'type'    => 'share',
        'created' => time(),
      ])
      ->execute();

    $count = (int) \Drupal::database()->select('product_interactions', 'pi')
      ->condition('nid', $nid)
      ->condition('type', 'share')
      ->countQuery()
      ->execute()
      ->fetchField();

    return new JsonResponse(['shares' => $count]);
  }
}
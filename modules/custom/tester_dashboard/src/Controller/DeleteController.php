<?php


namespace Drupal\tester_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;
// use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\NodeInterface;

class DeleteController extends ControllerBase {

    public function unpublishNode(NodeInterface $node) {
  $nid = $node->id();

  // Check if the current user has update access (not delete).
  if ($node->access('update')) {
    $node->setUnpublished(); // Equivalent to $node->set('status', 0);
    $node->save();

    return new JsonResponse([
      'status' => 'success',
      'nid' => $nid,
      'message' => 'Node unpublished successfully',
    ]);
  }

  return new JsonResponse([
    'status' => 'forbidden',
    'message' => 'You do not have permission to unpublish this node',
  ], 403);
}
//   public function deleteNode($node) {
//     $nid = $node->id();
//     if ($node->access('delete')) {
//       $node->delete();
//       return new JsonResponse(['status' => 'success', 'nid' => $nid]);
//     }
//     return new JsonResponse(['status' => 'forbidden'], 403);
//   }
}

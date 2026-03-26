<?php

namespace Drupal\custom_likes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\Routing\Annotation\Route;
use Drupal\Core\Database\Database;
use Drupal\user\Entity\User;



/**
 * Handles AJAX Like/Dislike.
 */
class LikeController extends ControllerBase {

  protected $database;
  protected $currentUser;

  public function __construct(Connection $database, AccountProxyInterface $current_user) {
    $this->database = $database;
    $this->currentUser = $current_user;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('current_user')
    );
  }

  /**
   * Toggle like/unlike.
   *
   * @Route("/custom-likes/toggle/{nid}", methods={"POST"}, requirements={"_access"="TRUE"})
   */
  public function toggle($nid) {
    $uid = $this->currentUser->id();

    if ($uid == 0) {
      return new JsonResponse(['message' => 'You must be logged in.']);
    }

    $exists = $this->database->select('custom_likes', 'cl')
      ->fields('cl', ['id'])
      ->condition('nid', $nid)
      ->condition('uid', $uid)
      ->execute()
      ->fetchField();

    if ($exists) {
      $this->database->delete('custom_likes')
        ->condition('nid', $nid)
        ->condition('uid', $uid)
        ->execute();
      $status = 'unliked';
    } else {
      $this->database->insert('custom_likes')
        ->fields(['uid' => $uid, 'nid' => $nid])
        ->execute();
      $status = 'liked';
    }

    $count = $this->database->select('custom_likes', 'cl')
      ->condition('nid', $nid)
      ->countQuery()
      ->execute()
      ->fetchField();

    return new JsonResponse([
      'status' => $status,
      'count' => $count,
    ]);
  }

    public function submitComment() {
    // Retrieve the comment and node ID (nid) from the request
    $comment = \Drupal::request()->get('comment');
    $nid = \Drupal::request()->get('nid'); // Assume nid is being passed with the comment
    
    // Check if the user is logged in
    if ($user = \Drupal::currentUser()) {
      // Get the current timestamp
      $timestamp = date('Y-m-d H:i:s');
      
      // Insert the comment into the database
      $database = Database::getConnection();
      $database->insert('custom_comments')
        ->fields([
          'uid' => $user->id(),
          'nid' => $nid, // Save the node ID (post ID)
          'comment' => $comment,
          'timestamp' => $timestamp,
        ])
        ->execute();

      // Get the username using the user ID
      $username = User::load($user->id())->getAccountName();
      
      // Prepare the response data
      $response = [
        'status' => 'success',
        'username' => $username,
        'comment' => $comment,
        'timestamp' => $timestamp,
        'nid' => $nid, // Include the node ID in the response
      ];
    } else {
      // If not logged in, return an error message
      $response = [
        'status' => 'error',
        'message' => 'You must be logged in to comment.',
      ];
    }

    // Return the response as JSON
    return new JsonResponse($response);
  }

 public function recordShare($nid) {
  $uid = \Drupal::currentUser()->id();
  $connection = \Drupal::database();

  // Insert the share record (no duplicates check for simplicity)
  $connection->insert('custom_shares')
    ->fields([
      'uid' => $uid,
      'nid' => $nid,
      'timestamp' => date('Y-m-d H:i:s'),
    ])
    ->execute();

  // Fetch updated count
  $count = $connection->select('custom_shares', 'cs')
    ->condition('nid', $nid)
    ->countQuery()
    ->execute()
    ->fetchField();

  return new \Symfony\Component\HttpFoundation\JsonResponse([
    'status' => 'shared',
    'count' => $count,
  ]);
}
}
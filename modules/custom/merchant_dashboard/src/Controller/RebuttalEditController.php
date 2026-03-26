<?php

namespace Drupal\marchant_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for product editing.
 */
class RebuttalEditController extends ControllerBase {

  /**
   * Edit product form.
   */
  public function edit($nid) {
    // Load the node
    $node = Node::load($nid);
    
    if (!$node) {
      throw new NotFoundHttpException();
    }

    // Get the edit form
    $form = $this->entityFormBuilder()->getForm($node, 'edit');
    
    return $form;
  }

  /**
   * Access check for product editing.
   */
  public function access(AccountInterface $account, $nid = NULL) {
    // Load the node
    $node = Node::load($nid);
    
    if (!$node) {
      return AccessResult::forbidden();
    }

    // Check if user has permission to edit this node
    $has_access = $node->access('update', $account);
    
    // Additional check: verify user has the merchant role
    $is_merchant = in_array('merchant', $account->getRoles());
    
    return AccessResult::allowedIf($has_access && $is_merchant);
  }

}
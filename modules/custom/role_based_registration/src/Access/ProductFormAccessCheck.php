<?php

namespace Drupal\role_based_registration\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access check for product form.
 */
class ProductFormAccessCheck implements AccessInterface {

  /**
   * Checks access to the product form.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    // Check if user has merchant role AND permission to create products
    $has_merchant_role = in_array('merchant', $account->getRoles());
    $has_permission = $account->hasPermission('create product content');
    
    return AccessResult::allowedIf($has_merchant_role && $has_permission)
      ->addCacheContexts(['user.roles', 'user.permissions']);
  }

}
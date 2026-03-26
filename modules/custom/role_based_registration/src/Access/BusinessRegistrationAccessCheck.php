<?php

namespace Drupal\role_based_registration\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;

class BusinessRegistrationAccessCheck implements AccessInterface {

  public function access(AccountInterface $account) {
    // Example: allow only authenticated users
    return $account->isAuthenticated()
      ? AccessResult::allowed()
      : AccessResult::forbidden();
  }

}

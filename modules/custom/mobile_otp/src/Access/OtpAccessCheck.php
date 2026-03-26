<?php

namespace Drupal\mobile_otp\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks access for OTP verification.
 */
class OtpAccessCheck implements AccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $route->hasRequirement('_otp_access');
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    // Your access logic here
    return AccessResult::allowedIf($account->isAuthenticated());
  }

}
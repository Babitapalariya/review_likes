<?php

namespace Drupal\role_based_registration\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class UserListController.
 *
 * Provides a basic page for user list.
 */
class UserListController extends ControllerBase {

  /**
   * Displays the User List page.
   *
   * @return array
   *   A render array for the user list page.
   */
  public function userListPage() {
    return [
      //'#markup' => $this->t('This is the User List page.'),
    ];
  }

}

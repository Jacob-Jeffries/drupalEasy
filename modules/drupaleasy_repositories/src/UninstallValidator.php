<?php declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories;

use Drupal\Core\Extension\ModuleUninstallValidatorInterface;

/**
 * @todo Add class description.
 */
final class UninstallValidator implements ModuleUninstallValidatorInterface {

  /**
   * @todo Add method description.
   */
  public function validate($module) {
    $reasons = [];
    if ($module == 'drupaleasy_repositories') {
      if ($this->hasRepositories())
    }
  }

}

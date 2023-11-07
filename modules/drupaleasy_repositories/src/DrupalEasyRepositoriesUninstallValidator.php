<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * @todo Add validator description here.
 */
final class DrupalEasyRepositoriesUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new BookUninstallValidator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module): array {
    $reasons = [];
    if ($module === 'drupaleasy_repositories') {
      if ($this->checkUserField()) {
        $reasons[] = $this->t('Please delete Repository URL user profile field content before uninstalling.');
      }
    }
    return $reasons;
  }

  /**
   * Determines if there is data in the repository field.
   *
   * @return bool
   *   TRUE if there is data, FALSE otherwise.
   */
  protected function checkUserField() {
    // Check whether we have data in the user profile field Repository URL.
    $fieldDate = $this->entityTypeManager->getStorage('user')->getQuery()
      ->exists('field_repository_url')
      ->accessCheck(FALSE)
      ->execute();
    return !empty($fieldDate);
  }

}

<?php

namespace Drupal\drupaleasy_repositories;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * DrupalEasy repositories batch service class to integrate with Batch API.
 */
class DrupaleasyRepositoriesBatch {

  use StringTranslationTrait;

  /**
   * Batch service class to integrate with Batch API.
   *
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService $drupaleasyRepositoriesService
   *   The DrupaleasyRepositories service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extensionListModule
   *   The module extension list.
   */
  public function __construct(
    protected DrupaleasyRepositoriesService $drupaleasyRepositoriesService,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ModuleExtensionList $extensionListModule) {}

  /**
   * Updates all user repositories using the Batch API.
   */
  public function updateAllUserRepositories(bool $drush = FALSE): void {
    $operations = [];

    // Get all active users.
    // Exactly like what we did for the CRON task.
    // Get all the active users in the system.
    $user_storage = $this->entityTypeManager->getStorage('user');
    $query = $user_storage->getQuery();
    $query->condition('status', '1');
    $users = $query->accessCheck(FALSE)->execute();

    // Create a Batch API item for each user.
    foreach ($users as $uid => $user) {
      // This is the name of a procedual function defined in the .inc file.
      $operations[] = ['drupaleasy_update_repositories_batch_operation', [$uid]];
    }

    // The finished element is a function that can run at the end
    // of the batch process. A clean-up perhaps.
    $batch = [
      'operations' => $operations,
      'finished' => 'drupaleasy_update_all_repositories_finished',
      'file' => $this->extensionListModule->getPath('drupaleasy_repositories') . '/drupaleasy_repositories.batch.inc',
    ];

    // Submit the batch for processing.
    batch_set($batch);

    // The Drupal Form API normally calls this.
    if ($drush) {
      drush_backend_batch_process();
    }
  }

  /**
   * Batch process callback from updating user repositories.
   *
   * @param int $uid
   *   User ID to update.
   * @param array|\ArrayAccess $context
   *   Context for operations. We do not want to type hint this as an array or
   *   an object as sometimes it is an array (when calling from a form) and
   *   sometimes it is an object (when calling from Drush).
   */
  public function updateRepositoriesBatch(int $uid, array|\ArrayAccess &$context): void {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $account = $user_storage->load($uid);
    $this->drupaleasyRepositoriesService->updateRepositories($account);
    $context['results'][] = $uid;
    $context['results']['num']++;
    $context['message'] = $this->t('Updated repositories belonging to "@username".',
      ['@username' => $account->label()]
    );
  }

}

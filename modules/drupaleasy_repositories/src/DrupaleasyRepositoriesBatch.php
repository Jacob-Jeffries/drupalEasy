<?php declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService;

/**
 * @todo Add class description.
 */
final class DrupaleasyRepositoriesBatch {

  /**
   * Constructs a DrupaleasyRepositoriesBatch object.
   */
  public function __construct(
    private readonly DrupaleasyRepositoriesService $drupaleasyRepositoriesService,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ModuleExtensionList $extensionListModule,
  ) {}

  /**
   * @todo Add method description.
   */
  public function doSomething(): void {
    // @todo Place your code here.
  }

}

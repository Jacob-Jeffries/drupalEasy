<?php

declare(strict_types = 1);

namespace Drupal\Tests\drupaleasy_repositories\Kernel;

use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\drupaleasy_repositories\Traits\RepositoryContentTypeTrait;

/**
 * Tests methods of the main DrupalEasy Repositories service.
 *
 * @group drupaleasy_repositories
 */
class DrupaleasyRepositoriesServiceTest extends KernelTestBase {
  use RepositoryContentTypeTrait;

  /**
   * The drupaleasy_repositories service.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService
   */
  protected DrupaleasyRepositoriesService $drupaleasyRepositoriesService;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'drupaleasy_repositories',
    'node',
    'field',
    'user',
    'system',
    // For text_long field types.
    'text',
    // For link field types.
    'link',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupaleasyRepositoriesService = $this->container->get('drupaleasy_repositories.service');
    $this->createRepositoryContentType();

  }

  /**
   * Test callback.
   */
  public function testSomething() {
    $result = $this->container->get('transliteration')->transliterate('Друпал');
    self::assertSame('Drupal', $result);
  }

}

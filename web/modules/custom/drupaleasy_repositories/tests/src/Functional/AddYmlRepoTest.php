<?php

declare(strict_types = 1);

namespace Drupal\Tests\drupaleasy_repositories\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\drupaleasy_repositories\Traits\RepositoryContentTypeTrait;

/**
 * Main functional test for the entire repo metadata process.
 *
 * @group drupaleasy_repositories
 */
final class AddYmlRepoTest extends BrowserTestBase {
  use RepositoryContentTypeTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'drupaleasy_repositories',
    // 'user',
    // 'node',
    // 'link',
    // 'path',
    // 'text',
    // 'field',
    // 'menu_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable the Yml Remote Plugin in Code.
    // This will add a new row in the DB config table & generate a Yml file.
    $config = $this->config('drupaleasy_repositories.settings');
    $config->set('repositories_plugins', ['yml_remote' => 'yml_remote']);
    $config->save();

    // Create and login as a user with permission to access the
    // Drupal Easy Settings Page.
    // When installing drupal the UID = 1 is created, the Admin user.
    $user = $this->drupalCreateUser(['configure drupaleasy repositories']);
    $this->drupalLogin($user);

    // $this->createRepositoryContentType();

    // // With a functional test like ours, the Repo URL field must be visible.
    // // This means that it needs a view mode configured, such that the test.
    // // Is able to submit the form with the field.
    /** @var \Drupal\Core\Entity\EntityDisplayRepository $entity_display_repository  */
    $entity_display_repository = \Drupal::service('entity_display.repository');
    $entity_display_repository->getFormDisplay('user', 'user', 'default')
      ->setComponent('field_repository_url', ['type' => 'link_default'])
      ->save();
  }

  /**
   * Test callback.
   */
  public function testSomething(): void {
    $admin_user = $this->drupalCreateUser(['access administration pages']);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin');
    $this->assertSession()->elementExists('xpath', '//h1[text() = "Administration"]');
  }

}

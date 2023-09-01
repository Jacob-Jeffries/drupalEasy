<?php

declare(strict_types = 1);

namespace Drupal\Tests\drupaleasy_repositories\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\drupaleasy_repositories\Traits\RepositoryContentTypeTrait;

use function PHPUnit\Framework\fileExists;

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

    // The setup method is run before each test method.
    parent::setUp();

    // Enable the Yml Remote Plugin in Code.
    // This will add a new row in the DB config table & generate a Yml file.
    $config = $this->config('drupaleasy_repositories.settings');
    $config->set('repositories_plugins', ['yml_remote' => 'yml_remote']);
    $config->save();

    // The config saves to the DB.
    // Create and login as a user with permission to access the
    // Drupal Easy Settings Page.
    // When installing drupal the UID = 1 is created, the Admin user.
    $admin_user = $this->drupalCreateUser(['configure drupaleasy repositories']);
    $this->drupalLogin($admin_user);

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
   *
   * @test Identifies the method as a test method, also naming the method name as testSomething does
   * the same thing.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * Public function testSomething(): void {
   * $admin_user = $this->drupalCreateUser(['access administration pages']);
   * $this->drupalLogin($admin_user);
   * $this->drupalGet('admin');
   * $this->
   * assertSession()->elementExists('xpath', '//h1[text() = "Administration"]');
   * }.
   */

  /**
   * Test for testing the authorized but unpriviliged user.
   *
   * Test ananymous user without permissions.
   *
   * @test
   */
  public function testUnprivilegedUser(): void {
    $authUser = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($authUser);
    $session = $this->assertSession();
    $this->drupalGet('admin/config/services/repositories');
    $session->statusCodeEquals(403);
  }

  /**
   * Test that the settings page can be reached and works as expected.
   *
   * This test, tests that an admin user can access the settings page and
   * select a plugin to enable, then submit the page successfully.
   * Essentially testing the route and permission.
   *
   * @return void
   *   Returns nothing.
   *
   * @test
   */
  public function testSettingsPage(): void {
    $user = $this->drupalCreateUser(['configure drupaleasy repositories']);
    $this->drupalLogin($user);

    // Get a handle on the browsing session.
    $session = $this->assertSession();

    // Navigate to Drupal Easy Repo and confirm page.
    $this->drupalGet('admin/config/services/repositories');
    $session->statusCodeEquals(200);

    // Check the YAML remote checkbox.
    // I used the form name instead of the id since that is what a POST sends.
    $edit = [
      'repositories_plugins[yaml_local]' => 'yaml_local',
      'repositories_plugins[gh_remote]' => 0,
    ];

    // Submit the form.
    $session->buttonExists('Save configuration');
    $this->submitForm($edit, 'Save configuration');

    // Confirm Checkbox is still checked after submission.
    $session->statusCodeEquals(200);
    $session->statusMessageExists();
    $session->statusMessageContains('The configuration options have been saved.');
    $session->checkboxChecked('repositories_plugins[yaml_local]');
    $session->checkboxNotChecked('repositories_plugins[gh_remote]');
  }

  /**
   * Test that a yml repo can be added to profile by a user.
   *
   * This tests that a yml-based repo can be added to a user's profile and
   * that a repository node is successfully created upon saving the profile.
   *
   * @test
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testAddYmlRepo(): void {
    // Create and login as a Drupal user with permission to access
    // content.
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);
    // Get a handle on the browsing session.
    $session = $this->assertSession();
    // Navigate to their edit profile page and confirm we can reach it.
    $this->drupalGet('/user/' . $user->id() . '/edit');
    // Try this with a 500 status code to see it fail.
    $session->statusCodeEquals(200);

    /**
     * Define full path to yml file.
     *
     * @var \Drupal\Core\Extension\ModuleHandler $module_handler
     */
    $module_handler = \Drupal::service('module_handler');
    $module = $module_handler->getModule('drupaleasy_repositories');

    // This would make the full path to the module,
    // but it is still not what we need.
    // Folks will have different localhosts I think.
    global $base_url;
    $path = $base_url . '/' . $module->getPath() . '/tests/assets/test.yml';
    fileExists($path);

    // Enter information in the URL Field.
    // Using the module handler service.
    $edit = [
      'field_repository_url[0][uri]' => $path,
    ];
    $this->submitForm($edit, 'Save');
    $session->statusCodeEquals(200);
  }

}

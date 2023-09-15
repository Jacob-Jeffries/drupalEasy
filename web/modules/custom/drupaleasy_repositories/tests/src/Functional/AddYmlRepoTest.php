<?php

declare(strict_types = 1);

namespace Drupal\Tests\drupaleasy_repositories\Functional;

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
    // The user created below is UID = 2.
    $admin_user = $this->drupalCreateUser(['configure drupaleasy repositories']);
    $this->drupalLogin($admin_user);

    // Create Repository content type by using the Traits file bellow.
    // $this->createRepositoryContentType();
    // The config/install are automatically installed and cannot be overwritten.
    // --
    // We could add the field_repository_url creation here (or config/install).
    // FieldStorageConfig::create([
    // 'field_name' => 'field_repository_url',
    // 'type' => 'link',
    // 'entity_type' => 'user',
    // 'cardinality' => -1,
    // ])->save();
    // FieldConfig::create([
    // 'field_name' => 'field_repository_url',
    // 'entity_type' => 'user',
    // 'bundle' => 'user',
    // 'label' => 'Repository URL',
    // ])->save();
    // --
    // With a functional test like ours, the Repo URL field must be visible.
    // This means that it needs a view mode configured, such that the test.
    // Is able to submit the form with the field.
    // Ensure that the new Repository URL field is visible in the existing
    // user entity form mode.
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
    $authenticatedUser = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($authenticatedUser);
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
    $authorizedUser = $this->drupalCreateUser(['configure drupaleasy repositories']);
    $this->drupalLogin($authorizedUser);

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
    $authenticatedUser = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($authenticatedUser);
    // Get a handle on the browsing session.
    $session = $this->assertSession();
    // Navigate to their edit profile page and confirm we can reach it.
    $this->drupalGet('/user/' . $authenticatedUser->id() . '/edit');
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
    $path = $base_url . '/' . $module->getPath() . '/tests/assets/test_repo.yml';

    // Enter information in the URL Field.
    // Using the module handler service.
    $edit = [
      'field_repository_url[0][uri]' => $path,
    ];
    $this->submitForm($edit, 'Save');
    $session->statusCodeEquals(200);
    $session->responseContains('The changes have been saved.');

    // A node is created once the Repo URL is saved.
    // repository is the machine name of our Entity Repository.
    // Query -> Node - > Type = Repository.
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'repository');
    // Check if the current user has access to the returned entities.
    $query->accessCheck(TRUE);
    // Returns an array of node id's that meet our criteria.
    $results = $query->execute();
    $session->assert(count($results) === 1, 'Either 0 or >1 repository nodes were found.');

    // Checking the node -> Node Load!
    // Load the node from the Query and check the node values.
    // DB side, not rendered.
    // Entity_Type.Manager !
    $entity_type_manager = \Drupal::entityTypeManager();

    // Modern drupal has abstracted storage, new providers could be written
    // Outside of the default MySQL database.
    // Memcached.org is the site.
    $node_storage = $entity_type_manager->getStorage('node');
    /**
     * \Drupal::entityTypeManager()->getStorage('node')->load(reset($results));
     *
     * @var \Drupal\node\NodeInterface $node
     */
    $node = $node_storage->load(reset($results));

    // Check values.
    $session->assert($node->get('field_machine_name')->getValue() == 'test-repo', 'Machine name does not match.');

    $session->assert($node->get('field_source')->getValue() == 'yml_remote', 'Source does not match.');

    $session->assert($node->getTitle() == 'The Batman repository', 'Title does not match.');

    $session->assert($node->get('field_description')->getValue() == 'This is where Batman keeps all his crime-fighting code.', 'Description does not match.');

    $session->assert($node->get('field_number_of_issues')->getValue() == '6', 'Number of issues does not match.');

  }

}

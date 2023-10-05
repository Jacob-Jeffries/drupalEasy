<?php

declare(strict_types = 1);

namespace Drupal\Tests\drupaleasy_repositories\Kernel;

use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\drupaleasy_repositories\Traits\RepositoryContentTypeTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

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
    // Install entity schema for Kernel tests, what would the DB look like?
    'node',
    'field',
    'user',
    'system',
    // For text_long field types.
    'text',
    // For link field types.
    'link',
    'key',
  ];

  /**
   * User for our test.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $adminUser;

  /**
   * Returns sample repository metadata, similair to ingesting a Yaml file.
   *
   * @return array<string, array<string, string|int>>
   *   The sample repository info.
   */
  protected function getAquamanRepo(): array {
    // The order of elements in the array matter when calculating a hash.
    $repo['aquaman-repository'] = [
      'label' => 'The Aquaman repository',
      'description' => 'This is where Aquaman keeps all his crime-fighting code.',
      'num_open_issues' => 6,
      'source' => 'yml_remote',
      'url' => 'http://example.com/aquaman-repo.yml',
    ];
    return $repo;
  }

  /**
   * Returns sample repository metadata, similair to ingesting a Yaml file.
   *
   * @return array<string, array<string, string|int>>
   *   The sample repository info.
   */
  protected function getSupermanRepo(): array {
    // The order of elements in the array matter when calculating a hash.
    $repo['superman-repo'] = [
      'label' => 'The Superman repository',
      'description' => 'This is where Superman keeps all his crime-fighting code.',
      'num_open_issues' => 0,
      'source' => 'yml_remote',
      'url' => 'https://example.com/superman-repo.yml',
    ];
    return $repo;
  }

  /**
   * Better way to return repo metadata.
   *
   * @return array<string, array<string, string|int>>
   *   The sample repository info.
   */
  protected function getTestRepo(string $repo_name): array {
    if ($repo_name === 'aquaman') {
      return $this->getAquamanRepo();
    }
    if ($repo_name === 'superman') {
      return $this->getSupermanRepo();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupaleasyRepositoriesService = $this->container->get('drupaleasy_repositories.service');
    $this->createRepositoryContentType();

    // Install the Entity Schemas for our setup.
    // Instead of a full drupal database we are creating tables in our temp DB.
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    // Gathering data to create a Node.
    $aquaman_repo = $this->getTestRepo('aquaman');
    $repo = reset($aquaman_repo);

    // This will be user id "1", no root user is created.
    $this->adminUser = User::create([
      'name' => $this->randomString(),
    ]);
    $this->adminUser->save();

    // There is no session in a Kernel test, we can only set a current user.
    // we are using the current_user service.
    /** @var \Drupal\Core\Session\AccountProxyInterface $current_user_service */
    $current_user_service = $this->container->get('current_user');
    $current_user_service->setAccount($this->adminUser);

    // We are creating our Node directly, so the md5() line never runs.
    $node = Node::create([
      'type' => 'repository',
      'title' => $repo['label'],
      'field_machine_name' => array_key_first($aquaman_repo),
      'field_url' => $repo['url'],
      'field_hash' => 'c8f3fd6cd928e6a1e62239a7fea461e7',
      'field_number_of_issues' => $repo['num_open_issues'],
      'field_source' => $repo['source'],
      'field_description' => $repo['description'],
      'user_id' => $this->adminUser->id(),
    ]);
    // Where is this saving? No db in Kernel!
    $node->save();

    // I am confused by this, but somehow it turns on our plugin.
    $config = $this->config('drupaleasy_repositories.settings');
    $config->set('repositories_plugins', ['yml_remote' => 'yml_remote']);
    $config->save();
  }

  /**
   * Data provider for testIsUnique().
   *
   * @return array<mixed>
   *   Test data and expected results.
   */
  public function providerTestIsUnique(): array {
    return [
      [FALSE, $this->getTestRepo('aquaman')],
      [TRUE, $this->getTestRepo('superman')],
    ];
  }

  /**
   * Test the ability for the service to ensure repositories are unique.
   *
   * @param bool $expected
   *   The expected result.
   * @param array<string, array<string, string|int>> $repo
   *   The test repo array.
   *
   * @covers \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService::isUnique
   * @dataProvider providerTestIsUnique
   * @test
   */
  public function testIsUnique(bool $expected, array $repo): void {

    // The Aquaman Repo is already in the DB - it serves as the original.
    // Normally isUnique() is a protected method, we will use reflection.
    // This essentially calls a public, copy, of the protected method.
    // $actual = $this->drupaleasyRepositoriesService->isUnique($repo, 999);.
    $reflection_isUnique = new \ReflectionMethod($this->drupaleasyRepositoriesService, 'isUnique');
    // PHP 8.1+ automatically changes the protected/public property.
    // $reflection_isUnique->setAccessible(TRUE);
    $actual = $reflection_isUnique->invoke($this->drupaleasyRepositoriesService, $repo, 999);
    $this->assertEquals($expected, $actual, 'The test failed Bruh! You better get Gud!');
  }

  /**
   * Data provider for testValidateRepositoryUrls().
   *
   * @return array<int, array<int, array<int, array<string, string>>|string>>
   *   Test URls and expected results.
   */
  public function providerValidateRepositoryUrls(): array {
    // This is run before setup() and other things so $this->container
    // isn't available here!
    // Normally this is coming from the $form_state of the User form.
    // Module handler paths don't seem to produce a file with Apache2.
    return [
      ['', [['uri' => 'http://localhost/batman-repo.yml']]],
      ['was not found', [['uri' => 'http://localhost/empty-repo.yml']]],
      ['is not valid', [['uri' => 'http://localhost/batman-repo.ym']]],
    ];
  }

  /**
   * Test the ability for the service to ensure repositories are valid.
   *
   * @param string $expected
   *   Presents the expected results.
   * @param array<string, array<array<string, string>>> $urls
   *   Array of testing URLs.
   *
   * @covers \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService::validateRepositoryUrls
   * @dataProvider providerValidateRepositoryUrls
   * @test
   */
  public function testValidateRepositoryUrls(string $expected, array $urls): void {
    $actual = $this->drupaleasyRepositoriesService->validateRepositoryUrls($urls, 999);
    if ($expected) {
      $this->assertTrue((bool) mb_stristr($actual, $expected), "The URLs' validation does not match the expected value. Actual: {$actual}, Expected: {$expected}");
    }
    else {
      $this->assertEquals($expected, $actual, "The URLs' validation does not match the expected value. Actual: {$actual}, Expected: {$expected}");
    }
  }

}

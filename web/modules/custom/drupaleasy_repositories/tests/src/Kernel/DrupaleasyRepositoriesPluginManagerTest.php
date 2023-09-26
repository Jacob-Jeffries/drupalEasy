<?php

declare(strict_types = 1);

namespace Drupal\Tests\drupaleasy_repositories\Kernel;

use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test description.
 *
 * @group drupaleasy_repositories
 */
final class DrupaleasyRepositoriesPluginManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array <mixed> $modules
   */
  protected static $modules = ['drupaleasy_repositories'];

  /**
   * Our plugin manager.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager
   */
  protected DrupaleasyRepositoriesPluginManager $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->manager = $this->container->get('plugin.manager.drupaleasy_repositories');
  }

  /**
   * Test both the plugin manager and the Yml Remote plugin definition and type.
   *
   * @test
   */
  public function testYmlRemoteInstance(): void {
    /** @var \Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories\YmlRemote $example_instance */
    $example_instance = $this->manager->createInstance('yml_remote');
    $plugin_def = $example_instance->getPluginDefinition();

    $this->assertInstanceOf('\Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories\YmlRemote', $example_instance, 'Plugin type does not match');

    $this->assertInstanceOf('\Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase', $example_instance, 'Plugin parent type does not match');

    $this->assertInstanceOf('\Drupal\Component\Plugin\PluginBase', $example_instance, 'Plugin grandparent type does not match');

    $this->assertArrayHasKey('id', $plugin_def, 'The "id" key is missing from the plugin definition.');
    $this->assertArrayHasKey('label', $plugin_def, 'The "label" key is missing from the plugin definition.');
    $this->assertArrayHasKey('description', $plugin_def, 'The "description" key is missing from the plugin definition.');

    $this->assertTrue($plugin_def['id'] == 'yml_remote', 'Plugin id does not match.');
    $this->assertTrue($plugin_def['label'] == 'Yml Remote', 'Plugin label does not match.');
    $this->assertTrue($plugin_def['description'] == 'Remote .yml file that includes repository metadata.', 'Plugin description does not match.');
  }

}

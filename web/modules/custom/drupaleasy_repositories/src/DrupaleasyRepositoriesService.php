<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * @todo Add class description.
 */
final class DrupaleasyRepositoriesService {

  /**
   * The plugin manager for DrupaleasyRepositories.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected PluginManagerInterface $pluginManagerDrupaleasyRepositories;

  /**
   * The configuration factory interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a DrupaleasyRepositories object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager_drupaleasy_repositories
   *   The plugin manager interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory interface.
   */
  public function __construct(PluginManagerInterface $plugin_manager_drupaleasy_repositories, ConfigFactoryInterface $config_factory) {
    $this->pluginManagerDrupaleasyRepositories = $plugin_manager_drupaleasy_repositories;
    $this->configFactory = $config_factory;
  }

  /**
   * Get repository URL help text from each enabled plugin.
   *
   * @return string
   *   The help text.
   */
  public function getValidatorHelpText(): string {
    $repository_plugins = [];
    // Use Null Coalesce Operator in case no repositories are enabled.
    // See https://wiki.php.net/rfc/isset_ternary
    $repository_plugin_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories') ?? [];

    foreach ($repository_plugin_ids as $repository_plugin_id) {
      if (!empty($repository_plugin_id)) {
        $repository_plugins[] = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_plugin_id);
      }
    }

    $help = [];

    /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesInterface $repository_plugin */
    foreach ($repository_plugins as $repository_plugin) {
      $help[] = $repository_plugin->validateHelpText();
    }

    return implode(' ', $help);
  }

}

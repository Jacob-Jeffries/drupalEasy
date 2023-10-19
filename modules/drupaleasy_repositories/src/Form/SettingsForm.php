<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure DrupalEasy Repositories settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * The DrupalEasy repositories manager service.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager
   */
  protected DrupaleasyRepositoriesPluginManager $repositoriesManager;

  /**
   * Constructs an SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager $drupaleasy_repositories_manager
   *   The DrupalEasy repositories manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DrupaleasyRepositoriesPluginManager $drupaleasy_repositories_manager) {
    parent::__construct($config_factory);
    $this->repositoriesManager = $drupaleasy_repositories_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.drupaleasy_repositories')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drupaleasy_repositories_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @return array<mixed>
   *   Mixed array out.
   */
  protected function getEditableConfigNames(): array {
    return ['drupaleasy_repositories.settings'];
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state array.
   *
   * @return array<int, mixed>
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $repositories = $this->repositoriesManager->getDefinitions();
    $repositories_config = $this
      ->config('drupaleasy_repositories.settings')
      ->get('repositories_plugins') ?? [];

    uasort($repositories, function ($a, $b) {
      return Unicode::strcasecmp($a['label'], $b['label']);
    });
    $repository_options = [];
    foreach ($repositories as $repository => $definition) {
      $repository_options[$repository] = $definition['label'];
    }

    // $form['repositories_plugins'] = [
    // '#type' => 'checkboxes',
    // '#options' => [
    // 'yaml_remote' => $this->t('Yaml Remote'),
    // 'gh_remote' => $this->t('GitHub Remote'),
    // ],
    // '#title' => $this->t('Repository plugins'),
    // '#default_value' => $repositories_config,
    // ];
    // With the new code below, the options are gleaned from the pluginManager.
    $form['repositories_plugins'] = [
      '#type' => 'checkboxes',
      '#options' => $repository_options,
      '#title' => $this->t('Available Repository Plugins'),
      '#default_value' => $repositories_config,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form constructor.
   *
   * @param array<mixed> $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('drupaleasy_repositories.settings')
      ->set('repositories_plugins', $form_state->getValue('repositories_plugins'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}

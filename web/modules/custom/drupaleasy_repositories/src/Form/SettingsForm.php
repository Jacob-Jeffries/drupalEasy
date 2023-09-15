<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure DrupalEasy Repositories settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

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
   * Form constructor.
   *
   * @param array<mixed> $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array<mixed>
   *   The form structure.
   *   The <mixed> attribute has been added to define the itterable array.
   *   Instead of inheriting we are redefining it to pass phpstan lvl=6.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // THIS IS THE ADMIN FORM FOR THE MODULE, it enables/disables DER plugins.
    $repositories_config = $this
      ->config('drupaleasy_repositories.settings')
      ->get('repositories_plugins') ?? [];
    $form['repositories_plugins'] = [
      '#type' => 'checkboxes',
      '#options' => [
        'yaml_local' => $this->t('Yaml Local'),
        'gh_remote' => $this->t('GitHub Remote'),
      ],
      '#title' => $this->t('Repository plugins'),
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

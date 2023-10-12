<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesBatch;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a DrupalEasy Repositories form.
 */
final class UpdateRepositoriesForm extends FormBase {

  /**
   * Class constructor.
   *
   * @param Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService $repositoriesService
   *   The DrupalEasy repositories service.
   * @param Drupal\drupaleasy_repositories\DrupaleasyRepositoriesBatch $drupaleasyRepositoriesBatch
   *   The DrupalEasy repositories batch service.
   * @param Drupal\Core\Form\FormStateInterface $entityTypeManager
   *   The Entity type manager service.
   */
  public function __construct(protected DrupaleasyRepositoriesService $repositoriesService, protected DrupaleasyRepositoriesBatch $drupaleasyRepositoriesBatch, protected EntityTypeManagerInterface $entityTypeManager) {
    $this->repositoriesService = $repositoriesService;
    $this->drupaleasyRepositoriesBatch = $drupaleasyRepositoriesBatch;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): UpdateRepositoriesForm {
    return new static(
      $container->get('drupaleasy_repositories.service'),
      $container->get('drupaleasy_repositories.batch'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drupaleasy_repositories_update_repositories';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['uid'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#title' => $this->t('Username'),
      '#description' => $this->t('Leave blank to update all repository nodes for all users.'),
      '#required' => FALSE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Go'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if (!is_null($form_state->getValue('uid'))) {

    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->messenger()->addStatus($this->t('The message has been sent.'));
    $form_state->setRedirect('<front>');
  }

}

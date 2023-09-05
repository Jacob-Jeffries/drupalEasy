<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories\DrupaleasyRepositories;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for drupaleasy_repositories plugins.
 *
 * This is an interface that all of our plugin types should adhere to. We've
 * extended the class so that folks do not have to extend our intergaface.
 */
interface DrupaleasyRepositoriesInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Returns the translated plugin label.
   */
  public function label(): string;

  /**
   * URL validator.
   *
   * @param string $uri
   *   The URI to validate.
   *
   * @return bool
   *   Returns TRUE if the validation passes.
   */
  public function validate(string $uri): bool;

  /**
   * Returns help text for the plugin's URL pattern required.
   *
   * @return string
   *   The help text string.
   */
  public function validateHelpText(): string;

  /**
   * Queries the repository source for info about a repository.
   *
   * @param string $uri
   *   The URI of the repo.
   *
   * @return array<mixed>
   *   The metadata of each repository.
   */
  public function getRepo(string $uri): array;

}

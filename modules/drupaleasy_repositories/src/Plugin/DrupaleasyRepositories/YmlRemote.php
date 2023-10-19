<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories;

use Drupal\Component\Serialization\Yaml;
use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase;

/**
 * Plugin implementation of the drupaleasy_repositories.
 *
 * @DrupaleasyRepositories(
 *   id = "yml_remote",
 *   label = @Translation("Yml Remote"),
 *   description = @Translation("Remote .yml file that includes repository metadata.")
 * )
 */
final class YmlRemote extends DrupaleasyRepositoriesPluginBase {

  /**
   * {@inheritdoc}
   */
  public function validate(string $uri): bool {
    // The form has to cycle through all the plugins to test for "TRUE"
    // from any plugin. For example, this validates Yml files,
    // an other plugin will validate github etc.
    $pattern = '|^https?://[a-zA-Z0-9.\-]+/[a-zA-Z0-9_\-.%/]+\.ya?ml$|';

    if (preg_match($pattern, $uri) === 1) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateHelpText(): string {
    return 'https://anything.anything/anything/anything.yml (or "http")';
  }

  /**
   * {@inheritdoc}
   */
  public function getRepo(string $uri): array {
    // Temporarily set the PHP error handler to this custom one. If there are
    // any E_WARNINGs, then TRUE to disable default PHP error handler.
    // This tells PHP that we are going to handle errors of type E_WARNING,
    // Until we say otherwise.
    set_error_handler(function () {
      // If FALSE is returned, then the default PHP error handler is run as
      // well.
      return TRUE;
    },
    E_WARNING
    );

    // The file_exists PHP function doesn't work with files over http.
    // If $uri doesn't exist, file() will throw a PHP E_WARNING.
    if (file($uri)) {
      // Restore the default PHP error handler.
      restore_error_handler();
      if ($file_content = file_get_contents($uri)) {
        $repo_info = Yaml::decode($file_content);
        $repo_machine_name = array_key_first($repo_info);
        $repo_data = reset($repo_info);

        $this->messenger->addStatus($this->t('The Repository has been found.'));

        // Return our data in a preformatted array.
        return $this->mapToCommonFormat($repo_machine_name, $repo_data['label'], $repo_data['description'], $repo_data['num_open_issues'], $uri);
      }
      return [];
    }
    else {
      // Restore the default PHP error handler.
      restore_error_handler();
      return [];
    }
  }

}

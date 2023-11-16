<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_gitlab\Plugin\DrupaleasyRepositories;

use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase;
use Gitlab\Client;

/**
 * Plugin implementation of the drupaleasy_repositories.
 *
 * @DrupaleasyRepositories(
 *   id = "gitlab",
 *   label = @Translation("GitLab"),
 *   description = @Translation("GitLab.com")
 * )
 */
final class Gitlab extends DrupaleasyRepositoriesPluginBase {

  /**
   * {@inheritdoc}
   */
  public function validate(string $uri): bool {
    // Gitlab Repo:
    // https://gitlab.com/<NAMESPAVE>/<PROJECT_PATH>
    $pattern = '|^https://gitlab.com/[a-zA-Z0-9_\-/]+/[a-zA-Z0-9_\-/]+$|';
    if (preg_match($pattern, $uri) === 1) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateHelpText(): string {
    return 'https://gitlab.com/<NAMESPACE>/<PROJECT_PATH>';
  }

  /**
   * {@inheritdoc}
   */
  public function getRepo(string $uri): array {

    $uriComp = explode("/", $uri);
    $namespace = $uriComp[3];
    $project_path = $uriComp[4];
    $url_path = $namespace . '/' . $project_path;

    // No need for authentication for this API call.
    $this->client = new Client();

    $gitlab_info = [];
    $gitlab_issues = [];

    // TC block for project info.
    try {
      $gitlab_info = $this->client->projects()->show($url_path);
    }
    catch (\Throwable $th) {
      $this->messenger->addError($this->t('GitLab error: @error', [
        '@error' => $th->getMessage(),
      ]));
      return [];
    }

    // TC block for issues.
    try {
      $gitlab_issues = $this->client->issues()->all($url_path);
    }
    catch (\Throwable $th) {
      $this->messenger->addError($this->t('GitLab error: @error', [
        '@error' => $th->getMessage(),
      ]));
      return [];
    }

    $open_issues = 0;

    foreach ($gitlab_issues as $issue) {
      if ($issue['state'] == "opened") {
        $open_issues++;
      }
    }

    return $this->mapToCommonFormat($gitlab_info['path'], $gitlab_info['name'], $gitlab_info['description'], $open_issues, $gitlab_info['web_url']);
  }

}

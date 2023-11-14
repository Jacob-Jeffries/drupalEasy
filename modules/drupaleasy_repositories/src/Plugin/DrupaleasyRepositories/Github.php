<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories;

use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase;
use Github\AuthMethod;
use Github\Client;

/**
 * Plugin implementation of the drupaleasy_repositories.
 *
 * @DrupaleasyRepositories(
 *   id = "github",
 *   label = @Translation("GitHub"),
 *   description = @Translation("GitHub.com")
 * )
 */
final class Github extends DrupaleasyRepositoriesPluginBase {

  /**
   * {@inheritdoc}
   */
  public function validate(string $uri): bool {
    // Github repo:
    // https://github.com/{owner}/{repo}
    $pattern = '|^https://github.com/[a-zA-Z0-9_\-/]+/[a-zA-Z0-9_\-/]+$|';

    if (preg_match($pattern, $uri) === 1) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateHelpText(): string {
    return 'https://github.com/{owner_name}/{repo_name}';
  }

  /**
   * {@inheritdoc}
   */
  public function getRepo(string $uri): array {
    // Parse incoming URI into its component parts.
    // https://github.com/KnpLabs/php-github-api
    $uriComp = explode("/", $uri);
    $owner_name = $uriComp[3];
    $repo_name = $uriComp[4];

    // Setup authentication from the API.
    // https://github.com/KnpLabs/php-github-api/blob/master/doc/security.md
    // $this->setAuthentication();
    $this->client = new Client();

    // Get the metadata from the API.
    try {
      $repo = $this->client->api('repo')->show($owner_name, $repo_name);
    }
    catch (\Throwable $th) {
      // Do something with the exception extends Throwable.
      $this->messenger->addMessage($this->t('GitHub error: @error', [
        '@error' => $th->getMessage(),
      ]));
      return [];
    }
    finally{
      // This code gets run no matter what.
    }

    // Map metadata to common format.
    // $machine_name, $label, $description, $num_open_issues, $uri.
    return $this->mapToCommonFormat($repo['full_name'], $repo['name'], $repo['description'], $repo['open_issues_count'], $repo['html_url']);
  }

  /**
   * Authenticate with Github.
   */
  protected function setAuthentication(): void {
    $this->client = new Client();

    // $this->keyRepository->getKey('github')->getKeyValues();
    // The authenticate() method does not actually call the Github API,
    // rather it only stores the authentication info in $client for use when
    // $client makes an API call that requires authentication.
    $github_key = $this->keyRepository->getKey('github')->getKeyValues();

    $this->client->authenticate($github_key['username'], $github_key['key'], AuthMethod::CLIENT_ID);

    // Test the credentials with the following code block.
    try {
      $email = $this->client->currentUser()->emails()->allPublic();
      $this->messenger->addMessage('GitHub Authentication Successful');
      $this->messenger->addMessage('Emails Received: ' . $email[0]['email']);
    }
    catch (\Throwable $th) {
      $this->messenger->addMessage($this->t('GitHub error: @error', [
        '@error' => $th->getMessage(),
      ]));
    }
  }

}

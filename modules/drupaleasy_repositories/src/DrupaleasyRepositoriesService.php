<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * This is our custom service for the plugin.
 */
final class DrupaleasyRepositoriesService {
  use StringTranslationTrait;

  /**
   * Constructs a DrupaleasyRepositories object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManagerDrupaleasyRepositories
   *   The plugin manager interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory interface.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param bool $dryRun
   *   When set to TRUE, no nodes are CRUD'd, defaulted to FALSE.
   *
   *   Using property promotion, we do not need to list properties.
   */
  public function __construct(
    protected PluginManagerInterface $pluginManagerDrupaleasyRepositories,
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected bool $dryRun = FALSE,
  ) {}

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
    // Indeed this was a typo in the handout -> it is "repositories_plugins".
    $repository_plugin_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories_plugins') ?? [];

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

  /**
   * Validate repository URLs.
   *
   * Validate the URLs are valid based on the enabled plugins and ensure they
   * haven't been added by another user.
   *
   * @param array<mixed> $urls
   *   The urls to be validated.
   * @param int $uid
   *   The user id of the user submitting the URLs.
   *
   * @return string
   *   Errors reported by plugins.
   */
  public function validateRepositoryUrls(array $urls, int $uid): string {
    $errors = [];
    $repository_plugins = [];

    // Get IDs all DrupaleasyRepository plugins (enabled or not).
    // repositories_plugins.
    $repository_plugin_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories_plugins') ?? [];

    // Instantiate each enabled DrupaleasyRepository plugin (and confirm that
    // at least one is enabled).
    $atLeastOne = FALSE;
    foreach ($repository_plugin_ids as $repository_plugin_id) {
      if (!empty($repository_plugin_id)) {
        $atLeastOne = TRUE;
        $repository_plugins[] = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_plugin_id);
      }
    }
    if (!$atLeastOne) {
      return 'There are no enabled repository plugins.';
    }

    // Loop around each Repository URL and attempt to validate.
    foreach ($urls as $url) {
      if (is_array($url)) {
        if ($uri = trim($url['uri'])) {
          $is_valid_url = FALSE;
          // Check to see if the URI is valid for any enabled plugins.
          /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesInterface $repository_plugin */
          foreach ($repository_plugins as $repository_plugin) {
            if ($repository_plugin->validate($uri)) {
              $is_valid_url = TRUE;
              $repo_info = $repository_plugin->getRepo($uri);
              if ($repo_info) {
                if (!$this->isUnique($repo_info, $uid)) {
                  $errors[] = $this->t('The repository at %uri has been added by another user.', ['%uri' => $uri]);
                  break;
                }
              }
              else {
                $errors[] = $this->t('The repository at the url %uri was not found.', ['%uri' => $uri]);
              }
            }
          }
          if (!$is_valid_url) {
            $errors[] = $this->t('The repository url %uri is not valid.', ['%uri' => $uri]);
          }
        }
      }
    }

    if ($errors) {
      return implode(' ', $errors);
    }
    // No errors found.
    return '';
  }

  /**
   * Update the repository nodes for a given account.
   *
   * @param \Drupal\Core\Entity\EntityInterface $account
   *   The user account whose repositories to update.
   *   We are using EntityInterface, because that is what Drush returns.
   *
   * @return bool
   *   TRUE if successful.
   */
  public function updateRepositories(EntityInterface $account): bool {
    // Query plugins.
    // Get URLs & match plugin type.
    // Loop through getRepo(), and get Metadata.
    // Create or Update Node.
    // Delete nodes that have been removed (no URL).
    $repos_metadata = [];
    $repositories_plugin_ids = $this->configFactory
      ->get('drupaleasy_repositories.settings')
      ->get('repositories_plugins') ?? [];

    // We are going to initialize all the plugins, validate, then getRepo().
    foreach ($repositories_plugin_ids as $repository_plugin_id) {
      if (!empty($repository_plugin_id)) {

        // The plugin manager can create instances.
        /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesInterface $repository_plugin */
        $repository_plugin = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_plugin_id);

        // Loop through repository URLs.
        // The field is on the user object, we set this up in the begining.
        foreach ($account->field_repository_url ?? [] as $url) {
          // Check if the URL validates for this repository.
          // Using the validate to make sure we have the correct plugin.
          if ($repository_plugin->validate($url->uri)) {
            $uri = $url->uri;
            // Confirm the repository exists and get metadata.
            // If the getRepo() finds something the expression is true.
            if ($repo_metadata = $repository_plugin->getRepo($uri)) {
              $repos_metadata += $repo_metadata;
            }
          }
        }

      }
    }
    return $this->updateRepositoryNodes($repos_metadata, $account) || $this->deleteRepositoryNodes($repos_metadata, $account);
  }

  /**
   * Update repository nodes for a given user.
   *
   * @param array<string, array<string, string|int>> $repos_info
   *   Repository info from API call.
   * @param \Drupal\Core\Entity\EntityInterface $account
   *   The user account whose repositories to update.
   *
   * @return bool
   *   TRUE if successful.
   */
  protected function updateRepositoryNodes(array $repos_info, EntityInterface $account): bool {
    // Does this metadata already exist?
    // No -> Create Node.
    // Yes -> compare hash update or die.
    // Remove all nodes of type Repo that belong to this user without URL.
    // Entity Querey -> as if entities are SQL based.
    if (!$repos_info) {
      // The user does not have any repo URLs in their profile.
      return FALSE;
    }

    // Prepare the storage and query stuff.
    // Give me all the storage perameters for all 'nodes'.
    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    foreach ($repos_info as $key => $repo_info) {
      // Calculate Hash value.
      $hash = md5(serialize($repo_info));

      // We are building a query very similiar to how an ORM would work.
      // Adding the QueryInterface (phpstan) fixes the accessCheck error thrown.
      /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
      $query = $node_storage->getQuery();
      $query->condition('type', 'repository')
        ->condition('uid', $account->id())
        ->condition('field_machine_name', $key)
        ->condition('field_source', $repo_info['source'])
        ->accessCheck(FALSE);
      $results = $query->execute();

      if ($results) {
        /** @var \Drupal\node\Entity\Node $node */
        // The all the stroage types have a load method.
        // Reset points to the first Key in the array.
        $node = $node_storage->load(reset($results));

        if ($hash != $node->get('field_hash')->value) {
          // Something changed, update node.
          $node->setTitle((string) $repo_info['label']);
          $node->set('field_description', $repo_info['description']);
          $node->set('field_machine_name', $key);
          $node->set('field_number_of_issues', $repo_info['num_open_issues']);
          $node->set('field_source', $repo_info['source']);
          $node->set('field_url', $repo_info['url']);
          $node->set('field_hash', $hash);
          // If we stop here, only the node instance is updated, we still need to
          // save to the DB.
          if (!$this->dryRun) {
            $node->save();
            // $this->repoUpdated($node, 'updated');
          }
        }
      }
      else {
        // Repository node doesn't exist - create a new one.
        // The field API values are the ones we've added that start with field_.
        /** @var \Drupal\node\NodeInterface $node */
        $node = $node_storage->create([
          'uid' => $account->id(),
          'type' => 'repository',
          'title' => $repo_info['label'],
          'field_description' => $repo_info['description'],
          'field_machine_name' => $key,
          'field_number_of_issues' => $repo_info['num_open_issues'],
          'field_source' => $repo_info['source'],
          'field_url' => $repo_info['url'],
          'field_hash' => $hash,
        ]);
        if (!$this->dryRun) {
          $node->save();
        }
      }
    }
    return FALSE;
  }

  /**
   * Delete repository nodes deleted from the source for a given user.
   *
   * @param array<string, array<string, string>> $repos_info
   *   Repository info from API call.
   * @param \Drupal\Core\Entity\EntityInterface $account
   *   The user account whose repositories to update.
   *
   * @return bool
   *   TRUE if successful.
   */
  protected function deleteRepositoryNodes(array $repos_info, EntityInterface $account): bool {
    // Prepare the storage and query stuff.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $node_storage->getQuery();
    $query->condition('type', 'repository')
      ->condition('uid', $account->id())
      ->accessCheck(FALSE);
    // We can't chain this above because $repos_info might be empty.
    if ($repos_info) {
      $query->condition('field_machine_name', array_keys($repos_info), 'NOT IN');
    }
    $results = $query->execute();
    if ($results) {
      $nodes = $node_storage->loadMultiple($results);
      /** @var \Drupal\node\Entity\Node $node */
      foreach ($nodes as $node) {
        if (!$this->dryRun) {
          $node->delete();
          // $this->repoUpdated($node, 'deleted');
        }
      }
    }
    return TRUE;
  }

  /**
   * Check to see if the repository is unique.
   *
   * @param array $repo_info
   *   The repository info.
   * @param int $uid
   *   The user ID of the submitter.
   *
   * @return bool
   *   Return true if the repository is unique.
   */
  protected function isUnique(array $repo_info, int $uid): bool {
    $node_storage = $this->entityTypeManager->getStorage('node');

    $repo_metadata = array_pop($repo_info);

    // Look for repository nodes with a matching url.
    $query = $node_storage->getQuery();
    $results = $query->condition('type', 'repository')
      ->condition('field_url', $repo_metadata['url'])
      ->condition('uid', $uid, '<>')
      ->accessCheck(FALSE)
      ->execute();

    if ($results) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

}

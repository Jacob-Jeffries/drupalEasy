<?php

namespace Drupal\drupaleasy_repositories\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\node\NodeInterface;

/**
 * Event that is fired when a repository is created/updated/deleted.
 */
class RepoUpdatedEvent extends Event {

  /**
   * The name of the event triggered when a repo is updated.
   *
   * @Event
   *
   * @var string
   */
  const REPO_UPDATED = 'drupaleasy_repositories_repo_updated';

  /**
   * The repository node created, updated, or deleted.
   *
   * @var \Drupal\node\NodeInterface
   */
  public NodeInterface $node;

  /**
   * The action performed on the node, e.g. 'created', 'updated', or 'deleted'.
   *
   * @var string
   */
  public string $action;

  /**
   * Constructs the object.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node that was created/updated/deleted.
   * @param string $action
   *   The action performed on the node.
   */
  public function __construct(NodeInterface $node, string $action) {
    $this->node = $node;
    $this->action = $action;
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_notify\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\drupaleasy_repositories\Event\RepoUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * DrupalEasy Notify event Subscriber.
 */
final class DrupaleasyNotifySubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * Constructs a DrupaleasyNotifySubscriber object.
   *
   * @param Drupal\Core\Messenger\MessengerInterface $messenger
   *   Drupal's core messenger service.
   */
  public function __construct(
    private readonly MessengerInterface $messenger,
  ) {}

  /**
   * Repo updated event handler.
   *
   * Displays a message for the user whenever a repo node is updated.
   *
   * @param \Drupal\drupaleasy_repositories\Event\RepoUpdatedEvent $event
   *   Repo updated event.
   */
  public function onRepoUpdated(RepoUpdatedEvent $event): void {
    $this->messenger->addStatus($this->t('The repo named %repo_name has been @action (@repo_url). The repo node is owned by @author_name (@author_id).', [
      '%repo_name' => $event->node->getTitle(),
      '@action' => $event->action,
      '@repo_url' => $event->node->toLink()->getUrl()->toString(),
      '@author_name' => $event->node->getOwner()->label(),
      '@author_id' => $event->node->getOwnerId(),
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      RepoUpdatedEvent::REPO_UPDATED => ['onRepoUpdated'],
    ];
  }

}

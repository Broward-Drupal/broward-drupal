<?php

namespace Drupal\entity_share_server\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller to generate list of channels URLs.
 */
class EntryPoint extends ControllerBase {

  /**
   * Controller to list all the resources.
   */
  public function index() {
    $self = Url::fromRoute('entity_share_server.resource_list')
      ->setOption('absolute', TRUE)
      ->toString();
    $urls = [
      'self' => $self,
    ];
    $data = [
      'channels' => [],
    ];

    /** @var \Drupal\entity_share_server\Entity\ChannelInterface[] $channels */
    $channels = $this->entityTypeManager()
      ->getStorage('channel')
      ->loadMultiple();

    $languages = $this->languageManager()->getLanguages(LanguageInterface::STATE_ALL);

    /** @var \Drupal\Core\Session\AccountProxyInterface $current_user */
    $current_user = $this->currentUser();
    $current_user = $current_user->getAccount();
    if ($current_user instanceof UserInterface) {
      $uuid = $current_user->uuid();

      foreach ($channels as $channel) {
        // Check access for this user.
        if (in_array($uuid, $channel->get('authorized_users'))) {
          $channel_langcode = $channel->get('channel_langcode');
          $route_name = sprintf('jsonapi.%s--%s.collection', $channel->get('channel_entity_type'), $channel->get('channel_bundle'));
          $url = Url::fromRoute($route_name)
            ->setOption('language', $languages[$channel_langcode])
            ->setOption('absolute', TRUE)
            ->setOption('query', $channel->getQuery());

          // Prepare an URL to get only the UUIDs.
          $url_uuid = clone($url);
          $query = $url_uuid->getOption('query');
          $query = (!is_null($query)) ? $query : [];
          $url_uuid->setOption('query',
            $query + ['fields[' . $channel->get('channel_entity_type') . '--' . $channel->get('channel_bundle') . ']' => 'id']
          );

          $data['channels'][$channel->id()] = [
            'label' => $channel->label(),
            'url' => $url->toString(),
            'url_uuid' => $url_uuid->toString(),
          ];
        }
      }
    }

    return new JsonResponse([
      'data' => $data,
      'links' => $urls,
    ]);
  }

}

<?php

namespace Drupal\entity_share_client\Service;

use Drupal\Component\Serialization\Json;
use Drupal\entity_share_client\Entity\RemoteInterface;
use GuzzleHttp\Client;

/**
 * Class RemoteManager.
 *
 * @package Drupal\entity_share_client\Service
 */
class RemoteManager implements RemoteManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function prepareClient(RemoteInterface $remote) {
    $http_client = new Client([
      'base_uri' => $remote->get('url'),
      'cookies' => TRUE,
      'allow_redirects' => TRUE,
    ]);

    $http_client->post('/user/login', [
      'form_params' => [
        'name' => $remote->get('basic_auth_username'),
        'pass' => $remote->get('basic_auth_password'),
        'form_id' => 'user_login_form',
      ],
    ]);

    return $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareJsonApiClient(RemoteInterface $remote) {
    return new Client([
      'base_uri' => $remote->get('url'),
      'auth' => [
        $remote->get('basic_auth_username'),
        $remote->get('basic_auth_password'),
      ],
      'headers' => [
        'Content-type' => 'application/vnd.api+json',
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getChannelsInfos(RemoteInterface $remote) {
    $http_client = $this->prepareJsonApiClient($remote);

    $json_response = $http_client->get('entity_share')
      ->getBody()
      ->getContents();
    $json = Json::decode($json_response);

    return $json['data']['channels'];
  }

}

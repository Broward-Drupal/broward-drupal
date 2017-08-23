<?php

namespace Drupal\entity_share_client\Service;

use Drupal\entity_share_client\Entity\RemoteInterface;

/**
 * Remote manager interface methods.
 */
interface RemoteManagerInterface {

  /**
   * Prepare an HTTP client authenticated to handle private files.
   *
   * @param \Drupal\entity_share_client\Entity\RemoteInterface $remote
   *   The remote website on which to prepare the client.
   *
   * @return \GuzzleHttp\Client
   *   An HTTP client with some info from the remote.
   */
  public function prepareClient(RemoteInterface $remote);

  /**
   * Prepare an HTTP client for the JSON API endpoints.
   *
   * @param \Drupal\entity_share_client\Entity\RemoteInterface $remote
   *   The remote website on which to prepare the client.
   *
   * @return \GuzzleHttp\Client
   *   An HTTP client with some info from the remote.
   */
  public function prepareJsonApiClient(RemoteInterface $remote);

  /**
   * Get the channels infos of a remote website.
   *
   * @param \Drupal\entity_share_client\Entity\RemoteInterface $remote
   *   The remote website on which to get the channels infos.
   *
   * @return array
   *   An array of channel infos as returned by entity_share_server entry point.
   */
  public function getChannelsInfos(RemoteInterface $remote);

}

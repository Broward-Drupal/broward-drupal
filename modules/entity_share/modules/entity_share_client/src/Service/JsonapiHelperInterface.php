<?php

namespace Drupal\entity_share_client\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\entity_share_client\Entity\RemoteInterface;

/**
 * Remote manager interface methods.
 */
interface JsonapiHelperInterface {

  /**
   * Prepare entities from an URI to request.
   *
   * @param array $json_data
   *   An array of data send by the JSON API..
   *
   * @return array
   *   The array of options for the tableselect form type element.
   */
  public function buildEntitiesOptions(array $json_data);

  /**
   * Helper function to unserialize an entity from the JSON API response.
   *
   * TODO: Should this method be removed from the interface?
   *
   * @param array $data
   *   An array of data.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   An unserialize entity.
   */
  public function extractEntity(array $data);

  /**
   * Create or update the entity reference field values of an entity.
   *
   * TODO: Should this method be removed from the interface?
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to update.
   * @param array $data
   *   An array of data.
   */
  public function updateRelationships(ContentEntityInterface $entity, array $data);

  /**
   * Create or update the entity reference field values of an entity.
   *
   * TODO: Should this method be removed from the interface?
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to update.
   * @param array $data
   *   An array of data. Can be modified to change the URI if needed.
   */
  public function handlePhysicalFiles(ContentEntityInterface $entity, array &$data);

  /**
   * Use data from the JSONAPI to import content.
   *
   * @param array $entity_list_data
   *   An array of data from a JSONAPI endpoint.
   *
   * @return int[]
   *   The list of entity ids imported.
   */
  public function importEntityListData(array $entity_list_data);

  /**
   * Set the remote to get content from.
   *
   * @param \Drupal\entity_share_client\Entity\RemoteInterface $remote
   *   The remote website to get content from.
   */
  public function setRemote(RemoteInterface $remote);

  /**
   * Uniformize JSON data in case of single value.
   *
   * @param array $data
   *   The JSON data.
   *
   * @return array
   *   An array of data.
   */
  public function prepareData(array $data);

}

<?php

namespace Drupal\entity_share_server\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Channel entities.
 */
interface ChannelInterface extends ConfigEntityInterface {

  /**
   * Remove an authorized user if present. Do not save the entity.
   *
   * @param string $uuid
   *   The uuid of the user to remove.
   *
   * @return bool
   *   TRUE if the authorized_users property has been changed. FALSE otherwise.
   */
  public function removeAuthorizedUser($uuid);

  /**
   * Generate URL query.
   *
   * @return array
   *   The query options to use to request JSON API.
   */
  public function getQuery();

}

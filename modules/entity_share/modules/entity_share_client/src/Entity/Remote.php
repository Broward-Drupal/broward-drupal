<?php

namespace Drupal\entity_share_client\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Remote entity.
 *
 * @ConfigEntityType(
 *   id = "remote",
 *   label = @Translation("Remote"),
 *   handlers = {
 *     "list_builder" = "Drupal\entity_share_client\RemoteListBuilder",
 *     "form" = {
 *       "add" = "Drupal\entity_share_client\Form\RemoteForm",
 *       "edit" = "Drupal\entity_share_client\Form\RemoteForm",
 *       "delete" = "Drupal\entity_share_client\Form\RemoteDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\entity_share_client\RemoteHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "remote",
 *   admin_permission = "administer_remote_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/entity_share/remote/{remote}",
 *     "add-form" = "/admin/config/services/entity_share/remote/add",
 *     "edit-form" = "/admin/config/services/entity_share/remote/{remote}/edit",
 *     "delete-form" = "/admin/config/services/entity_share/remote/{remote}/delete",
 *     "collection" = "/admin/config/services/entity_share/remote"
 *   }
 * )
 */
class Remote extends ConfigEntityBase implements RemoteInterface {

  /**
   * The Remote ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Remote label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Remote URL.
   *
   * @var string
   */
  protected $url;

  /**
   * The Remote basic auth username.
   *
   * @var string
   */
  protected $basic_auth_username;

  /**
   * The Remote basic auth password.
   *
   * @var string
   */
  protected $basic_auth_password;

}

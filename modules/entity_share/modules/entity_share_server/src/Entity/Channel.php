<?php

namespace Drupal\entity_share_server\Entity;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Language\LanguageInterface;

/**
 * Defines the Channel entity.
 *
 * @ConfigEntityType(
 *   id = "channel",
 *   label = @Translation("Channel"),
 *   handlers = {
 *     "list_builder" = "Drupal\entity_share_server\ChannelListBuilder",
 *     "form" = {
 *       "add" = "Drupal\entity_share_server\Form\ChannelForm",
 *       "edit" = "Drupal\entity_share_server\Form\ChannelForm",
 *       "delete" = "Drupal\entity_share_server\Form\ChannelDeleteForm",
 *       "filter_add" = "Drupal\entity_share_server\Form\FilterAddForm",
 *       "filter_edit" = "Drupal\entity_share_server\Form\FilterEditForm",
 *       "filter_delete" = "Drupal\entity_share_server\Form\FilterDeleteForm",
 *       "sort_add" = "Drupal\entity_share_server\Form\SortAddForm",
 *       "sort_edit" = "Drupal\entity_share_server\Form\SortEditForm",
 *       "sort_delete" = "Drupal\entity_share_server\Form\SortDeleteForm",
 *       "group_add" = "Drupal\entity_share_server\Form\GroupAddForm",
 *       "group_edit" = "Drupal\entity_share_server\Form\GroupEditForm",
 *       "group_delete" = "Drupal\entity_share_server\Form\GroupDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\entity_share_server\ChannelHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "channel",
 *   admin_permission = "administer_channel_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/entity_share/channel/{channel}",
 *     "add-form" = "/admin/config/services/entity_share/channel/add",
 *     "edit-form" = "/admin/config/services/entity_share/channel/{channel}/edit",
 *     "delete-form" = "/admin/config/services/entity_share/channel/{channel}/delete",
 *     "collection" = "/admin/config/services/entity_share/channel",
 *     "filter-add" = "/admin/config/services/entity_share/channel/{channel}/filters/add",
 *     "filter-edit" = "/admin/config/services/entity_share/channel/{channel}/filters/{filter}/edit",
 *     "filter-delete" = "/admin/config/services/entity_share/channel/{channel}/filters/{filter}/delete",
 *     "sort-add" = "/admin/config/services/entity_share/channel/{channel}/sorts/add",
 *     "sort-edit" = "/admin/config/services/entity_share/channel/{channel}/sorts/{sort}/edit",
 *     "sort-delete" = "/admin/config/services/entity_share/channel/{channel}/sorts/{sort}/delete",
 *     "group-add" = "/admin/config/services/entity_share/channel/{channel}/groups/add",
 *     "group-edit" = "/admin/config/services/entity_share/channel/{channel}/groups/{group}/edit",
 *     "group-delete" = "/admin/config/services/entity_share/channel/{channel}/groups/{group}/delete",
 *   }
 * )
 */
class Channel extends ConfigEntityBase implements ChannelInterface {

  /**
   * The channel ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The channel label.
   *
   * @var string
   */
  protected $label;

  /**
   * The channel entity type.
   *
   * @var string
   */
  protected $channel_entity_type;

  /**
   * The channel bundle.
   *
   * @var string
   */
  protected $channel_bundle;

  /**
   * The channel langcode.
   *
   * @var string
   */
  protected $channel_langcode;

  /**
   * The channel filters.
   *
   * @var array
   */
  protected $channel_filters;

  /**
   * The channel groups.
   *
   * @var array
   */
  protected $channel_groups;

  /**
   * The channel sorts.
   *
   * @var array
   */
  protected $channel_sorts;

  /**
   * The UUIDs of the users authorized to see this channel.
   *
   * @var string[]
   */
  protected $authorized_users;

  /**
   * {@inheritdoc}
   */
  public function removeAuthorizedUser($uuid) {
    $authorized_users = $this->authorized_users;
    if (($key = array_search($uuid, $authorized_users)) !== FALSE) {
      unset($authorized_users[$key]);
      $this->set('authorized_users', $authorized_users);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery() {
    $query = [];

    // In case of translatable entities. Add a filter on the langcode to
    // only get entities in the channel language.
    if ($this->channel_langcode != LanguageInterface::LANGCODE_NOT_SPECIFIED) {
      $query['filter']['langcode-filter'] = [
        'condition' => [
          'path' => 'langcode',
          'operator' => '=',
          'value' => $this->channel_langcode,
        ],
      ];
    }

    // Add groups.
    if (!is_null($this->channel_groups)) {
      foreach ($this->channel_groups as $group_id => $group) {
        $query['filter'][$group_id] = [
          'group' => [
            'conjunction' => $group['conjunction'],
          ],
        ];

        if (isset($group['memberof'])) {
          $query['filter'][$group_id]['group']['memberOf'] = $group['memberof'];
        }
      }
    }

    // Add filters.
    if (!is_null($this->channel_filters)) {
      foreach ($this->channel_filters as $filter_id => $filter) {
        $query['filter'][$filter_id] = [
          'condition' => [
            'path' => $filter['path'],
            'operator' => $filter['operator'],
          ],
        ];

        if (isset($filter['value'])) {
          $query['filter'][$filter_id]['condition']['value'] = $filter['value'];
        }

        if (isset($filter['memberof'])) {
          $query['filter'][$filter_id]['condition']['memberOf'] = $filter['memberof'];
        }
      }
    }

    // Add sorts.
    if (!is_null($this->channel_sorts)) {
      $sorts = $this->channel_sorts;

      uasort($sorts, [SortArray::class, 'sortByWeightElement']);

      foreach ($sorts as $sort_id => $sort) {
        $query['sort'][$sort_id] = [
          'path' => $sort['path'],
          'direction' => $sort['direction'],
        ];
      }
    }

    return $query;
  }

}

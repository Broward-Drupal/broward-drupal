<?php

namespace Drupal\field_permissions\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a field permission type plugin.
 *
 * @Annotation
 */
class FieldPermissionType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable title.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

  /**
   * The permission type description.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * The weight for ordering the plugins on the field settings page.
   *
   * @var int
   */
  public $weight;

}

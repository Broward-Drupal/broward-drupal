<?php

/**
 * @file
 * Contains \Drupal\block_styles\Entity\BlockLayout.
 */

namespace Drupal\block_styles\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;


/**
 * Defines the BlockStyle entity.
 *
 * The bootstrap blocks entity stores information about 
 * theme suggestions for block.
 *
 * @ConfigEntityType(
 *   id = "block_styles",
 *   label = @Translation("Block Styles"),
 *   module = "block_styles",
 *   config_prefix = "blocks",
 *   admin_permission = "administer site configuration",
 *   handlers = {
 *     "storage" = "Drupal\block_styles\BlockStyleStorage",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   config_export = {
 *     "id" = "id",
 *     "theme" = "theme",
 *     "text" = "text",
 *     "classes" = "classes",
 *   }
 * )
 */
class BlockStyle extends ConfigEntityBase implements BlockStyleInterface {

  /**
   * The block id.
   *
   * @var string
   */
  protected $id;

  /**
   * The theme name.
   *
   * @var string
   */
  protected $theme;

  /**
   * The button label.
   *
   * @var string
   */
  protected $text;

  /**
   * The theme classes.
   *
   * @var string
   */
  protected $classes;

  /**
   * The block position.
   *
   * @var string
   */
  protected $position;

  /**
   * {@inheritdoc}
   */
  public function getTheme() {
    return $this->theme;
  }

  /**
   * {@inheritdoc}
   */
  public function getText() {
    return $this->text;
  }

  /**
   * {@inheritdoc}
   */
  public function getClasses() {
    return $this->classes;
  }

  /**
   * {@inheritdoc}
   */
  public function getStyle() {
    return array(
      'theme' => $this->theme,
      'classes' => $this->classes,
      'text' => $this->text,
    );
  }
}

<?php

/**
 * @file
 * Contains \Drupal\block_styles\Entity\BlockStyleInterface.
 */

namespace Drupal\block_styles\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for BlockStyles.
 */
interface BlockStyleInterface extends ConfigEntityInterface {

  public function getTheme();

  public function getText();

  public function getClasses();

  public function getStyle();

}

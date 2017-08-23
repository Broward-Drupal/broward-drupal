<?php

namespace Drupal\linkit\Plugin\Linkit\Substitution;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\linkit\SubstitutionInterface;
use Drupal\views\Plugin\views\PluginBase;

/**
 * A substitution plugin for the URL to a file.
 *
 * @Substitution(
 *   id = "file",
 *   label = @Translation("Direct File URL"),
 * )
 */
class File extends PluginBase implements SubstitutionInterface {

  /**
   * {@inheritdoc}
   */
  public function getUrl(EntityInterface $entity) {
    $url = new GeneratedUrl();
    /** @var \Drupal\file\FileInterface $entity */
    $url->setGeneratedUrl(file_create_url($entity->getFileUri()));
    $url->addCacheableDependency($entity);
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(EntityTypeInterface $entity_type) {
    return $entity_type->isSubclassOf('Drupal\file\FileInterface');
  }

}

<?php

namespace Drupal\entity_share_client;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Remote entities.
 */
class RemoteListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Remote');
    $header['url'] = $this->t('URL');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label() . ' (' . $entity->id() . ')';
    $row['url'] = $entity->get('url');
    return $row + parent::buildRow($entity);
  }

}

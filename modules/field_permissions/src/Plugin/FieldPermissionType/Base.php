<?php

namespace Drupal\field_permissions\Plugin\FieldPermissionType;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\field_permissions\Plugin\FieldPermissionTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * An abstract implementation of FieldPermissionTypeInterface.
 */
abstract class Base extends PluginBase implements FieldPermissionTypeInterface, ContainerFactoryPluginInterface {

  /**
   * The field storage.
   *
   * @var FieldStorageConfigInterface
   */
  protected $fieldStorage;

  /**
   * {@inheritdoc}
   *
   * @param FieldStorageConfigInterface $field_storage
   *   The field storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FieldStorageConfigInterface $field_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fieldStorage = $field_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, FieldStorageConfigInterface $field_storage = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $field_storage
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

}

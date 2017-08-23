<?php

namespace Drupal\config_ignore\Plugin\ConfigFilter;

use Drupal\config_filter\Plugin\ConfigFilterBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a ignore filter that reads partly from the active storage.
 *
 * @ConfigFilter(
 *   id = "config_ignore",
 *   label = "Config Ignore",
 *   weight = 0
 * )
 */
class IgnoreFilter extends ConfigFilterBase implements ContainerFactoryPluginInterface {

  const FORCE_EXCLUSION_PREFIX = '~';
  const INCLUDE_SUFFIX = '*';

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $active;

  /**
   * Constructs a new SplitFilter.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\StorageInterface $active
   *   The active configuration store with the configuration on the site.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, StorageInterface $active) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->active = $active;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Get the list of ignored config.
    $ignored = $container->get('config.factory')->get('config_ignore.settings')->get('ignored_config_entities');
    // Allow hooks to alter the list.
    $container->get('module_handler')->invokeAll('config_ignore_settings_alter', [&$ignored]);
    // Set the list in the plugin configuration.
    $configuration['ignored'] = $ignored;

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.storage')
    );
  }

  /**
   * Match a config entity name against the list of ignored config entities.
   *
   * @param string $config_name
   *   The name of the config entity to match against all ignored entities.
   *
   * @return bool
   *   True, if the config entity is to be ignored, false otherwise.
   */
  protected function matchConfigName($config_name) {
    if (Settings::get('config_ignore_deactivate')) {
      // Allow deactivating config_ignore in settings.php. Do not match any name
      // in that case and allow a normal configuration import to happen.
      return FALSE;
    }

    // If the string is an excluded config, don't ignore it.
    if (in_array(static::FORCE_EXCLUSION_PREFIX . $config_name, $this->configuration['ignored'], TRUE)) {
      return FALSE;
    }

    foreach ($this->configuration['ignored'] as $config_ignore_setting) {
      if (fnmatch($config_ignore_setting, $config_name)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function filterRead($name, $data) {
    // Read from the active storage when the name is in the ignored list.
    if ($this->matchConfigName($name)) {
      return $this->active->read($name);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterExists($name, $exists) {
    // A name exists if it is ignored and exists in the active storage.
    return $exists || ($this->matchConfigName($name) && $this->active->exists($name));
  }

  /**
   * {@inheritdoc}
   */
  public function filterReadMultiple(array $names, array $data) {
    // Limit the names which are read from the active storage.
    $names = array_filter($names, [$this, 'matchConfigName']);
    $active_data = $this->active->readMultiple($names);

    // Return the data with merged in active data.
    return array_merge($data, $active_data);
  }

  /**
   * {@inheritdoc}
   */
  public function filterListAll($prefix, array $data) {
    $active_names = $this->active->listAll($prefix);
    // Filter out only ignored config names.
    $active_names = array_filter($active_names, [$this, 'matchConfigName']);

    // Return the data with the active names which are ignored merged in.
    return array_unique(array_merge($data, $active_names));
  }

  /**
   * {@inheritdoc}
   */
  public function filterCreateCollection($collection) {
    return new static($this->configuration, $this->pluginId, $this->pluginDefinition, $this->active->createCollection($collection));
  }

  /**
   * {@inheritdoc}
   */
  public function filterGetAllCollectionNames(array $collections) {
    // Add active collection names as there could be ignored config in them.
    return array_merge($collections, $this->active->getAllCollectionNames());
  }

}
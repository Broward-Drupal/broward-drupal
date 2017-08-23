<?php

namespace Drupal\webform\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for all results exporters.
 */
class WebformPluginExporterController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * A results exporter plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a WebformPluginExporterController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   A results exporter plugin manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PluginManagerInterface $plugin_manager) {
    $this->configFactory = $config_factory;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.webform.exporter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    $excluded_exporters = $this->config('webform.settings')->get('export.excluded_exporters');

    $definitions = $this->pluginManager->getDefinitions();
    $definitions = $this->pluginManager->getSortedDefinitions($definitions);

    $rows = [];
    foreach ($definitions as $plugin_id => $definition) {
      $rows[$plugin_id] = [
        'data' => [
          $plugin_id,
          $definition['label'],
          $definition['description'],
          (isset($excluded_exporters[$plugin_id])) ? $this->t('Yes') : $this->t('No'),
          $definition['provider'],
        ],
      ];
      if (isset($excluded_exporters[$plugin_id])) {
        $rows[$plugin_id]['class'] = ['color-warning'];
      }
    }
    ksort($rows);

    $build = [];

    // Settings
    $build['settings'] = [
      '#type' => 'link',
      '#title' => $this->t('Edit settings'),
      '#url' => Url::fromRoute('webform.settings.exporters'),
      '#attributes' => ['class' => ['button', 'button--small'], 'style' => 'float: right'],
    ];

    // Display info.
    $build['info'] = [
      '#markup' => $this->t('@total exporters', ['@total' => count($rows)]),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    // Exporters.
    $build['webform_exporters'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('ID'),
        $this->t('Label'),
        $this->t('Description'),
        $this->t('Excluded'),
        $this->t('Provided by'),
      ],
      '#rows' => $rows,
      '#sticky' => TRUE,
    ];

    return $build;
  }

}

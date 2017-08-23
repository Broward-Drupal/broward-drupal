<?php

namespace Drupal\svg_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'svg_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "svg_formatter",
 *   label = @Translation("SVG formatter"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class SvgFormatter extends FormatterBase {

  /**
   * The name of the field to which the formatter is associated.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->fieldName = $field_definition->getName();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
      'inline' => FALSE,
      'apply_dimensions' => TRUE,
      'width' => 100,
      'height' => 100,
      'enable_alt' => TRUE,
      'enable_title' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['inline'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Output SVG inline'),
      '#default_value' => $this->getSetting('inline'),
      '#description' => $this->t('Check this option if you want to manipulate the SVG image with CSS and JavaScript.'),
    ];
    $form['apply_dimensions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set image dimensions.'),
      '#default_value' => $this->getSetting('apply_dimensions'),
    ];
    $form['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Image width.'),
      '#default_value' => $this->getSetting('width'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldName . '][settings_edit_form][settings][apply_dimensions]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Image height.'),
      '#default_value' => $this->getSetting('height'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldName . '][settings_edit_form][settings][apply_dimensions]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['enable_alt'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable alt attribute.'),
      '#default_value' => $this->getSetting('enable_alt'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldName . '][settings_edit_form][settings][inline]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['enable_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable title attribute.'),
      '#default_value' => $this->getSetting('enable_title'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldName . '][settings_edit_form][settings][inline]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['notice'] = [
      '#type' => 'markup',
      '#markup' => '<div><small>' . $this->t('Alt and title attributes will be created from an image filename by removing file extension and replacing eventual underscores and dashes with spaces.') . '</small></div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    if ($this->getSetting('inline')) {
      $summary[] = $this->t('Inline SVG');
    }
    if ($this->getSetting('apply_dimensions') && $this->getSetting('width')) {
      $summary[] = $this->t('Image width:') . ' ' . $this->getSetting('width');
    }
    if ($this->getSetting('apply_dimensions') && $this->getSetting('width')) {
      $summary[] = $this->t('Image height:') . ' ' . $this->getSetting('height');
    }
    if ($this->getSetting('enable_alt') && !$this->getSetting('inline')) {
      $summary[] = $this->t('Alt enabled');
    }
    if ($this->getSetting('enable_title') && !$this->getSetting('inline')) {
      $summary[] = $this->t('Title enabled');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $attributes = [];
    if ($this->getSetting('apply_dimensions')) {
      $attributes['width'] = $this->getSetting('width');
      $attributes['height'] = $this->getSetting('height');
    }

    foreach ($items as $delta => $item) {
      if ($item->entity) {
        $filename = $item->entity->getFilename();
        $alt = $this->generateAltAttribute($filename);
        if ($this->getSetting('enable_alt')) {
          $attributes['alt'] = $alt;
        }
        if ($this->getSetting('enable_title')) {
          $attributes['title'] = $alt;
        }
        $uri = $item->entity->getFileUri();

        if ($this->getSetting('inline')) {
          $svg_data = NULL;
          $svg_file = file_exists($uri) ? file_get_contents($uri) : NULL;
          if ($svg_file) {
            $dom = new \DomDocument();
            libxml_use_internal_errors(TRUE);
            $dom->loadXML($svg_file);
            $svg_data = $dom->saveXML();
            if ($this->getSetting('apply_dimensions') && isset($dom->documentElement)) {
              $dom->documentElement->setAttribute('height', $attributes['height']);
              $dom->documentElement->setAttribute('width', $attributes['width']);
              $svg_data = $dom->saveXML();
            }
          }
        }

        $elements[$delta] = [
          '#theme' => 'svg_formatter',
          '#inline' => $this->getSetting('inline') ? TRUE : FALSE,
          '#attributes' => $attributes,
          '#uri' => $this->getSetting('inline') ? NULL : $uri,
          '#svg_data' => $this->getSetting('inline') ? $svg_data : NULL,
        ];
      }
    }

    return $elements;
  }

  /**
   * Generate alt attribute from an image filename.
   */
  private function generateAltAttribute($filename) {
    $alt = str_replace(['.svg', '-', '_'], ['', ' ', ' '], $filename);
    $alt = ucfirst($alt);
    return $alt;
  }

}

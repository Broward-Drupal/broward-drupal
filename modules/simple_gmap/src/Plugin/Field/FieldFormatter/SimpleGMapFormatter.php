<?php

namespace Drupal\simple_gmap\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'simple_gmap' formatter.
 *
 * @FieldFormatter(
 *   id = "simple_gmap",
 *   label = @Translation("Google Map from one-line address"),
 *   field_types = {
 *     "string",
 *     "computed",
 *     "computed_string",
 *   }
 * )
 */
class SimpleGMapFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      "include_map" => "1",
      "include_static_map" => "0",
      "include_link" => "0",
      "include_text" => "0",
      "iframe_height" => "200",
      "iframe_width" => "200",
      "static_scale" => 1,
      "zoom_level" => "14",
      "link_text" => "View larger map",
      "map_type" => "m",
      "langcode" => "en",
      "apikey" => "",
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['embedded_label'] = array(
      '#type' => 'markup',
      '#markup' => '<h3>' . $this->t('Embedded map') . '</h3>',
    );
    $elements['include_map'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Include embedded dynamic map'),
      '#default_value' => $this->getSetting('include_map'),
    );
    $elements['include_static_map'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Include embedded static map'),
      '#default_value' => $this->getSetting('include_static_map'),
    );
    $elements['apikey'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API key'),
      '#default_value' => $this->getSetting('apikey'),
      '#description' => $this->t('Static Maps will not work without an API key. See the <a href="https://developers.google.com/maps/documentation/static-maps" target="_blank">Static Maps API page</a> to learn more and obtain a key.'),
      '#states' => array(
        'visible' => array(
          ':input[name*="include_static_map"]' => ['checked' => TRUE],
        ),
      ),
    );
    $elements['iframe_width'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Width of embedded map'),
      '#default_value' => $this->getSetting('iframe_width'),
      '#description' => $this->t('You can set sizes in px or percent (ex: 600px or 100%). Note that static maps only accept sizes in pixels, without the suffix px (ex: 600).'),
      '#size' => 10,
    );
    $elements['iframe_height'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Height of embedded map'),
      '#default_value' => $this->getSetting('iframe_height'),
      '#description' => $this->t('You can set sizes in px or percent (ex: 600px or 100%). Note that static maps only accept sizes in pixels, without the suffix px (ex: 600).'),
      '#size' => 10,
    );
    $elements['static_scale'] = array(
      '#title' => $this->t('Load Retina sized static image'),
      '#type' => 'select',
      '#description' => $this->t('Choose "Yes" to double the width and height of the static image for use on retina displays. (Only applicable to static map)'),
      '#options' => array(
        1 => $this->t('No'),
        2 => $this->t('Yes'),
      ),
      '#default_value' => (int) $this->getSetting('static_scale'),
    );
    $elements['link_label'] = array(
      '#type' => 'markup',
      '#markup' => '<h3>' . $this->t('Link to map') . '</h3>',
    );
    $elements['include_link'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Include link to map'),
      '#default_value' => $this->getSetting('include_link'),
    );
    $elements['link_text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#default_value' => $this->getSetting('link_text'),
      '#description' => $this->t("Enter the text to use for the link to the map, or enter 'use_address' (without the quotes) to use the entered address text as the link text"),
    );
    $elements['generic_label'] = array(
      '#type' => 'markup',
      '#markup' => '<h3>' . $this->t('General settings') . '</h3>',
    );
    $elements['zoom_level'] = array(
      '#type' => 'select',
      '#options' => array(
        1 => $this->t('1 - Minimum'),
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
        12 => 12,
        13 => 13,
        14 => $this->t('14 - Default'),
        15 => 15,
        16 => 16,
        17 => 17,
        18 => 18,
        19 => 19,
        20 => $this->t('20 - Maximum'),
      ),
      '#title' => $this->t('Zoom level'),
      '#default_value' => $this->getSetting('zoom_level'),
    );
    $elements['include_text'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Include original address text'),
      '#default_value' => $this->getSetting('include_text'),
    );
    $elements['map_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Map type'),
      '#description' => $this->t('Choose a default map type for embedded and linked maps'),
      '#options' => array(
        'm' => $this->t('Map'),
        'k' => $this->t('Satellite'),
        'h' => $this->t('Hybrid'),
        'p' => $this->t('Terrain'),
      ),
      '#default_value' => $this->getSetting('map_type'),
    );
    $elements['langcode'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Language'),
      '#default_value' => $this->getSetting('langcode'),
      '#description' => $this->t("Enter a two-letter language code that Google Maps can recognize, or enter 'page' (without the quotes) to use the current page's language code"),
    );
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $map_types = array(
      'm' => $this->t('Map'),
      'k' => $this->t('Satellite'),
      'h' => $this->t('Hybrid'),
      'p' => $this->t('Terrain'),
    );
    $map_type = $this->getSetting('map_type') ? $this->getSetting('map_type') : 'm';
    $map_type = isset($map_types[$map_type]) ? $map_types[$map_type] : $map_types['m'];

    $include_map = $this->getSetting('include_map');
    if ($include_map) {
      $summary[] = $this->t('Dynamic map: @width x @height', array('@width' => $this->getSetting('iframe_width'), '@height' => $this->getSetting('iframe_height')));
    }
    $include_static_map = $this->getSetting('include_static_map');
    if ($include_static_map) {
      $summary[] = $this->t('Static map: @width x @height, Scale: @static_scale', array('@width' => $this->getSetting('iframe_width'), '@height' => $this->getSetting('iframe_height'), '@static_scale' => $this->getSetting('static_scale')));
    }
    $include_link = $this->getSetting('include_link');
    if ($include_link) {
      $summary[] = $this->t('Map link: @link_text', array('@link_text' => $this->getSetting('link_text')));
    }

    if ($include_link || $include_map || $include_static_map) {
      $summary[] = $this->t('Map Type: @map_type', array('@map_type' => $map_type));
      $summary[] = $this->t('Zoom Level: @zoom_level', array('@zoom_level' => $this->getSetting('zoom_level')));
      $summary[] = $this->t('Language: @language', array('@language' => $this->getSetting('langcode')));
    }
    $include_text = $this->getSetting('include_text');
    if ($include_text) {
      $summary[] = $this->t('Original text displayed');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $element = array();
    $settings = $this->getSettings();

    $embed = (int) $settings['include_map'] ? TRUE : FALSE;
    $static = (int) $settings['include_static_map'] ? TRUE: FALSE;
    $link = (int) $settings['include_link'] ? TRUE : FALSE;
    $text = (int) $settings['include_text'] ? TRUE : FALSE;

    $zoom_level = (int) $settings['zoom_level'];

    // For some reason, static gmaps accepts a different value for map type.
    $static_map_types = array('m' => 'roadmap', 'k' => 'satellite', 'h' => 'hybrid', 'p' => 'terrain');

    $map_type = $settings['map_type'];

    // Figure out a language code to use. Google cannot recognize 'und'.
    if ($settings['langcode'] == 'page') {
      $lang_to_use = $langcode;
    } else {
      $lang_to_use = ['#plain_text' => $settings['langcode']];
    }

    foreach ($items as $delta => $item) {
      $url_value = urlencode($item->value);
      $address_value = $item->value;
      $address = $text ? $address_value : '';
      $text_for_link = ($settings['link_text'] == 'use_address') ? $address_value : $settings['link_text'];
      $link_text = ['#plain_text' => $text_for_link];

      $element[$delta] = array(
        '#theme' => 'simple_gmap_output',
        '#include_map' => $embed,
        '#include_static_map' => $static,
        '#include_link' => $link,
        '#include_text' => $text,
        '#width' => ['#plain_text' => $settings['iframe_width']],
        '#height' => ['#plain_text' => $settings['iframe_height']],
        '#static_scale' => (int) $settings['static_scale'],
        '#url_suffix' => $url_value,
        '#zoom' => $zoom_level,
        '#link_text' => $link_text,
        '#address_text' => $address,
        '#map_type' => $map_type,
        '#langcode' => $lang_to_use,
        '#static_map_type' => $static_map_types[$map_type],
        '#apikey' => $settings['apikey'],
      );
    }
    return $element;
  }
}

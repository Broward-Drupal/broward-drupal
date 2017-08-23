<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'webform_tableselect_sort' element.
 *
 * @WebformElement(
 *   id = "webform_tableselect_sort",
 *   label = @Translation("Tableselect sort"),
 *   description = @Translation("Provides a form element for a table with radios or checkboxes in left column that can be sorted."),
 *   category = @Translation("Options elements"),
 *   states_wrapper = TRUE,
 * )
 */
class WebformTableSelectSort extends OptionsBase {

  use WebformTableTrait;

  /**
   * {@inheritdoc}
   */
  protected $exportDelta = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      'multiple' => TRUE,
      'multiple_error' => '',
      // Table settings.
      'js_select' => TRUE,
      // iCheck settings.
      'icheck' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function supportsMultipleValues() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleValues(array $element) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'ol';
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    return $this->getTableSelectElementSelectorOptions($element, '[checkbox]');
  }

}

<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'select_other' element.
 *
 * @WebformElement(
 *   id = "webform_select_other",
 *   label = @Translation("Select other"),
 *   description = @Translation("Provides a form element for a drop-down menu or scrolling selection box, with the ability to enter a custom value."),
 *   category = @Translation("Options elements"),
 * )
 */
class WebformSelectOther extends Select {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + static::getOtherProperties();
  }

}

<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_markup' element.
 *
 * @WebformElement(
 *   id = "webform_markup",
 *   label = @Translation("Basic HTML"),
 *   description = @Translation("Provides an element to render basic HTML markup."),
 *   category = @Translation("Markup elements"),
 *   states_wrapper = TRUE,
 * )
 */
class WebformMarkup extends WebformMarkupBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      'wrapper_attributes' => [],
      // Markup settings.
      'markup' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildText(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $element['#markup'] = MailFormatHelper::htmlToText($element['#markup']);
    return parent::buildText($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['markup']['markup'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('HTML markup'),
      '#description' => $this->t('Enter custom HTML into your webform.'),
      '#format' => FALSE,
    ];
    return $form;
  }

}

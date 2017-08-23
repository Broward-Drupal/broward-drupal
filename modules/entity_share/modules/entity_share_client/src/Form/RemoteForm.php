<?php

namespace Drupal\entity_share_client\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class RemoteForm.
 *
 * @package Drupal\entity_share_client\Form
 */
class RemoteForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\entity_share_client\Entity\RemoteInterface $remote */
    $remote = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $remote->label(),
      '#description' => $this->t('Label for the remote website.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $remote->id(),
      '#machine_name' => [
        'exists' => '\Drupal\entity_share_client\Entity\Remote::load',
      ],
      '#disabled' => !$remote->isNew(),
    ];

    $form['url'] = [
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#maxlength' => 255,
      '#description' => $this->t('The remote URL. Example: http://example.com'),
      '#default_value' => $remote->get('url'),
      '#required' => TRUE,
    ];

    $form['basic_auth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Basic Auth'),
    ];

    $form['basic_auth']['basic_auth_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $remote->get('basic_auth_username'),
      '#required' => TRUE,
    ];

    $form['basic_auth']['basic_auth_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate URL.
    if (!UrlHelper::isValid($form_state->getValue('url'), TRUE)) {
      $form_state->setError($form['url'], $this->t('Invalid URL.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_share_client\Entity\RemoteInterface $remote */
    $remote = $this->entity;
    $status = $remote->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label remote website.', [
          '%label' => $remote->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label remote website.', [
          '%label' => $remote->label(),
        ]));
    }
    $form_state->setRedirectUrl($remote->toUrl('collection'));
  }

}

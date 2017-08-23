<?php

namespace Drupal\webform\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionExporterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform for webform results export webform.
 */
class WebformResultsExportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_results_export';
  }

  /**
   * The webform submission exporter.
   *
   * @var \Drupal\webform\WebformSubmissionExporterInterface
   */
  protected $submissionExporter;

  /**
   * Constructs a WebformResultsExportForm object.
   *
   * @param \Drupal\webform\WebformSubmissionExporterInterface $webform_submission_exporter
   *   The webform submission exported.
   */
  public function __construct(WebformSubmissionExporterInterface $webform_submission_exporter) {
    $this->submissionExporter = $webform_submission_exporter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform_submission.exporter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Set the merged default (global setting), saved, and user export options
    // into the webform's state.
    $settings_options = $this->config('webform.settings')->get('export');
    $saved_options = $this->submissionExporter->getWebformOptions();
    $user_options = $this->submissionExporter->getValuesFromInput($form_state->getUserInput());
    $export_options = NestedArray::mergeDeep($settings_options, $saved_options, $user_options);

    // Build the webform.
    $this->submissionExporter->buildExportOptionsForm($form, $form_state, $export_options);

    // Build actions.
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download'),
      '#button_type' => 'primary',
    ];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
      '#submit' => ['::save'],
    ];
    $form['actions']['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset settings'),
      '#attributes' => [
        'class' => ['button', 'button--danger'],
      ],
      '#access' => ($saved_options) ? TRUE : FALSE,
      '#submit' => ['::delete'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $export_options = $this->submissionExporter->getValuesFromInput($form_state->getValues());

    // Implode exclude columns.
    $export_options['excluded_columns'] = implode(',', $export_options['excluded_columns']);

    if ($source_entity = $this->submissionExporter->getSourceEntity()) {
      $entity_type = $source_entity->getEntityTypeId();
      $entity_id = $source_entity->id();
      $route_parameters = [$entity_type => $entity_id];
      $route_options = ['query' => $export_options];
      $form_state->setRedirect('entity.' . $entity_type . '.webform.results_export', $route_parameters, $route_options);
    }
    elseif ($webform = $this->submissionExporter->getWebform()) {
      $route_parameters = ['webform' => $webform->id()];
      $route_options = ['query' => $export_options];
      $form_state->setRedirect('entity.webform.results_export', $route_parameters, $route_options);
    }
  }

  /**
   * Webform save configuration handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function save(array &$form, FormStateInterface $form_state) {
    // Save the export options to the webform's state.
    $export_options = $this->submissionExporter->getValuesFromInput($form_state->getValues());
    $this->submissionExporter->setWebformOptions($export_options);
    drupal_set_message($this->t('The download settings have been saved.'));
  }

  /**
   * Webform delete configuration handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function delete(array &$form, FormStateInterface $form_state) {
    $this->submissionExporter->deleteWebformOptions();
    drupal_set_message($this->t('The download settings have been reset.'));
  }

}

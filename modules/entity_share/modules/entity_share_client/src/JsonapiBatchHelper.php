<?php

namespace Drupal\entity_share_client;

use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\entity_share_client\Entity\RemoteInterface;

/**
 * Class JsonapiBatchHelper.
 *
 * Contains static method to use for batch operations.
 *
 * @package Drupal\entity_share_client
 */
class JsonapiBatchHelper {

  /**
   * Batch operation.
   *
   * @param \Drupal\entity_share_client\Entity\RemoteInterface $remote
   *   The selected remote.
   * @param array $data
   *   An array of data from a JSONAPI endpoint.
   * @param array $context
   *   Batch context information.
   */
  public static function importEntityListBatch(RemoteInterface $remote, array $data, array &$context) {
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($data);
    }

    $limit = 10;
    $sub_data = array_slice($data, $context['sandbox']['progress'], $limit);

    /** @var \Drupal\entity_share_client\Service\JsonapiHelperInterface $jsonapi_helper */
    $jsonapi_helper = \Drupal::service('entity_share_client.jsonapi_helper');
    $jsonapi_helper->setRemote($remote);
    $result_ids = $jsonapi_helper->importEntityListData($sub_data);

    $context['results'] = array_merge($context['results'], $result_ids);
    $context['sandbox']['progress'] += count($sub_data);
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Batch finish callback.
   *
   * @param bool $success
   *   A boolean indicating whether the batch has completed successfully.
   * @param array $results
   *   The value set in $context['results'] by callback_batch_operation().
   * @param array $operations
   *   If $success is FALSE, contains the operations that remained unprocessed.
   */
  public static function importEntityListBatchBatchFinished($success, array $results, array $operations) {
    if ($success) {
      $message = new PluralTranslatableMarkup(
        count($results),
        'One entity processed.',
        '@count entities processed.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}

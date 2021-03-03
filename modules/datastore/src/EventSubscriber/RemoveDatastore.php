<?php

namespace Drupal\datastore\EventSubscriber;

use Drupal\metastore\Events\ResourceCleanup;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\common\Storage\JobStoreFactory;
use Drupal\common\Resource;

/**
 * Class MapperCleanup.
 */
class RemoveDatastore implements EventSubscriberInterface {

  /**
   * Inherited.
   *
   * @inheritdoc
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[ResourceCleanup::EVENT_RESOURCE_CLEANUP][] = ['drop'];
    return $events;
  }

  /**
   * React to a distribution being orphaned.
   *
   * @param \Drupal\metastore\Events\ResourceCleanup $event
   *   The event object containing the resource object.
   */
  public function drop(ResourceCleanup $event) {
    /** @var \Drupal\common\Resource $resouce */
    $resource = $event->getResource();
    $ref_uuid = $resource->getUniqueIdentifier();
    $id = md5(str_replace('source', 'local_file', $ref_uuid));
    try {
      /** @var \Drupal\datastore\Service $datastoreService */
      $datastoreService = \Drupal::service('dkan.datastore.service');
      $datastoreService->drop($resource->getIdentifier(), $resource->getVersion());

      \Drupal::logger('datastore')->notice('Dropping datastore for @id', ['@id' => $id]);
    }
    catch (\Exception $e) {
      \Drupal::logger('datastore')->error('Failed to drop datastore for @id. @message',
        [
          '@uuid' => $id,
          '@message' => $e->getMessage(),
        ]);
    }
    try {
      \Drupal::database()->delete('jobstore_dkan_datastore_importer')->condition('ref_uuid', $id)->execute();
      //\Drupal::service('dkan.common.job_store')->getInstance(Import::class)->remove($id);
    }
    catch (\Exception $e) {
      \Drupal::logger('datastore')->error('Failed to remove importer job. @message', ['@message' => $e->getMessage()]);
    }
  }

}

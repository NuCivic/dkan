<?php

namespace Drupal\metastore\EventSubscriber;

use Drupal\common\Events\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\metastore\ResourceMapper;

/**
 * Class RemoveFile.
 */
class RemoveFile implements EventSubscriberInterface {

  /**
   * Inherited.
   *
   * @inheritdoc
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[ResourceMapper::EVENT_RESOURCE_CLEANUP][] = ['fileCleanup'];
    return $events;
  }

  /**
   * React to a distribution being orphaned.
   */
  public function fileCleanup(Event $event) {

    /** @var \Drupal\common\Resource $resource */
    $resource = $event->getData();
    if ($resource->getPerspective() == 'source') {
      $resourceLocalizer = \Drupal::service('dkan.datastore.service.resource_localizer');
      try {
        $resourceLocalizer->remove($resource->getIdentifier(), $resource->getVersion());
      }
      catch (\Exception $e) {
        \Drupal::logger('datastore')->error('Failed file clean up. @message',
          [
            '@message' => $e->getMessage(),
          ]);
      }
    }
  }

}

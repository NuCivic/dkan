<?php

namespace Drupal\common;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\datastore\Service as Datastore;
use Drupal\metastore\ResourceMapper;
use Drupal\metastore\Service as Metastore;
use Drupal\metastore\Storage\DataFactory;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DatasetInfo
 *
 * @package Drupal\common
 */
class DatasetInfo implements ContainerInjectionInterface {

  protected $moduleHandler;

  /**
   * Metastore storage.
   *
   * @var \Drupal\metastore\Storage\Data
   */
  protected $storage;

  /**
   * Metastore.
   *
   * @var Metastore
   */
  protected $metastore;

  /**
   * Datastore.
   *
   * @var Datastore
   */
  protected $datastore;

  /**
   * Resource mapper.
   *
   * @var \Drupal\metastore\ResourceMapper
   */
  protected $resourceMapper;

  /**
   * Set storage.
   *
   * @param \Drupal\metastore\Storage\DataFactory $dataFactory
   *   Metastore's data factory.
   */
  public function setStorage(DataFactory $dataFactory) {
    $this->storage = $dataFactory->getInstance('dataset');
  }

  /**
   * Set metastore.
   *
   * @param \Drupal\metastore\Service $metastore
   *   Metastore service.
   */
  public function setMetastore(Metastore $metastore) {
    $this->metastore = $metastore;
  }

  /**
   * Set datastore.
   *
   * @param \Drupal\datastore\Service $datastore
   *   Datastore service.
   */
  public function setDatastore(Datastore $datastore) {
    $this->datastore = $datastore;
  }

  /**
   * Set the resource mapper.
   *
   * @param \Drupal\metastore\ResourceMapper $resourceMapper
   *   Resource mapper service.
   */
  public function setResourceMapper(ResourceMapper $resourceMapper) {
    $this->resourceMapper = $resourceMapper;
  }

  /**
   * DatasetInfo constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler service.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
    );
  }

  /**
   * Gather info about a dataset from its identifier.
   *
   * @param string $uuid
   *   Dataset identifier.
   *
   * @return array
   *   Dataset information array.
   */
  public function gather(string $uuid) {
    $info['uuid'] = $uuid;

    if (!$this->metastore) {
      $info['notice'] = 'The DKAN Metastore module is not enabled.';
      return $info;
    }

    $latestRevision = $this->storage->getNodeLatestRevision($uuid);
    if (!$latestRevision) {
      $info['notice'] = 'Not found.';
      return $info;
    }
    $info['node id'] = $latestRevision->id();
    $info['latest revision'] = $this->getRevisionInfo($latestRevision);

    $latestRevisionIsDraft = 'draft' === $latestRevision->get('moderation_state')->getString();
    $latestPublished = $this->storage->getNodePublishedRevision($uuid);
    if ($latestRevisionIsDraft && $latestPublished) {
      $info['published revision'] = $this->getRevisionInfo($latestPublished);
    }

    return $info;
  }

  protected function getRevisionInfo(Node $node) : array {
    $revisionInfo = [];

    $revisionInfo['revision id'] = $node->getRevisionId();
    $revisionInfo['moderation state'] = $node->get('moderation_state')->getString();
    $revisionInfo['modified date'] = $node->getChangedTime();
    $revisionInfo['distributions'] = $this->getDistributions($node);

    return $revisionInfo;
  }

  protected function getDistributions(Node $node) {
    $distributions = [];
    foreach ($this->metastore->getResources('dataset', $node->uuid()) as $key => $distribution) {
      print_r($distribution);
      $distributions[$key] = $this->getResources($distribution);
    }
    return $distributions;
  }

  protected function getResources(\stdClass $distribution) {
    $resources = [];
    foreach ($distribution->{'%Ref:downloadURL'} as $key => $resource) {
      $identifier = $resource->data->identifier;
      $version = $resource->data->version;

      $resources[$key]['identifier'] = $identifier;
      $resources[$key]['version'] = $version;
      $resources[$key]['file path'] = $this->resourceMapper->get($identifier, 'local_file', $version)->getFilePath();
      $resources[$key]['table name'] = $this->datastore->getStorage($identifier, $version)->getTableName();
    }
    return $resources;
  }

}

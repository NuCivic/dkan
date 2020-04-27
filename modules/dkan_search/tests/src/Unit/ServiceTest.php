<?php

namespace Drupal\dkan_search;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\dkan_metastore\Service as Metastore;
use Drupal\search\SearchIndexInterface;
use Drupal\search_api\Utility\QueryHelper;
use MockChain\Chain;
use MockChain\Options;
use PHPUnit\Framework\TestCase;

/**
 * Dkan search service tests.
 */
class ServiceTest extends TestCase {

  /**
   * Test for missing search index.
   */
  public function testNoSearchIndex() {
    $container = $this->getCommonMockChain()
      ->add(EntityStorageInterface::class, "load", null);

    $this->expectExceptionMessage("An index named [dkan] does not exist.");
    Service::create($container->getMock());
  }

  /**
   * Test for search().
   */
  public function testSearch() {
    $container = $this->getCommonMockChain();
    $service = Service::create($container->getMock());

    $this->assertEquals(["foo"], $service->search());
  }

  /**
   * Test for searchByIndexField().
   */
  public function testSearchByIndexField() {
    $container = $this->getCommonMockChain();
    $service = Service::create($container->getMock());

    $this->assertEquals(["bar"], $service->searchByIndexField("theme", "finance"));
  }

  /**
   * Common mock chain.
   */
  public function getCommonMockChain() {
    $options = (new Options())
      ->add("dkan_metastore.service", Metastore::class)
      ->add("entity_type.manager", EntityTypeManager::class)
      ->add("search_api.query_helper", QueryHelper::class)
      ->index(0);

    return (new Chain($this))
      ->add(Container::class, "get", $options)
      ->add(EntityTypeManager::class, "getStorage", EntityStorageInterface::class)
      ->add(EntityStorageInterface::class, "load", SearchIndexInterface::class);
  }

}
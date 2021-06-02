<?php

namespace Drupal\Tests\schema;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\metastore\SchemaRetriever;
use Drupal\Tests\common\Traits\ServiceCheckTrait;
use MockChain\Chain;
use PHPUnit\Framework\TestCase;

/**
 * Tests Drupal\schema\SchemaRetriever.
 *
 * @coversDefaultClass \Drupal\metastore\SchemaRetriever
 * @group harvest
 */
class SchemaRetrieverTest extends TestCase {
  use ServiceCheckTrait;

  const APP_ROOT = __DIR__ . '/files';

  /**
   * Protected.
   */
  protected function setUp() {
    // TODO: Change the autogenerated stub.
    parent::setUp();
    if (!defined("DRUPAL_MINIMUM_PHP")) {
      define("DRUPAL_MINIMUM_PHP", "7.0");
    }
  }

  /**
   *
   */
  public function testSchemaDirectory() {
    $retriever = $this->getSchemaRetriever();
    $dir = $retriever->getSchemaDirectory();
    $appRoot = self::APP_ROOT;
    $this->assertEquals($dir, "{$appRoot}/schema");
  }

  /**
   *
   */
  public function testGetAllIds() {
    $retriever = $this->getSchemaRetriever();
    $ids = $retriever->getAllIds();
    $expected = [
      'catalog',
      'dataset',
      'dataset.ui',
      'publisher',
      'publisher.ui',
      'distribution',
      'distribution.ui',
      'theme',
      'theme.ui',
      'keyword',
      'keyword.ui',
      'legacy'
    ];
    $this->assertEquals($expected, $ids);
  }

  /**
   *
   */
  public function testGet() {
    $retriever = $this->getSchemaRetriever();
    $schema = $retriever->retrieve('dataset');
    $json = json_decode($schema);
    $this->assertNotFalse($json);
  }

  /**
   *
   */
  public function testError() {
    $this->expectExceptionMessage("Schema blah not found.");
    $retriever = $this->getSchemaRetriever();
    $retriever->retrieve('blah');
  }

  /**
   *
   */
  public function testNoDirectory() {
    $this->expectExceptionMessage("No schema directory found.");
    $retriever = $this->getSchemaRetriever(TRUE);
    $retriever->retrieve('dataset');
  }

  /**
   * Private.
   */
  private function getSchemaRetriever($badRoot = FALSE) {
    if ($badRoot) {
      $appRoot = "tmp";
    }
    else {
      $appRoot = self::APP_ROOT;
    }

    $options = $this->getContainerOptionsForService('dkan.metastore.schema_retriever');
    $options->add('app.root', $appRoot);

    $chain = (new Chain($this))
      ->add(Container::class, 'get', $options)
      ->add(ModuleExtensionList::class, 'getPathname', "tmp");

    return SchemaRetriever::create($chain->getMock());
  }

}

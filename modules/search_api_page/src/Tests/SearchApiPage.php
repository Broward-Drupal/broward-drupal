<?php

namespace Drupal\search_api_page\Tests;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Tests\ExampleContentTrait;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\simpletest\WebTestBase as SimpletestWebTestBase;

/**
 * Provides web tests for Search API Pages.
 *
 * @group search_api_page
 */
class SearchApiPage extends SimpletestWebTestBase {

  use StringTranslationTrait;
  use ExampleContentTrait;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = ['search_api_page', 'node', 'search_api', 'search_api_db', 'block'];

  /**
   * An admin user used for this test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * A user without any permission..
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $unauthorizedUser;

  /**
   * The anonymous user used for this test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $anonymousUser;

  /**
   * A search database server.
   *
   * @var \Drupal\search_api\Entity\Server
   */
  protected $server = NULL;

  /**
   * A search index.
   *
   * @var \Drupal\search_api\Entity\Index
   */
  protected $index = NULL;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create the users used for the tests.
    $this->adminUser = $this->drupalCreateUser([
      'administer search_api',
      'administer search_api_page',
      'access administration pages',
      'administer nodes',
      'access content overview',
      'administer content types',
      'administer blocks',
      'view search api pages',
    ]);
    $this->unauthorizedUser = $this->drupalCreateUser();
    $this->anonymousUser = $this->drupalCreateUser(['view search api pages']);

    // Create article content type and content.
    $this->drupalCreateContentType(array('type' => 'article'));
    for ($i = 1; $i < 50; $i++) {
      $this->drupalCreateNode(array(
        'title' => 'Item number' . $i,
        'type' => 'article',
        'body' => [['value' => 'Body number' . $i]]));
    }
  }

  /**
   * Test search api pages.
   */
  public function testSearchApiPage() {
    $this->drupalLogin($this->adminUser);

    // Setup search api server and index.
    $this->setupSearchAPI();

    $this->drupalGet('admin/config/search/search-api-pages');
    $this->assertResponse(200);

    $step1 = array(
      'label' => 'Search',
      'id' => 'search',
      'index' => $this->index->id(),
    );
    $this->drupalPostForm('admin/config/search/search-api-pages/add', $step1, 'Next');

    $step2 = array(
      'path' => 'search',
    );
    $this->drupalPostForm(NULL, $step2, 'Save');

    $this->drupalGet('search');
    $this->assertRaw('Enter the terms you wish to search for.');
    $this->assertNoRaw('Your search yielded no results.');
    $this->assertResponse(200);

    $this->drupalLogout();
    $this->drupalLogin($this->unauthorizedUser);
    $this->drupalGet('search');
    $this->assertResponse(403);

    $this->drupalLogout();
    $this->drupalLogin($this->anonymousUser);
    $this->drupalGet('search');
    $this->assertResponse(200);

    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('search/nothing-found');
    $this->assertRaw('Enter the terms you wish to search for.');
    $this->assertRaw('Your search yielded no results.');
    $this->drupalGet('search');
    $this->assertNoRaw('Your search yielded no results.');

    $this->drupalPostForm('admin/config/search/search-api-pages/search', array('show_all_when_no_keys' => TRUE, 'show_search_form' => FALSE), 'Save');
    $this->drupalGet('search');
    $this->assertNoRaw('Your search yielded no results.');
    $this->assertNoRaw('Enter the terms you wish to search for.');
    $this->assertText('49 results found');

    $this->drupalGet('search/number10');
    $this->assertText('1 result found');

    $this->drupalPostForm('admin/config/search/search-api-pages/search', array('show_search_form' => TRUE), 'Save');

    $this->drupalGet('search/number11');
    $this->assertText('1 result found');
    $this->assertRaw('name="keys" value="number11"');

    // Cache should be cleared after the save.
    //$this->drupalGet('search/number10');
    //$this->assertText('1 result found');
    //$this->assertRaw('name="keys" value="number10"');
  }

  /**
   * Private method to setup Search API database and server.
   */
  private function setupSearchAPI() {
    $this->server = Server::create(array(
      'name' => $this->randomString(64),
      'id' => $this->randomMachineName(32),
      'backend' => 'search_api_db',
      'backend_config' =>
        array (
          'database' => 'default:default',
        ),
    ));
    $this->server->save();

    $this->index = Index::create(array(
      'id' => $this->randomMachineName(32),
      'name' => $this->randomString(64),
      'description' => $this->randomString(512),
      'server' => $this->server->id(),
      'datasource_settings' => array(
        'entity:node' => array(
          'plugin_id' => 'entity:node',
          'settings' => array(),
        ),
      ),
      'field_settings' =>
        array (
          'rendered_item' =>
            array (
              'label' => 'Rendered HTML output',
              'property_path' => 'rendered_item',
              'type' => 'text',
              'configuration' =>
                array (
                  'roles' =>
                    array (
                      'anonymous' => 'anonymous',
                    ),
                  'view_mode' =>
                    array (
                      'entity:node' =>
                        array (
                          'article' => 'default',
                          'page' => '',
                        ),
                    ),
                ),
            ),
        ),
    ));
    $this->index->save();

    $task_manager = \Drupal::getContainer()->get('search_api.index_task_manager');
    $task_manager->addItemsAll(Index::load($this->index->id()));
    $this->indexItems($this->index->id());
  }

}

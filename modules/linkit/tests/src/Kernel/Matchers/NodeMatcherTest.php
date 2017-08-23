<?php

namespace Drupal\Tests\linkit\Kernel\Matchers;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\linkit\Kernel\LinkitKernelTestBase;

/**
 * Tests node matcher.
 *
 * @group linkit
 */
class NodeMatcherTest extends LinkitKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field', 'node'];

  /**
   * The matcher manager.
   *
   * @var \Drupal\linkit\MatcherManager
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installConfig(['field', 'node']);

    $this->manager = $this->container->get('plugin.manager.linkit.matcher');

    // Set the current user to a new user, else the nodes will be created by an
    // anonymous user.
    \Drupal::currentUser()->setAccount($this->createUser());

    $type1 = NodeType::create([
      'type' => 'test1',
      'name' => 'Test1',
    ]);
    $type1->save();

    $type2 = NodeType::create([
      'type' => 'test2',
      'name' => 'Test2',
    ]);
    $type2->save();

    // Nodes with type 1.
    $node = Node::create([
      'title' => 'Lorem Ipsum 1',
      'type' => $type1->id(),
    ]);
    $node->save();

    $node = Node::create([
      'title' => 'Lorem Ipsum 2',
      'type' => $type1->id(),
    ]);
    $node->save();

    // Node with type 2.
    $node = Node::create([
      'title' => 'Lorem Ipsum 3',
      'type' => $type2->id(),
    ]);
    $node->save();

    // Unpublished node.
    $node = Node::create([
      'title' => 'Lorem unpublishd',
      'type' => $type1->id(),
      'status' => FALSE,
    ]);
    $node->save();

    // Set the current user to someone that is not the node owner.
    \Drupal::currentUser()->setAccount($this->createUser([], ['access content']));
  }

  /**
   * Tests node matcher.
   */
  public function testNodeMatcherWidthDefaultConfiguration() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:node', []);
    $suggestions = $plugin->execute('Lorem');
    $this->assertEquals(3, count($suggestions->getSuggestions()), 'Correct number of suggestions');
  }

  /**
   * Tests node matcher with bundle filer.
   */
  public function testNodeMatcherWidthBundleFiler() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:node', [
      'settings' => [
        'bundles' => [
          'test1' => 'test1',
        ],
      ],
    ]);

    $suggestions = $plugin->execute('Lorem');
    $this->assertEquals(2, count($suggestions->getSuggestions()), 'Correct number of suggestions');
  }

  /**
   * Tests node matcher with include unpublished setting activated.
   */
  public function testNodeMatcherWidthIncludeUnpublished() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:node', [
      'settings' => [
        'include_unpublished' => TRUE,
      ],
    ]);

    // Test without permissions to see unpublished nodes.
    $suggestions = $plugin->execute('Lorem');
    $this->assertEquals(3, count($suggestions->getSuggestions()), 'Correct number of suggestions');

    // Set the current user to a user with 'bypass node access' permission.
    \Drupal::currentUser()->setAccount($this->createUser([], ['bypass node access']));

    // Test with permissions to see unpublished nodes.
    $suggestions = $plugin->execute('Lorem');
    $this->assertEquals(4, count($suggestions->getSuggestions()), 'Correct number of suggestions');
  }

  /**
   * Tests node matcher with tokens in the matcher metadata.
   */
  public function testNodeMatcherWidthMetadataTokens() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:node', [
      'settings' => [
        'metadata' => '[node:nid] [node:field_with_no_value]',
      ],
    ]);

    $suggestionCollection = $plugin->execute('Lorem');
    /** @var \Drupal\linkit\Suggestion\EntitySuggestion[] $suggestions */
    $suggestions = $suggestionCollection->getSuggestions();

    foreach ($suggestions as $suggestion) {
      $this->assertNotContains('[node:nid]', $suggestion->getDescription(), 'Raw token "[node:nid]" is not present in the description');
      $this->assertNotContains('[node:field_with_no_value]', $suggestion->getDescription(), 'Raw token "[node:field_with_no_value]" is not present in the description');
    }
  }

}

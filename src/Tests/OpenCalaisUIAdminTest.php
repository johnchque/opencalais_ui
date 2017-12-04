<?php

namespace Drupal\opencalais_ui\Tests;

use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the Open Calais UI admin.
 *
 * @group opencalais_ui
 */
class OpenCalaisUIAdminTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'node',
    'block',
    'opencalais_ui',
    'taxonomy'
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create article content type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
  }

  /**
   * Tests Open Calais UI configuration form.
   */
  public function testConfigForm() {
    $admin_user = $this->drupalCreateUser(array(
      'administer site configuration',
      'administer nodes',
      'create article content',
      'edit any article content',
      'administer opencalais'
    ));
    $this->drupalLogin($admin_user);

    // Create a test node.
    $node = Node::create([
      'title' => 'Target node',
      'type' => 'article',
      'body' => 'Target body text',
    ]);
    $node->save();

    // Assert that the API key is not set and the message is displayed.
    $this->drupalGet('admin/config/content/opencalais/general');
    $this->assertFieldByName('api_key', '');
    $this->drupalGet('node/' . $node->id() . '/opencalais_tags');
    $this->assertLink('here');
    $this->assertText('No API key has been set. Click here to set it');

    // Set the API key and check that the message is no longer displayed.
    $edit = [
      'api_key' => 'test_key'
    ];
    $this->drupalPostForm('admin/config/content/opencalais/general', $edit, 'Save configuration');
    $this->drupalGet('node/' . $node->id() . '/opencalais_tags');
    $this->assertNoLink('here');
    $this->assertNoText('No API key has been set. Click here to set it');
  }

}

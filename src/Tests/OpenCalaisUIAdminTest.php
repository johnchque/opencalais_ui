<?php

namespace Drupal\opencalais_ui\Tests;

use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the Open Calais UI admin.
 *
 * @group opencalais_ui
 */
class OpenCalaisUIAdminTest extends WebTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'node',
    'block',
    'opencalais_ui',
    'taxonomy',
    'field_ui'
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create article content type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Tests Open Calais UI configuration form.
   */
  public function testConfigForm() {
    $admin_user = $this->drupalCreateUser(array(
      'administer site configuration',
      'administer nodes',
      'create article content',
      'administer content types',
      'administer node fields',
      'edit any article content',
      'administer opencalais',
      'administer content types'
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
    $this->assertNoText('No Open Calais field has been set. Click here to set it');

    // Set the API key and check that the message is no longer displayed.
    $edit = [
      'api_key' => 'test_key'
    ];
    $this->drupalPostForm('admin/config/content/opencalais/general', $edit, 'Save configuration');
    $this->drupalGet('node/' . $node->id() . '/opencalais_tags');
    $this->assertLink('here');
    $this->assertNoText('No API key has been set. Click here to set it');
    // Assert the message of the missing open calais field.
    $this->assertText('No Open Calais field has been set. Click here to set it');

    // Add a taxonomy field and check if the message is no longer displayed.
    $this->drupalGet('admin/structure/types/manage/article');
    $this->assertText('The content type has no taxonomy fields available. Please add one to use Open Calais.');
    $field_edit = [
      'settings[handler_settings][target_bundles][entities]' => TRUE,
      'settings[handler_settings][target_bundles][industry_tags]' => TRUE,
      'settings[handler_settings][target_bundles][markup_tags]' => TRUE,
      'settings[handler_settings][target_bundles][social_tags]' => TRUE,
      'settings[handler_settings][target_bundles][topic_tags]' => TRUE
    ];
    static::fieldUIAddNewField('admin/structure/types/manage/article', 'taxonomy_test', 'taxonomy_test', 'field_ui:entity_reference:taxonomy_term', [], $field_edit);
    $this->drupalGet('admin/structure/types/manage/article');
    $this->assertNoText('The content type has no taxonomy fields available. Please add one to use Open Calais.');

    // Set the open calais field and check if the message is no longer displayed.
    $this->drupalPostForm('admin/structure/types/manage/article', ['opencalais_field' => 'field_taxonomy_test'], 'Save content type');
    $this->drupalGet('node/' . $node->id() . '/opencalais_tags');
    $this->assertNoText('No API key has been set. Click here to set it');
    $this->assertNoText('No Open Calais field has been set. Click here to set it');
  }

}

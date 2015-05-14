<?php

/**
 * @file
 * Tests for the Lorem Ipsum module.
 */

namespace Drupal\loremipsum\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * @group loremipsum
 */
class LoremIpsumTest extends WebTestBase {

  /**
   * Modules to install
   *
   * @var array
   */
  public static $modules = array('loremipsum');

  // A simple user
  private $user;

  // Perform initial setup tasks that run before every test method.
  // TODO: custom permissions
  public function setUp() {
    parent::setUp();
    $this->user = $this->DrupalCreateUser(array('access content'));
  }

  /**
   * Tests that the settings pages can be reached.
   * TODO: generator test
   * TODO: block test
   */
  public function testCustomPageExists() {
    // Login
    $this->drupalLogin($this->user);
    // Access config page
    $this->drupalGet('admin/config/development/loremipsum');
    $this->assertResponse(200);
    // Test the form elements exist and have defaults
    $config = $this.config('loremipsum.settings');
    $this->assertFieldByName('page_title', $config->get('loremipsum.settings.page_title'), 'Page title field was found with the correct value');
    $this->assertFieldByName('source_text', $config->get('loremipsum.settings.source_text'), 'Source text field was found and not empty');
    // Test form submission

    // Generator test:
    // $this->drupalGet('loremipsum/generate/4/20');
  }
}
<?php

namespace Drupal\Tests\loremipsum\Functional\LoremIpsumTests;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the Lorem Ipsum module.
 * @group loremipsum
 */
class LoremIpsumTests extends BrowserTestBase {

  /**
   * Modules to install
   *
   * @var array
   */
  protected static $modules = array('loremipsum');
  protected $defaultTheme = 'stark';

  // A simple user
  private $user;

  // Perform initial setup tasks that run before every test method.
  public function setUp(): void {
    parent::setUp();
    $this->user = $this->DrupalCreateUser(array(
      'administer site configuration',
      'generate lorem ipsum',
    ));
  }

  /**
   * Tests that the Lorem ipsum page can be reached.
   * TODO: block test
   */
  public function testLoremIpsumPageExists() {
    // Login
    $this->drupalLogin($this->user);

    // Generator test:
    $this->drupalGet('loremipsum/generate/4/20');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the config form.
   */
  public function testConfigForm() {
    // Login
    $this->drupalLogin($this->user);

    // Access config page
    $this->drupalGet('admin/config/development/loremipsum');
    $this->assertSession()->statusCodeEquals(200);
    // Test the form elements exist and have defaults
    $config = \Drupal::config('loremipsum.settings');
    $this->assertSession()->fieldValueEquals(
      'page_title',
      'Lorem ipsum'
    );
    $source_text = 'Lorem ipsum dolor sit amet, consectetur adipisci elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. ' . PHP_EOL;
    $source_text .= 'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. ' . PHP_EOL;
    $source_text .= 'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. ' . PHP_EOL;
    $source_text .= 'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. ';
    $this->assertSession()->fieldValueEquals(
      'source_text',
      $source_text
    );
    // Test form submission
    $this->submitForm(
      [
        'page_title' => 'Test lorem ipsum',
        'source_text' => 'Test phrase 1 \nTest phrase 2 \nTest phrase 3 \n',
      ],
      t('Save configuration'),
    );
    $this->assertSession()->responseContains(
      'The configuration options have been saved.',
      'The form was saved correctly.'
    );
    // Test the new values are there.
    $this->drupalGet('admin/config/development/loremipsum');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals(
      'page_title',
      'Test lorem ipsum'
    );
    $this->assertSession()->fieldValueEquals(
      'source_text',
      'Test phrase 1 \nTest phrase 2 \nTest phrase 3 \n'
    );
  }

}
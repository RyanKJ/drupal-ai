<?php

/**
 * @file
 * Contains Drupal\drupalai\Service\DrupalAIService
 */

namespace Drupal\drupalai\Service;

use Drupal\Component\Utility\Html;

/**
 * Service layer for drupal AI generation.
 */
class DrupalAIService {

  /**
   * Constructs Lorem ipsum text with arguments.
   *
   * @param string $paragraphs
   *   How many paragraphs of Lorem ipsum text.
   * @param string $phrases
   *   Average number of phrases per paragraph.
   */
  public function generate($paragraphs, $phrases) {
    // Default settings
    $config = \Drupal::config('drupalai.settings');
    // Page title and source text.
    $page_title = "Drupal AI";
    $source_text = "This is a test.";
    
    
    $element['#source_text'] = array();
    $element['#source_text'][] = Html::escape($source_text);
    
    $element['#title'] = Html::escape($page_title);

    // Theme function
    $element['#theme'] = 'drupalai';
    
    return $element;    
  }

}

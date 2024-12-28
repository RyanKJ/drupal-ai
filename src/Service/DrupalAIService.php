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
    
    /**
     *
     *
     * // Default settings
     * $config = \Drupal::config('drupalai.settings');
     * // Page title and source text.
     * $page_title = $config->get('drupalai.page_title');
     * $source_text = $config->get('drupalai.source_text');
     *
     * // Repertory strategy.
     * $repertory = explode(PHP_EOL, str_replace(array("\r\n", "\n\r", "\r", "\n"), PHP_EOL, $source_text));
     *
     * $element['#source_text'] = array();
     *
     * // Generate X paragraphs with up to Y phrases each
     * for ($i = 1; $i <= $paragraphs; $i++) {
     *  $this_paragraph = '';
     *  // When we say "up to Y phrases each", we can't mean "from 1 to Y".
     *  // So we go from halfway up.
     *  $random_phrases = mt_rand(round($phrases/2), $phrases);
     *  // Also don't repeat the last phrase.
     *  $last_number = 0;
     *  $next_number = 0;
     *  for ($j = 1; $j <= $random_phrases; $j++) {
     *    do {
     *      $next_number = floor(mt_rand(0, count($repertory)-1));
     *    } while ($next_number === $last_number && count($repertory) > 1);
     *    $this_paragraph .= $repertory[$next_number] . ' ';
     *    $last_number = $next_number;
     *  }
     *  $element['#source_text'][] = Html::escape($this_paragraph);
     * }
     * $element['#title'] = Html::escape($page_title);
     *
     * // Theme function
     * $element['#theme'] = 'drupalai';
     *
     * return $element;
     */
    
    
  }

}

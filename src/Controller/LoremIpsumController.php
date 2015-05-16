<?php

/**
 * @file
 * Contains \Drupal\loremipsum\Controller\LoremIpsumController
 */

namespace Drupal\loremipsum\Controller;

use Drupal\Core\Url;
use Drupal\Component\Utility\String;
// TODO: permissions.
// use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for Lorem ipsum pages.
 */
class LoremIpsumController {

  /**
   * Constructs Lorem ipsum text with arguments.
   * This callback is mapped to the path
   * 'loremipsum/generate/{lorem}/{ipsum}'.
   * 
   * @param string $lorem
   *   First part of your text block.
   * @param string $ipsum
   *   Second part of your text block.
   */
  public function generate($paragraphs, $phrases) {
    // Default settings
    $config = \Drupal::config('loremipsum.settings');
    // Page title and source text.
    $page_title = $config->get('loremipsum.page_title');
    $source_text = $config->get('loremipsum.source_text');

    /* Repertory strategy.
     * TODO: different strategies:
     * - break on periods (like below)
     * - random words
     * - standard lorem ipsum with custom words thrown in
     * - begin with "Lorem Ipsum" etc.
     */
    $repertory = explode(PHP_EOL, $source_text);

    $element['#markup'] = '';

    // Generate X paragraphs with up to Y phrases each
    for ($i = 0; $i < $paragraphs; $i++) {
      $this_paragraph = '';
      // When we say "up to Y phrases each", we can't mean "from 1 to Y".
      // So we go from halfway up.
      $random_phrases = mt_rand(round($phrases/2), $phrases);
      for ($j = 0; $j <= $random_phrases; $j++) {
        $this_paragraph .= $repertory[floor(rand(0, count($repertory)-1))] . ' ';
      }
      $element['#markup'] .= '<p>' . String::checkPlain($this_paragraph) . '</p>';
    }
    $element['#title'] = String::checkPlain($page_title);

    return $element;
  }

}

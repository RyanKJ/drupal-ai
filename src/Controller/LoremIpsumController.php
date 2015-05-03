<?php

/**
 * @file
 * Contains \Drupal\loremipsum\Controller\LoremIpsumController
 */

namespace Drupal\loremipsum\Controller;

use Drupal\Core\Url;
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
    // Source text.
    // TODO: variable source from settings.
    // $source = 'Lorem ipsum dolor sit amet, consectetur adipisci elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';
    $source = \Drupal::config('loremipsum.settings')->get('loremipsum.settings.source_text');

    /* Repertory strategy.
     * TODO: different strategies:
     * - break on periods (like below)
     * - random words
     * - standard lorem ipsum with custom words thrown in
     * - begin with "Lorem Ipsum" etc.
     */
    $repertory = explode(PHP_EOL, $source);

    $element['#markup'] = '';

    // Generate X paragraphs with up to Y phrases each
    for ($i = 0; $i < $paragraphs; $i++) {
      $this_paragraph = '';
      // When we say "up to Y phrases each", we can't mean "from 1 to Y".
      // So we go from halfway up.
      $random_phrases = rand(round($phrases/2), $phrases);
      for ($j = 0; $j <= $random_phrases; $j++) {
        $this_paragraph .= $repertory[floor(rand(0, count($repertory)-1))] . ' ';
      }
      $element['#markup'] .= "<p>$this_paragraph</p>";
    }

    return str_replace('  ', ' ', $element);
  }

}

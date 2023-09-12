<?php

/**
 * @file
 * Contains \Drupal\loremipsum\Controller\LoremIpsumController
 */

namespace Drupal\loremipsum\Controller;

use Drupal\Component\Utility\Html;
use Drupal\loremipsum\Service\LoremIpsumService;

/**
 * Controller routines for Lorem ipsum pages.
 */
class LoremIpsumController {

  /**
   * Constructs Lorem ipsum text with arguments.
   * This callback is mapped to the path
   * 'loremipsum/generate/{lorem}/{ipsum}'.
   *
   * @var \Drupal\loremipsum\Service\LoremIpsumService $LoremIpsumService
   *   A call to the Lorem ipsum service.
   * @param string $paragraphs
   *   How many paragraphs of Lorem ipsum text.
   * @param string $phrases
   *   Average number of phrases per paragraph.
   */

  // The themeable element.
  protected $element = [];

  // The generate method which stores lorem ipsum text in a themeable element.
  public function generate($paragraphs, $phrases) {
    $LoremIpsumService = \Drupal::service('loremipsum.loremipsum_service');
    $element = $LoremIpsumService->generate($paragraphs, $phrases);

    return $element;
  }

}

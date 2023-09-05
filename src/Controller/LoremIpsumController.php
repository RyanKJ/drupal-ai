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
   * @param Drupal\loremipsum\Service\LoremIpsumService $loremipsum_service
   *   How many paragraphs of Lorem ipsum text.
   * @param string $paragraphs
   *   How many paragraphs of Lorem ipsum text.
   * @param string $phrases
   *   Average number of phrases per paragraph.
   */

  /**
   * The themeable element.
   *
   * @var \Drupal\loremipsum\Service\LoremIpsumService $loremipsum_service
   */
  protected $element = [];

  public function generate($paragraphs, $phrases) {
    $LoremIpsumService = \Drupal::service('loremipsum.loremipsum_service');
    $element = $LoremIpsumService->generate($paragraphs, $phrases);

    return $element;
  }

}

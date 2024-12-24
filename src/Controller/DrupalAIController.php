<?php

/**
 * @file
 * Contains \Drupal\drupalai\Controller\DrupalAIController
 */

namespace Drupal\drupalai\Controller;

use Drupal\Component\Utility\Html;
use Drupal\drupalai\Service\DrupalAIService;

/**
 * Controller routines for drupal ai pages.
 */
class DrupalAIController {

  /**
   * Constructs Lorem ipsum text with arguments.
   * This callback is mapped to the path
   * 'drupalai/generate/{lorem}/{ipsum}'.
   *
   * @var \Drupal\drupalai\Service\DrupalAIService $DrupalAIService
   *   A call to the Drupal AI service.
   * @param string $paragraphs
   *   How many paragraphs of Lorem ipsum text.
   * @param string $phrases
   *   Average number of phrases per paragraph.
   */

  // The themeable element.
  protected $element = [];

  // The generate method which stores lorem ipsum text in a themeable element.
  public function generate($paragraphs, $phrases) {
    $DrupalAIService = \Drupal::service('drupalai.drupalai_service');
    $element = $DrupalAIService->generate($paragraphs, $phrases);

    return $element;
  }

}

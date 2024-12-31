<?php

/**
 * @file
 * Contains \Drupal\drupalai\Form\BlockFormController
 */

namespace Drupal\drupalai\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Drupal AI block form
 */
class DrupalAIBlockForm extends FormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drupal_ai_block_form';
  }

  /**
   * {@inheritdoc}
   * Drupal AI generator block.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="ai-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['query'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Your question'),
      '#required' => TRUE,
    ]; 
    
    $form['#response-prefix'] = '<div id="ai-response-wrapper">';
    
    $form['chatgtp-response'] = [
      '#type' => 'markup',
      '#markup' => '<div id="chatgtp-response"></div>',
    ];
    
     $form['claude-response'] = [
      '#type' => 'markup',
      '#markup' => '<div id="claude-response"></div>',
    ];

    $form['gemini-response'] = [
      '#type' => 'markup',
      '#markup' => '<div id="gemini-response"></div>',
    ];
    
    $form['#response-prefix'] = '</div>';
    

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit Your Query'),
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'wrapper' => 'ai-form-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Asking ChatGTP, Claude, and Gemini...'),
        ],
      ],
    ];

    return $form;
  
/**
 *   // How many paragraphs?
 *   for ($i = 1; $i <= 10; $i++) $options[$i] = $i;
 *   $form['paragraphs'] = array(
 *     '#type' => 'select',
 *     '#title' => t('Paragraphs'),
 *     '#options' => $options,
 *     '#default_value' => 4,
 *     '#description' => t('How many?'),
 *   );
 *
 *   // How many phrases?
 *   $form['phrases'] = array(
 *     '#type' => 'textfield',
 *     '#title' => t('Phrases'),
 *     '#default_value' => '20',
 *     '#description' => t('Maximum per paragraph'),
 *   );
 *
 *   // Submit
 *   $form['submit'] = array(
 *     '#type' => 'submit',
 *     '#value' => t('Generate'),
 *   );
 *
 *   return $form;
 */ 
  }
  
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    
    try {
      $query = $form_state->getValue('query');
      $claude_response = "This is a test of AJAX functionality!" . " " . $query;
      
      $response->addCommand(
        new HtmlCommand(
          '#chatgtp-response',
          '<div class="chatgpt-message">' . nl2br($claude_response) . '</div>'
        )
        
        new HtmlCommand(
          '#claude-response',
          '<div class="claude-message">' . nl2br($claude_response) . '</div>'
        )
        
        new HtmlCommand(
          '#gemini-response',
          '<div class="gemini-message">' . nl2br($claude_response) . '</div>'
        )
      );
    }
    catch (\Exception $e) {
      $response->addCommand(
        new HtmlCommand(
          '#claude-response',
          '<div class="claude-error">Error: ' . $e->getMessage() . '</div>'
        )
      );
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $phrases = $form_state->getValue('phrases');
    // The value cannot be empty.
    if (is_null($phrases)) $form_state->setErrorByName('phrases', t('This field cannot be empty.'));
    // The value must be numeric.
    if (!is_numeric($phrases)) {
      $form_state->setErrorByName('phrases', t('Please use a number.'));
    }
    else {
      // A numeric value must still be an integer.
      if (floor($phrases) != $phrases) $form_state->setErrorByName('phrases', t('No decimals, please.'));
      // A numeric value cannot be zero or negative.
      if ($phrases < 1) $form_state->setErrorByName('phrases', t('Please use a number greater than zero.'));
    }
  }

  /**
   * {@inheritdoc}
   * 
   * Redirects users to the results page with the Lorem ipsum text created
   * using the provided parameters.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
 
    //$form_state->setRedirect(
    //  'drupalai.generate',
    //  array(
    //    'paragraphs' => $form_state->getValue('paragraphs'),
    //    'phrases' => $form_state->getValue('phrases'),
    //  )
    //);
  }
}
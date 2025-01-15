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
use Drupal\drupalai\Service\API\OpenAIClient;
use Drupal\drupalai\Service\API\AnthropicClient;
use Drupal\drupalai\Service\API\BardClient;


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
    $form['#attached']['library'][] = 'drupalai/drupalai_styles';
    
    $form['#prefix'] = '<div id="ai-form-wrapper">';
    $form['#suffix'] = '</div>';
    
    //$chatgpt_model_options = ['chatgpt_2341234' => 'ChatGPT Model 1', 'chatgpt_987899' => 'ChatGPT Model 2'];
    $chatgpt_model_options = OpenAIClient::getModelOptions();
    //$claude_model_options = ['claude_haiku_2341' => 'Claude Haiku', 'claude_sonnet_342' => 'Claude Sonnet'];
    $claude_model_options = AnthropicClient::getModelOptions();
    $gemini_model_options = ['gemini_11231' => 'Gemini 1', 'gemini_23421' => 'Gemini 2'];
    //$gemini_mode_options = BardClient::getModelOptions();

    $form['query'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Your question'),
      '#required' => FALSE,
    ]; 
    
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit Your Question'),
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'wrapper' => 'ai-form-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Asking ChatGPT, Claude, and Gemini...'),
        ],
      ],
    ];
    
    // Create a container for all responses
    $form['responses_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['responses-container'],
      ],
    ];

    // ChatGPT response column
    $form['responses_wrapper']['chatgpt'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['response-column'],
      ],
    ];
    $form['responses_wrapper']['chatgpt']['header'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['response-header', 'chatgpt-header'],
      ],
    ];
    $form['responses_wrapper']['chatgpt']['header']['name'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('ChatGPT'),
    ];
    $form['responses_wrapper']['chatgpt']['header']['chatgpt_model_selection'] = [
      '#type' => 'select',
      '#title' => t('Select Version'),
      '#title_display' => 'invisible',
      '#options' => $chatgpt_model_options,
      '#default_value' => reset($chatgpt_model_options),
    ];
    $form['responses_wrapper']['chatgpt']['content'] = [
      '#type' => 'markup',
      '#markup' => '<div id="chatgpt-response" class="response-content"></div>',
    ];
    $form['responses_wrapper']['chatgpt']['meta'] = [
      '#type' => 'markup',
      '#markup' => '<div id="chatgpt-meta" class="response-meta"></div>',
    ];

    // Claude response column
    $form['responses_wrapper']['claude'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['response-column'],
      ],
    ];
    $form['responses_wrapper']['claude']['header'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['response-header', 'claude-header'],
      ],
    ];
    $form['responses_wrapper']['claude']['header']['name'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('Claude'),
    ];
    $form['responses_wrapper']['claude']['header']['claude_model_selection'] = [
      '#type' => 'select',
      '#title' => t('Select Version'),
      '#title_display' => 'invisible',
      '#options' => $claude_model_options,
      '#default_value' => reset($claude_model_options),
    ];
    $form['responses_wrapper']['claude']['content'] = [
      '#type' => 'markup',
      '#markup' => '<div id="claude-response" class="response-content"></div>',
    ];
    $form['responses_wrapper']['claude']['meta'] = [
      '#type' => 'markup',
      '#markup' => '<div id="claude-meta" class="response-meta"></div>',
    ]; 
  
    // Gemini response column
    $form['responses_wrapper']['gemini'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['response-column'],
      ],
    ];
    $form['responses_wrapper']['gemini']['header'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['response-header', 'gemini-header'],
      ],
    ];
    $form['responses_wrapper']['gemini']['header']['name'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('Gemini'),
    ];
    $form['responses_wrapper']['gemini']['header']['gemini_model_selection'] = [
      '#type' => 'select',
      '#title' => t('Select Version'),
      '#title_display' => 'invisible',
      '#options' => $gemini_model_options,
      '#default_value' => reset($gemini_model_options),
    ];
    $form['responses_wrapper']['gemini']['content'] = [
      '#type' => 'markup',
      '#markup' => '<div id="gemini-response" class="response-content"></div>',
    ];
    $form['responses_wrapper']['gemini']['meta'] = [
      '#type' => 'markup',
      '#markup' => '<div id="gemini-meta" class="response-meta"></div>',
    ];    

    return $form;
  
  }
  
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    
    try {
      $query = $form_state->getValue('query');
      $chatgpt_model = $form_state->getValue('chatgpt_model_selection');
      $claude_model = $form_state->getValue('claude_model_selection');
      
      $time = "1.53 Seconds";
      
      // ChatGPT
      //$chatgpt_client = new OpenAIClient($chatgpt_model);
      //$chatgpt_response_and_time = $chatgpt_client->getResponseAndTime($query);
        
      //$chatgpt_response = $chatgpt_response_and_time["response"];
      //$chatgpt_time = $chatgpt_response_and_time["time"];
      
      $chatgpt_response = "Test";
      $chatgpt_time = "1.53 Second Test";
      
      $response->addCommand(
        new HtmlCommand(
          '#chatgpt-response',
          '<div class="chatgpt-message">' . $chatgpt_response . '</div>'
        )
      );
      $response->addCommand(
        new HtmlCommand(
          '#chatgpt-meta',
          '<div class="response-meta">' . $chatgpt_time . '</div>'
        )
      );
      
      // Claude
      $claude_client = new AnthropicClient($claude_model);
      $claude_response_and_time = $claude_client->getResponseAndTime($query);
        
      $claude_response = $claude_response_and_time["response"];
      $claude_time = $claude_response_and_time["time"];
       
      $response->addCommand(
        new HtmlCommand(
          '#claude-response',
          '<div class="claude-message">' . $claude_response . '</div>'
        )
      );
      $response->addCommand(
        new HtmlCommand(
          '#claude-meta',
          '<div class="response-meta">' . $claude_time . '</div>'
        )
      );
      
      // Gemini
      $response->addCommand(  
        new HtmlCommand(
          '#gemini-response',
          '<div class="gemini-message">' . nl2br($claude_response) . '</div>'
        )
      );
      $response->addCommand(
        new HtmlCommand(
          '#gemini-meta',
          '<div class="response-meta">' . $time . '</div>'
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
   * Test dummy expression of ajaxSubmit as to not waste API Tokens.
   */
  public function testAjaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    
    try {
      $query = $form_state->getValue('query');
      $chatgpt_model = $form_state->getValue('chatgpt_model_selection');
      $claude_response = "This is a test of AJAX functionality!" . " " . "ChatGPT Model Selection is: " . $chatgpt_model . $query;
      $time = "1.53 Seconds";
      
      // ChatGPT
      $response->addCommand(
        new HtmlCommand(
          '#chatgpt-response',
          '<div class="chatgpt-message">' . nl2br($claude_response) . '</div>'
        )
      );
      $response->addCommand(
        new HtmlCommand(
          '#chatgpt-meta',
          '<div class="response-meta">' . $time . '</div>'
        )
      );
      
      // Claude
      $response->addCommand(
        new HtmlCommand(
          '#claude-response',
          '<div class="claude-message">' . nl2br($claude_response) . '</div>'
        )
      );
      $response->addCommand(
        new HtmlCommand(
          '#claude-meta',
          '<div class="response-meta">' . $time . '</div>'
        )
      );
      
      // Gemini
      $response->addCommand(  
        new HtmlCommand(
          '#gemini-response',
          '<div class="gemini-message">' . nl2br($claude_response) . '</div>'
        )
      );
      $response->addCommand(
        new HtmlCommand(
          '#gemini-meta',
          '<div class="response-meta">' . $time . '</div>'
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
    // $phrases = $form_state->getValue('phrases');
    // The value cannot be empty.
    // if (is_null($phrases)) $form_state->setErrorByName('phrases', t('This field cannot be empty.'));
    // The value must be numeric.
    // if (!is_numeric($phrases)) {
    //  $form_state->setErrorByName('phrases', t('Please use a number.'));
    //}
    //else {
    //  // A numeric value must still be an integer.
    //  if (floor($phrases) != $phrases) $form_state->setErrorByName('phrases', t('No decimals, please.'));
    //  // A numeric value cannot be zero or negative.
    //  if ($phrases < 1) $form_state->setErrorByName('phrases', t('Please use a number greater than zero.'));
//    }
  }

  /**
   * {@inheritdoc}
   * 
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
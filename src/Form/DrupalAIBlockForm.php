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
    
    // Add instructions for screen readers
    $form['instructions'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('This form allows you to ask a question and compare responses from different AI models.'),
      '#attributes' => [
        'class' => ['form-instructions'],
        'id' => 'ai-form-instructions',
      ],
    ];
    
    $chatgpt_model_options = OpenAIClient::getModelOptions();
    $claude_model_options = AnthropicClient::getModelOptions();
    $gemini_model_options = BardClient::getModelOptions();

    // Make query field more accessible
    $form['query'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Your question'),
      '#required' => FALSE,
      '#description' => $this->t('Enter your question to get responses from ChatGPT, Claude, and Gemini.'),
      '#attributes' => [
        'aria-describedby' => 'ai-form-instructions',
        'aria-required' => 'true',
      ],
    ]; 
    
    // Make submit button more accessible
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit Your Question'),
      '#attributes' => [
        'aria-controls' => 'ai-responses-region',
      ],
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'wrapper' => 'ai-form-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Asking ChatGPT, Claude, and Gemini...'),
        ],
      ],
    ];
    
    // Add status message region for screen readers
    $form['status_message'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['visually-hidden'],
        'aria-live' => 'polite',
        'id' => 'ai-status-message',
      ],
    ];
    
    // Create a container for all responses with proper ARIA landmarks
    $form['responses_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['responses-container'],
        'role' => 'region',
        'aria-label' => $this->t('AI responses'),
        'id' => 'ai-responses-region',
      ],
    ];

    // ChatGPT response column with better semantic structure
    $form['responses_wrapper']['chatgpt'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['response-column'],
        'role' => 'region',
        'aria-labelledby' => 'chatgpt-heading',
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
      '#tag' => 'h2',
      '#value' => $this->t('ChatGPT'),
      '#attributes' => [
        'id' => 'chatgpt-heading',
      ],
    ];
    
    // Improved select element with label
    $form['responses_wrapper']['chatgpt']['header']['model_label'] = [
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => $this->t('Select ChatGPT Version'),
      '#attributes' => [
        'for' => 'edit-chatgpt-model-selection',
        'class' => ['visually-hidden'],
      ],
    ];
    $form['responses_wrapper']['chatgpt']['header']['chatgpt_model_selection'] = [
      '#type' => 'select',
      '#title' => $this->t('Select ChatGPT Version'),
      '#title_display' => 'invisible',
      '#options' => $chatgpt_model_options,
      '#default_value' => reset($chatgpt_model_options),
      '#attributes' => [
        'id' => 'edit-chatgpt-model-selection',
        'aria-labelledby' => 'chatgpt-heading',
      ],
    ];
    
    $form['responses_wrapper']['chatgpt']['content'] = [
      '#type' => 'markup',
      '#markup' => '<div id="chatgpt-response" class="response-content" tabindex="0" aria-live="polite"></div>',
    ];
    $form['responses_wrapper']['chatgpt']['meta'] = [
      '#type' => 'markup',
      '#markup' => '<div id="chatgpt-meta" class="response-meta" aria-live="polite"></div>',
    ];

    // Claude response column with similar accessibility improvements
    $form['responses_wrapper']['claude'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['response-column'],
        'role' => 'region',
        'aria-labelledby' => 'claude-heading',
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
      '#tag' => 'h2',
      '#value' => $this->t('Claude'),
      '#attributes' => [
        'id' => 'claude-heading',
      ],
    ];
    
    $form['responses_wrapper']['claude']['header']['model_label'] = [
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => $this->t('Select Claude Version'),
      '#attributes' => [
        'for' => 'edit-claude-model-selection',
        'class' => ['visually-hidden'],
      ],
    ];
    $form['responses_wrapper']['claude']['header']['claude_model_selection'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Claude Version'),
      '#title_display' => 'invisible',
      '#options' => $claude_model_options,
      '#default_value' => reset($claude_model_options),
      '#attributes' => [
        'id' => 'edit-claude-model-selection',
        'aria-labelledby' => 'claude-heading',
      ],
    ];
    
    $form['responses_wrapper']['claude']['content'] = [
      '#type' => 'markup',
      '#markup' => '<div id="claude-response" class="response-content" tabindex="0" aria-live="off"></div>',
    ];
    $form['responses_wrapper']['claude']['meta'] = [
      '#type' => 'markup',
      '#markup' => '<div id="claude-meta" class="response-meta" aria-live="off"></div>',
    ]; 
  
    // Gemini response column with accessibility improvements
    $form['responses_wrapper']['gemini'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['response-column'],
        'role' => 'region',
        'aria-labelledby' => 'gemini-heading',
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
      '#tag' => 'h2',
      '#value' => $this->t('Gemini'),
      '#attributes' => [
        'id' => 'gemini-heading',
      ],
    ];
    
    $form['responses_wrapper']['gemini']['header']['model_label'] = [
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => $this->t('Select Gemini Version'),
      '#attributes' => [
        'for' => 'edit-gemini-model-selection',
        'class' => ['visually-hidden'],
      ],
    ];
    $form['responses_wrapper']['gemini']['header']['gemini_model_selection'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Gemini Version'),
      '#title_display' => 'invisible',
      '#options' => $gemini_model_options,
      '#default_value' => reset($gemini_model_options),
      '#attributes' => [
        'id' => 'edit-gemini-model-selection',
        'aria-labelledby' => 'gemini-heading',
      ],
    ];
    
    $form['responses_wrapper']['gemini']['content'] = [
      '#type' => 'markup',
      '#markup' => '<div id="gemini-response" class="response-content" tabindex="0" aria-live="off"></div>',
    ];
    $form['responses_wrapper']['gemini']['meta'] = [
      '#type' => 'markup',
      '#markup' => '<div id="gemini-meta" class="response-meta" aria-live="off"></div>',
    ];    

    return $form;
  }
  
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    
    try {
      $query = $form_state->getValue('query');
      $chatgpt_model = $form_state->getValue('chatgpt_model_selection');
      $claude_model = $form_state->getValue('claude_model_selection');
      $gemini_model = $form_state->getValue('gemini_model_selection');
      
      // ChatGPT
      $chatgpt_client = new OpenAIClient($chatgpt_model);
      $chatgpt_response_and_time = $chatgpt_client->getResponseAndTime($query);
        
      $chatgpt_response = $chatgpt_response_and_time["response"];
      $chatgpt_time = $chatgpt_response_and_time["time"];
         
      $response->addCommand(
        new HtmlCommand(
          '#chatgpt-response',
          '<div class="chatgpt-message" aria-live="off">' . nl2br($chatgpt_response) . '</div>'
        )
      );
      $response->addCommand(
        new HtmlCommand(
          '#chatgpt-meta',
          '<div class="response-meta" aria-live="off">' . $chatgpt_time . '</div>'
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
          '<div class="claude-message" aria-live="off">' . nl2br($claude_response) . '</div>'
        )
      );
      $response->addCommand(
        new HtmlCommand(
          '#claude-meta',
          '<div class="response-meta" aria-live="off">' . $claude_time . '</div>'
        )
      );
      
      // Gemini
      $gemini_client = new BardClient($gemini_model);
      $gemini_response_and_time = $gemini_client->getResponseAndTime($query);
        
      $gemini_response = $gemini_response_and_time["response"];
      $gemini_time = $gemini_response_and_time["time"];
      
      $response->addCommand(  
        new HtmlCommand(
          '#gemini-response',
          '<div class="gemini-message" aria-live="off">' . nl2br($gemini_response) . '</div>'
        )
      );
      $response->addCommand(
        new HtmlCommand(
          '#gemini-meta',
          '<div class="response-meta" aria-live="off">' . $gemini_time . '</div>'
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
  }

  /**
   * {@inheritdoc}
   * 
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
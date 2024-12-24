<?php

/**
 * @file
 * Contains \Drupal\loremipsum\Form\DrupalAIForm.
 */

namespace Drupal\drupalai\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class DrupalAIForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'drupalai_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor
    $form = parent::buildForm($form, $form_state);
    // Default settings
    $config = $this->config('drupalai.settings');
    // Page title field
    $form['page_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Lorem ipsum generator page title:'),
      '#default_value' => $config->get('drupalai.page_title'),
      '#description' => t('Give your lorem ipsum generator page a title.'),
    );
    // Source text field
    $form['source_text'] = array(
      '#type' => 'textarea',
      '#title' => t('Source text for lorem ipsum generation:'),
      '#default_value' => $config->get('drupalai.source_text'),
      '#description' => t('Write one sentence per line. Those sentences will be used to generate random text.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('page_title') == NULL) {
      $form_state->setErrorByName('page_title', t('Please enter a valid Page title.'));
    }
    if ($form_state->getValue('source_text') == NULL) {
      $form_state->setErrorByName('source_text', t('Please enter at least one line of text for your generator.'));
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('drupalai.settings');
    $config->set('drupalai.source_text', $form_state->getValue('source_text'));
    $config->set('drupalai.page_title', $form_state->getValue('page_title'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  protected function getEditableConfigNames() {
    return [
      'drupalai.settings',
    ];
  }

}
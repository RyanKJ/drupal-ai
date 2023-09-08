<?php

/**
 * @file
 * Contains \Drupal\loremipsum\Form\BlockFormController
 */

namespace Drupal\loremipsum\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Lorem Ipsum block form
 */
class LoremIpsumBlockForm extends FormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'loremipsum_block_form';
  }

  /**
   * {@inheritdoc}
   * Lorem ipsum generator block.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // How many paragraphs?
    // $options = new array();
    for ($i = 1; $i <= 10; $i++) $options[$i] = $i;
    $form['paragraphs'] = array(
      '#type' => 'select',
      '#title' => t('Paragraphs'),
      '#options' => $options,
      '#default_value' => 4,
      '#description' => t('How many?'),
    );

    // How many phrases?
    $form['phrases'] = array(
      '#type' => 'textfield',
      '#title' => t('Phrases'),
      '#default_value' => '20',
      '#description' => t('Maximum per paragraph'),
    );

    // Submit
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Generate'),
    );

    return $form;
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
    $form_state->setRedirect(
      'loremipsum.generate',
      array(
        'paragraphs' => $form_state->getValue('paragraphs'),
        'phrases' => $form_state->getValue('phrases'),
      )
    );
  }
}
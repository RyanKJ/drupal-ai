<?php

/**
 * @file
 * Contains \Drupal\loremipsum\Form\LoremIpsumForm.
 */

namespace Drupal\loremipsum\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class LoremIpsumForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'loremipsum_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('loremipsum.settings');
    $form['source_text'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Source text for lorem ipsum generation:'),
      '#default_value' => $config->get('loremipsum.settings.source_text'),
      '#description' => $this->t('Write one sentence per line. Those sentences will be used to generate random text.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('loremipsum.settings');
    $config->set('loremipsum.settings.source_text', $form_state->getValue('source_text'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  protected function getEditableConfigNames() {
    return [
      'loremipsum.settings',
    ];
  }

}
<?php

namespace Drupal\drupalai\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

 /**
 * Provides a Drupal AI block with which you can generate dummy text anywhere
 *
 * @Block(
 *   id = "drupalai_block",
 *   admin_label = @Translation("Drupal AI block"),
 *   category = @Translation("Forms")
 * )
 */
class DrupalAIBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Return the form @ Form/DrupalAIBlockForm.php
    return \Drupal::formBuilder()->getForm('Drupal\drupalai\Form\DrupalAIBlockForm');
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'generate lorem ipsum');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    return $form;
  }

  /**
   * 
   * Changed drupal_ai_block_settings to drupalai_block_settings in attempt to fix submission bug.
   *
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue(
      'drupalai_block_settings',
      $form_state->getValue('drupalai_block_settings')
    );
  } 

}

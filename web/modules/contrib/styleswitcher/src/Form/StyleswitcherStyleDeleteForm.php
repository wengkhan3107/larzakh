<?php

namespace Drupal\styleswitcher\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form to delete a single style.
 */
class StyleswitcherStyleDeleteForm extends ConfirmFormBase {

  /**
   * The style to delete.
   *
   * @var array
   */
  protected $style;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'styleswitcher_style_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the style %title?', ['%title' => $this->style['label']]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('styleswitcher.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('The style %title will be permanently deleted.', ['%title' => $this->style['label']])
      . '<br />'
      . $this->t('After this operation users who have chosen this style will see the default one instead.');
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $style
   *   Style to delete. The structure of an array is the same as returned from
   *   styleswitcher_style_load().
   *
   * @return array
   *   The form structure.
   *
   * @see styleswitcher_style_load()
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $style = NULL) {
    $this->style = $style;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name = $this->style['name'];
    $styles = styleswitcher_custom_styles();

    if (isset($styles[$name]['path'])) {
      unset($styles[$name]);
      $this->configFactory()
        ->getEditable('styleswitcher.custom_styles')
        ->set('styles', $styles)
        ->save();

      $this->messenger()->addStatus($this->t('The style %title has been deleted.', ['%title' => $this->style['label']]));
    }
    else {
      $this->messenger()->addWarning($this->t('The blank style cannot be deleted.'));
    }

    $form_state->setRedirect('styleswitcher.admin');
  }

}

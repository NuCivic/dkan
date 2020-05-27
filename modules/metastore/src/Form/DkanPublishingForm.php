<?php

namespace Drupal\metastore\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\metastore\Storage\Data;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DkanPublishingForm.
 */
class DkanPublishingForm extends ConfigFormBase {

  /**
   * Constructs a new DkanPublishingForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'metastore.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dkan_publishing_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('metastore.settings');
    $form['publishing'] = [
      '#type' => 'select',
      '#title' => $this->t('When should dataset updates be published?'),
      '#description' => $this->t('E.g. automatically and immediately, or later and manually.'),
      '#options' => [
        Data::PUBLISH_IMMEDIATELY => $this->t(Data::PUBLISH_IMMEDIATELY),
        Data::PUBLISH_MANUALLY => $this->t(Data::PUBLISH_MANUALLY),
      ],
      '#size' => 1,
      '#default_value' => $config->get('publishing'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('metastore.settings')
      ->set('publishing', $form_state->getValue('publishing'))
      ->save();
  }

}

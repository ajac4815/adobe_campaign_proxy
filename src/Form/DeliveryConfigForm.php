<?php

namespace Drupal\adobe_campaign_proxy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The delivery configuration form.
 */
class DeliveryConfigForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'adobe_campaign_proxy.settings';

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'adobe_campaign_proxy_delivery';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo('node');
    $options = [];
    foreach ($bundles as $key => $value) {
      $options[$key] = $value["label"];
      $form[$key] = [
        '#type' => 'checkbox',
        '#title' => $value["label"],
      ];
      $form["{$key}_workflow_id"] = [
        '#type' => 'textfield',
        '#title' => 'Workflow ID',
        '#description' => $this->t('The ID of the Adobe Campaign workflow that will be used for delivery.'),
        '#states' => [
          'visible' => [
            ":input[name='{$key}']" => ['checked' => TRUE],
          ],
        ],
      ];
      $form["{$key}_signal_id"] = [
        '#type' => 'textfield',
        '#title' => 'External Signal ID',
        '#description' => $this->t('The ID of the Adobe Campaign workflow signal activity to trigger delivery.'),
        '#states' => [
          'visible' => [
            ":input[name='{$key}']" => ['checked' => TRUE],
          ],
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

}

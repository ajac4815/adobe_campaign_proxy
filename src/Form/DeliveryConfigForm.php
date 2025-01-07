<?php

namespace Drupal\adobe_campaign_proxy\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * Entity bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, EntityTypeBundleInfoInterface $bundleInfo) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->bundleInfo = $bundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.bundle.info')
    );
  }

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

    $content_types = $config->get('content_types');
    $bundles = $this->bundleInfo->getBundleInfo('node');
    $options = [];
    foreach ($bundles as $key => $value) {
      $options[$key] = $value["label"];
      $form[$key] = [
        '#default_value' => in_array($key, array_keys($content_types)) ? 1 : 0,
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

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bundles = array_keys($this->bundleInfo->getBundleInfo('node'));
    $values = $form_state->getValues();
    $config = $this->config(static::SETTINGS);
    $content_types = $config->get('content_types');
    foreach ($bundles as $bundle) {
      if (isset($values[$bundle]) && $values[$bundle] === 1) {
        if (!in_array($bundle, $content_types)) {
          $content_types[$bundle] = [
            'workflow_id' => $values["{$bundle}_workflow_id"],
            'signal_id' => $values["{$bundle}_signal_id"],
          ];
          $config->set('content_types', $content_types);
        }
      }
    }
    $config->save();
  }

}

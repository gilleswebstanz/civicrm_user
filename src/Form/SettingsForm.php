<?php

namespace Drupal\civicrm_user\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\civicrm_tools\CiviCrmApiInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Drupal\civicrm_tools\CiviCrmApiInterface definition.
   *
   * @var \Drupal\civicrm_tools\CiviCrmApiInterface
   */
  protected $civicrmToolsApi;

  /**
   * Constructs a new SettingsForm object.
   */
  public function __construct(
      ConfigFactoryInterface $config_factory,
      CiviCrmApiInterface $civicrm_tools_api
    ) {
    parent::__construct($config_factory);
    $this->civicrmToolsApi = $civicrm_tools_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('civicrm_tools.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'civicrm_user.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'civicrm_user_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('civicrm_user.settings');

    $groups = $this->civicrmToolsApi->getAll('Group', []);
    $groupOptions = [];
    foreach ($groups as $gid => $group) {
      $groupOptions[$gid] = $group['title'];
    }

    $tags = $this->civicrmToolsApi->getAll('Tag', []);
    $tagOptions = [];
    foreach ($tags as $tid => $tag) {
      $tagOptions[$tid] = $tag['name'];
    }

    $form['group'] = [
      '#type' => 'select',
      '#title' => $this->t('Group'),
      '#description' => $this->t('Limit Drupal users to the selected groups. All apply if none selected.'),
      '#options' => $groupOptions,
      '#multiple' => TRUE,
      '#size' => 5,
      '#default_value' => $config->get('group'),
    ];
    $form['tag'] = [
      '#type' => 'select',
      '#title' => $this->t('Tag'),
      '#description' => $this->t('Limit Drupal users to the selected tags. All apply if none selected.'),
      '#options' => $tagOptions,
      '#multiple' => TRUE,
      '#size' => 5,
      '#default_value' => $config->get('tag'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('civicrm_user.settings')
      ->set('group', $form_state->getValue('group'))
      ->set('tag', $form_state->getValue('tag'))
      ->save();
  }

}

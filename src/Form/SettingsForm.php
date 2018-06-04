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

    $contactValueOptions = [
      'email' => 'Email',
      'display_name' => 'Display name (e.g. Ms Jane DOE)',
      'first_and_last_name' => 'First and last name (e.g. Jane DOE)',
    ];

    // @todo this value could be fetched from the civicrm.settings.php file
    // @todo group filters in a fieldset
    $form['domain_id'] = [
      '#type' => 'number',
      '#title' => $this->t('Domain id'),
      '#description' => $this->t('CiviCRM domain id. By default 1. Modify if multiple website instances of a frontend are accessing CiviCRM, this is the domain id that can be found in <em>civicrm.setting.php</em>.'),
      '#min' => 1,
      '#step' => 1,
      '#default_value' => empty($config->get('domain_id')) ? 1 : $config->get('domain_id'),
    ];
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

    $form['username'] = [
      '#type' => 'select',
      '#title' => $this->t('Username'),
      '#description' => $this->t('The Drupal username will be set from this CiviCRM contact value.'),
      '#options' => $contactValueOptions,
      '#default_value' => $config->get('username'),
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
      ->set('domain_id', $form_state->getValue('domain_id'))
      ->set('group', $form_state->getValue('group'))
      ->set('tag', $form_state->getValue('tag'))
      ->set('username', $form_state->getValue('username'))
      ->save();
  }

}

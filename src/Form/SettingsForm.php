<?php

namespace Drupal\civicrm_user\Form;

use Drupal\civicrm_user\CiviCrmUserQueueItem;
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
    // @todo provide default values
    $config = $this->config('civicrm_user.settings');
    $this->displayWorkSummary();

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

    $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();
    unset($roles['anonymous']);
    unset($roles['authenticated']);
    $roleOptions = [];
    foreach ($roles as $key => $role) {
      $roleOptions[$key] = $role->label();
    }

    $form['filters'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Filters'),
      '#description' => $this->t('Filters are cumulative (uses the <em>and</em> operator).'),
    ];
    // @todo this value could be fetched from the civicrm.settings.php file
    $form['filters']['domain_id'] = [
      '#type' => 'number',
      '#title' => $this->t('Domain id'),
      '#description' => $this->t('CiviCRM domain id. By default 1. Modify if multiple website instances of a frontend are accessing CiviCRM. The domain id value can be found in <em>civicrm.setting.php</em>.'),
      '#min' => 1,
      '#step' => 1,
      '#default_value' => empty($config->get('domain_id')) ? 1 : $config->get('domain_id'),
    ];
    $form['filters']['group'] = [
      '#type' => 'select',
      '#title' => $this->t('Group'),
      '#description' => $this->t('Limit Drupal users to the selected groups. All apply if none selected.'),
      '#options' => $groupOptions,
      '#multiple' => TRUE,
      '#size' => 5,
      '#default_value' => $config->get('group'),
    ];
    $form['filters']['tag'] = [
      '#type' => 'select',
      '#title' => $this->t('Tag'),
      '#description' => $this->t('Limit Drupal users to the selected tags. All apply if none selected.'),
      '#options' => $tagOptions,
      '#multiple' => TRUE,
      '#size' => 5,
      '#default_value' => $config->get('tag'),
    ];

    $form['user_default'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Drupal user default values'),
    ];
    $form['user_default']['username'] = [
      '#type' => 'select',
      '#title' => $this->t('Username'),
      '#description' => $this->t('The Drupal username will be set from this CiviCRM contact value.'),
      '#options' => $contactValueOptions,
      '#default_value' => $config->get('username'),
    ];
    $form['user_default']['passwd'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Default password. For testing purpose only, when the context does not allow Masquerade or drush uli.'),
      '#default_value' => $config->get('passwd'),
    ];
    $form['user_default']['role'] = [
      '#type' => 'select',
      '#title' => $this->t('Role'),
      '#description' => $this->t('Role(s) to assign to the newly created user.'),
      '#options' => $roleOptions,
      '#multiple' => TRUE,
      '#size' => 5,
      '#default_value' => $config->get('role'),
    ];

    $form['drupal_operations'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Drupal operations'),
    ];
    // @todo this choice should probably not be exposed to all users
    // its sole usage is to update before creating to cover use cases such
    // as existing users that have evolved without keeping a sync with contacts.
    $form['drupal_operations']['operation'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Operation'),
      '#description' => $this->t('Operation(s) to run on Drupal users. Currently "Create" only, "Update" and "Block" needs more tests.'),
      '#options' => [
        CiviCrmUserQueueItem::OPERATION_CREATE => t('Create'),
        // @todo update needs mail notification to user and edge cases debug
        //CiviCrmUserQueueItem::OPERATION_UPDATE => t('Update'),
        // @todo update needs test
        //CiviCrmUserQueueItem::OPERATION_BLOCK => t('Block'),
      ],
      '#default_value' => $config->get('operation'),
    ];
    $form['drupal_operations']['user_readonly'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('User read only'),
      '#description' => $this->t('Block data modification on users within Drupal (register, add, edit user name and email, delete).'),
      '#default_value' => $config->get('user_readonly'),
    ];
    return parent::buildForm($form, $form_state);
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
      ->set('role', $form_state->getValue('role'))
      ->set('passwd', $form_state->getValue('passwd'))
      ->set('operation', $form_state->getValue('operation'))
      ->set('user_readonly', $form_state->getValue('user_readonly'))
      ->save();
  }

  // @todo add validation for at least one of the two filters (group or tag)

  /**
   * Display what needs to be processed by workers based on the configuration.
   */
  private function displayWorkSummary() {
    /** @var \Drupal\civicrm_user\CiviCrmUserMatcherInterface $matcher */
    $matcher = \Drupal::service('civicrm_user.matcher');
    $existingMatches = $matcher->getExistingMatches();
    $candidateMatches = $matcher->getCandidateMatches();

    // Users that are not in the existing matches.
    $usersToCreate = array_diff_key($candidateMatches, $existingMatches);
    // Existing matches that are not candidates for a user account anymore.
    $usersToBlock = array_diff_key($existingMatches, $candidateMatches);
    // Update and unblock all other existing matches.
    $usersToUpdate = array_diff_key($candidateMatches, $usersToBlock);

    $this->messenger()->addWarning($this->t('Users to create: @number_create, to update: @number_update, to block: @number_block.', [
      '@number_create' => count($usersToCreate),
      '@number_update' => count($usersToUpdate),
      '@number_block' => count($usersToBlock),
    ]));
  }

}

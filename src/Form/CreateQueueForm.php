<?php

namespace Drupal\civicrm_user\Form;

use Drupal\civicrm_user\CiviCrmUserQueueCreatorInterface;
use Drupal\civicrm_user\CiviCrmUserQueueItem;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CreateQueueForm.
 */
class CreateQueueForm extends FormBase {

  /**
   * Drupal\civicrm_user\CiviCrmUserQueueCreatorInterface definition.
   *
   * @var \Drupal\civicrm_user\CiviCrmUserQueueCreatorInterface
   */
  protected $queueCreator;

  /**
   * {@inheritdoc}
   */
  public function __construct(CiviCrmUserQueueCreatorInterface $queue_creator) {
    $this->queueCreator = $queue_creator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('civicrm_user.creator')
    );
  }

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'civicrm_user_create_queue';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // @todo add link to the settings form
    // @todo povide a summary of the current configuration
    $form['help'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Submitting this form will add CiviCRM contacts to be processed by the manual queue.'),
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create queue'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo add link to process queue form
    $numberOfItems = $this->queueCreator->addItems(CiviCrmUserQueueItem::QUEUE_TYPE_MANUAL);
    $this->messenger()->addMessage($this->t('Added @number_of_items items to the manual queue.', ['number_of_items' => $numberOfItems]));
  }

}

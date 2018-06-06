<?php

namespace Drupal\civicrm_user\Form;

use Drupal\civicrm_user\CiviCrmUserQueueItem;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\civicrm_user\CiviCrmUserQueueProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ProcessQueueForm.
 */
class ProcessQueueForm extends FormBase {

  /**
   * Drupal\civicrm_user\CiviCrmUserQueueProcessorInterface definition.
   *
   * @var \Drupal\civicrm_user\CiviCrmUserQueueProcessorInterface
   */
  protected $queueProcessor;

  /**
   * {@inheritdoc}
   */
  public function __construct(CiviCrmUserQueueProcessorInterface $queue_processor) {
    $this->queueProcessor = $queue_processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('civicrm_user.processor')
    );
  }

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'civicrm_user_process_queue';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['help'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Submitting this form will process the CiviCRM contacts from the manual queue which contains @number items.',
        [
          '@number' => $this->queueProcessor->getNumberOfItems(CiviCrmUserQueueItem::QUEUE_TYPE_MANUAL),
        ]
      ),
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Process queue'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->queueProcessor->processItems(CiviCrmUserQueueItem::QUEUE_TYPE_MANUAL);
  }

}

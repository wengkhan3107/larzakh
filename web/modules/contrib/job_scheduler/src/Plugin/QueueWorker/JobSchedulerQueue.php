<?php

namespace Drupal\job_scheduler\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Utility\Error;
use Drupal\job_scheduler\Entity\JobSchedule;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Providing worker to reschedule the job or take care of cleanup.
 *
 * Note that as we run the execute() action, the job won't be queued again this
 * time.
 *
 * @QueueWorker(
 *   id = "job_scheduler_queue",
 *   title = @Translation("Job Scheduler Queue"),
 *   cron = {"time" = 60},
 *   deriver = "Drupal\job_scheduler\Plugin\Derivative\JobSchedulerQueueWorker"
 * )
 */
class JobSchedulerQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The name of this scheduler.
   *
   * @var \Drupal\job_scheduler\JobScheduler
   */
  protected $scheduler;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface|null
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $scheduler, $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->scheduler = $scheduler;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('job_scheduler.manager'),
      $container->get('logger.channel.job_scheduler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($id) {
    $job = JobSchedule::load($id);
    $scheduler = $this->scheduler;
    try {
      $scheduler->execute($job);
    }
    catch (\Exception $e) {
      Error::logException($this->logger, $e);
      // Drop jobs that have caused exceptions.
      $job->delete();
    }
  }

}

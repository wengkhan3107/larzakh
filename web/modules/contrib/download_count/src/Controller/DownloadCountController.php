<?php

namespace Drupal\download_count\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for download_count module routes.
 */
class DownloadCountController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Date Formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Queue manager.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * Service that turns a render array into an HTML string.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->configFactory = $container->get('config.factory');
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->queue = $container->get('queue');
    $instance->renderer = $container->get('renderer');
    $instance->state = $container->get('state');
    return $instance;
  }

  /**
   * Builds the fields info overview page.
   *
   * @return array
   *   Array of page elements to render.
   *
   * @throws \Exception
   */
  public function downloadCountReport(): array {
    $build = [];
    $config = $this->configFactory->get('download_count.settings');
    $build['#title'] = $config->get('download_count_view_page_title');

    $total_downloads = 0;
    $item = 1;
    $limit = $config->get('download_count_view_page_limit');
    $items_per_page = $config->get('download_count_view_page_items');
    $page_header = $config->get('download_count_view_page_header');
    $page_footer = $config->get('download_count_view_page_footer');
    $output = '<div id="download-count-page">';

    $header = [
      [
        'data' => '#',
      ],
      [
        'data' => $this->t('Count'),
        'field' => 'count',
        'sort' => 'desc',
      ],
      [
        'data' => $this->t('FID'),
        'field' => 'FID',
      ],
      [
        'data' => $this->t('Entity Type'),
        'field' => 'type',
      ],
      [
        'data' => $this->t('Entity ID'),
        'field' => 'id',
      ],
      [
        'data' => $this->t('File name'),
        'fi eld' => 'filename',
      ],
      [
        'data' => $this->t('File Size'),
        'field' => 'file-size',
      ],
      [
        'data' => $this->t('Total Size'),
        'field' => 'total-size',
      ],
      [
        'data' => $this->t('Last Downloaded'),
        'field' => 'last',
      ],
    ];
    $connection = Database::getConnection();
    $query = $connection->select('download_count', 'dc')
      ->fields('dc', ['type', 'id'])
      ->fields('f', ['filename', 'fid', 'filesize'])
      ->groupBy('dc.type')
      ->groupBy('dc.id')
      ->groupBy('dc.fid')
      ->groupBy('f.filename')
      ->groupBy('f.filesize')
      ->groupBy('f.fid');
    $query->addExpression('COUNT(*)', 'count');
    $query->addExpression('COUNT(*) * f.filesize', 'total-size');
    $query->addExpression('MAX(dc.timestamp)', 'last');
    $query->join('file_managed', 'f', 'dc.fid = f.fid');
    if ($limit > 0) {
      $query->range(0, $limit);
    }
    $query->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header);

    if ($items_per_page > 0) {
      $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')
        ->limit($items_per_page);
    }
    $view_all = '';
    if ($this->currentUser()->hasPermission('view download counts')) {
      $view_all = Link::fromTextAndUrl($this->t('View All'), Url::fromRoute('download_count.details', ['download_count_entry' => 'all']))
        ->toString();
      $header[] = [
        'data' => $view_all,
      ];
    }
    $export_all = '';
    if ($this->currentUser()->hasPermission('export all download count')) {
      $export_all = Link::fromTextAndUrl($this->t('Export All'), Url::fromRoute('download_count.export', ['download_count_entry' => 'all']))
        ->toString();
      $header[] = [
        'data' => $export_all,
      ];

    }
    $reset_all = '';
    if ($this->currentUser()->hasPermission('reset all download count')) {
      $reset_all = Link::fromTextAndUrl($this->t('View All'), Url::fromRoute('download_count.reset', ['download_count_entry' => 'all']))
        ->toString();
      $header[] = [
        'data' => $reset_all,
      ];

    }
    $rows = [];
    $result = $query->execute();
    foreach ($result as $file) {
      $row = [];
      $row[] = $item;
      $row[] = number_format($file->count);
      $row[] = $file->fid;
      $row[] = Html::escape($file->type);
      $row[] = $file->id;
      $row[] = Html::escape($file->filename);
      $row[] = ByteSizeMarkup::create($file->filesize);
      $row[] = ByteSizeMarkup::create($file->count * $file->filesize);
      $row[] = $this->t('@time ago', ['@time' => $this->dateFormatter->formatInterval(\Drupal::time()->getRequestTime() - $file->last)]);

      $query = $connection->select('download_count', 'dc')
        ->fields('dc', ['dcid'])
        ->groupBy('dc.dcid')
        ->condition('id', $file->id)
        ->condition('fid', $file->fid);
      $query->addExpression('MAX(dc.timestamp)', 'last');
      $dcid = $query->execute()->fetchField();
      if ($view_all) {
        $row[] = Link::fromTextAndUrl($this->t('Details'), Url::fromRoute('download_count.details', ['download_count_entry' => $dcid]))
          ->toString();
      }
      if ($export_all) {
        $row[] = Link::fromTextAndUrl($this->t('Export'), Url::fromRoute('download_count.export', ['download_count_entry' => $dcid]))
          ->toString();
      }
      if ($reset_all) {
        $row[] = Link::fromTextAndUrl($this->t('Reset'), Url::fromRoute('download_count.reset', ['download_count_entry' => $dcid]))
          ->toString();
      }
      $rows[] = $row;
      $item++;
      $total_downloads += $file->count;
    }
    $build['#attached'] = [
      'library' => [
        "download_count/global-styling-css",
      ],
    ];
    if (!empty($page_header['value'])) {
      $output .= '<div id="download-count-header">' . Html::escape($page_header['value']) . '</div>';
    }
    $output .= '<div id="download-count-total-top">' . $this->t('Total Downloads:') . ' ' . number_format($total_downloads) . '</div>';
    $table = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['id' => 'download-count-table'],
      '#empty' => $this->t('No files have been downloaded.'),
    ];

    $output .= $this->renderer->render($table);

    $output .= '<div id="download-count-total-bottom">' . $this->t('Total Downloads:') . ' ' . number_format($total_downloads) . '</div>';
    if ($items_per_page > 0) {
      $pager = [
        '#theme' => 'pager',
        '#element' => NULL,
        '#parameters' => [],
        '#route_name' => 'download_count.reports',
        'attributes' => ['tags' => []],
      ];
      $output .= $this->renderer->render($pager);
    }
    if (!empty($page_footer['value'])) {
      $output .= '<div id="download-count-footer">' . Html::escape($page_footer['value']) . '</div>';
    }
    $output .= '</div>';
    $build['#markup'] = $output;
    return $build;
  }

  /**
   * Download_count details page callback.
   *
   * @throws \Exception
   */
  public function downloadCountDetails($download_count_entry = NULL): array {
    $config = $this->configFactory->get('download_count.settings');
    $build = [];
    $build['#attached'] = [
      'library' => [
        "download_count/chart",
        "download_count/global-styling-css",
      ],
    ];
    $last_cron = $this->state->get('download_count_last_cron', 0);

    if ($download_count_entry != NULL) {
      $connection = Database::getConnection();
      $query = $connection->select('download_count', 'dc');
      $query->innerjoin('file_managed', 'f', 'dc.fid = f.fid');
      $query->fields('dc', [
        'dcid',
        'fid',
        'uid',
        'type',
        'id',
        'ip_address',
        'referrer',
        'timestamp',
      ]);
      $query->fields('f', ['filename', 'uri', 'filemime', 'filesize']);
      $query->condition('dc.dcid', $download_count_entry);
      $dc_entry = $query->execute()->fetchObject();
    }
    else {
      $dc_entry = 'all';
    }

    $output = Link::fromTextAndUrl($this->t('&#0171; Back to summary'), Url::fromRoute('download_count.reports'))
      ->toString();
    $connection = Database::getConnection();
    $query = $connection->select('download_count_cache', 'dc');
    $query->addExpression('COUNT(dc.count)', 'count');

    if (!is_object($dc_entry)) {
      $build['#title'] = $this->t('Download Count Details - All Files');
    }
    else {
      $build['#title'] = $this->t('Download Count Details - @filename from @type @id', [
        '@filename' => $dc_entry->filename,
        '@type' => $dc_entry->type,
        '@id' => $dc_entry->id,
      ]);
      $query->condition('dc.type', $dc_entry->type);
      $query->condition('dc.id', $dc_entry->id);
      $query->condition('dc.fid', $dc_entry->fid);
    }

    $result = $query->execute()->fetchField();
    $total = number_format($result);

    if ($last_cron > 0) {
      /** @var \Drupal\Core\Queue\DatabaseQueue $queue */
      $queue = $this->queue->get('download_count');
      $output .= '<p>Current as of ' . $this->dateFormatter->format($last_cron, 'long') . ' with ' . number_format($queue->numberOfItems()) . ' items still queued to cache.</p>';
    }
    else {
      $output .= '<p>No download count data has been cached. You may want to check Drupal cron.</p>';
    }

    $output .= '<div id="download-count-total-top"><strong>' . $this->t('Total Downloads:') . '</strong> ' . $total . '</div>';

    // Determine first day of week (from date module if set, 'Sunday' if not).
    if ($config->get('date_first_day') == 0) {
      $week_format = '%U';
    }
    else {
      $week_format = '%u';
    }

    $sparkline_type = $config->get('download_count_sparklines');
    // Base query for all files for all intervals.
    $query = $connection->select('download_count_cache', 'dc')
      ->groupBy('time_interval');
    $query->addExpression('SUM(dc.count)', 'count');
    $query->orderBy('dc.date', 'DESC');

    // Details for a specific download and entity.
    if ($dc_entry && $dc_entry != 'all') {
      $query->condition('type', $dc_entry->type);
      $query->condition('id', $dc_entry->id);
      $query->condition('fid', $dc_entry->fid);
    }

    // Daily data.
    $query->addExpression("FROM_UNIXTIME(date, '%Y-%m-%d')", 'time_interval');
    $query->range(0, $config->get('download_count_details_daily_limit'));
    $result = $query->execute();
    $daily = $this->downloadCountDetailsTable($result, 'Daily', 'Day');
    $output .= $this->renderer->render($daily['output']);
    $output .= $this->getCharMarkup($daily['values']);

    $expressions = &$query->getExpressions();
    // Weekly data.
    $expressions['time_interval']['expression'] = "FROM_UNIXTIME(date, '$week_format')";
    $query->range(0, $config->get('download_count_details_weekly_limit'));
    $result = $query->execute();
    $weekly = $this->downloadCountDetailsTable($result, 'Weekly', 'Week');
    $output .= $this->renderer->render($weekly['output']);
    $output .= $this->getCharMarkup($weekly['values']);

    // Monthly data.
    $expressions['time_interval']['expression'] = "FROM_UNIXTIME(date, '%Y-%m')";
    $query->range(0, $config->get('download_count_details_monthly_limit'));
    $result = $query->execute();
    $monthly = $this->downloadCountDetailsTable($result, 'Monthly', 'Month');
    $output .= $this->renderer->render($monthly['output']);
    $output .= $this->getCharMarkup($monthly['values']);

    // Yearly data.
    $expressions['time_interval']['expression'] = "FROM_UNIXTIME(date, '%Y')";
    $query->range(0, $config->get('download_count_details_yearly_limit'));
    $result = $query->execute();
    $yearly = $this->downloadCountDetailsTable($result, 'Yearly', 'Year');
    $output .= $this->renderer->render($yearly['output']);
    $output .= $this->getCharMarkup($yearly['values']);
    $output .= '<div id="download-count-total-bottom"><strong>' . $this->t('Total Downloads:') . '</strong> ' . $total . '</div>';

    $build['#markup'] = $output;
    return $build;
  }

  /**
   * Create and output details table.
   */
  public function downloadCountDetailsTable($result, $caption, $range): array {
    $header = [
      [
        'data' => $this->t('#'),
        'class' => 'number',
      ],
      [
        'data' => $this->t('@range', ['@range' => $range]),
        'class' => 'range',
      ],
      [
        'data' => $this->t('Downloads'),
        'class' => 'count',
      ],
    ];
    $count = 1;
    $rows = [];
    $values = [];
    foreach ($result as $download) {
      $row = [];
      $row[] = $count;
      $row[] = $download->time_interval;
      $row[] = number_format($download->count);
      $values[] = $download->count;
      $rows[] = $row;
      $count++;
    }
    $output = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => [
        'id' => 'download-count-' . mb_strtolower($caption),
        'class' => 'download-count-details download-count-table',
      ],
      '#caption' => $caption,
      '#sticky' => FALSE,
    ];

    return ['output' => $output, 'values' => $values];
  }

  /**
   * Generates HTML markup for displaying reversed comma-separated values.
   *
   * @param mixed $values
   *   The array or string of values to reverse and format.
   *
   * @return string
   *   HTML markup for displaying reversed values inside a div element.
   */
  private function getCharMarkup($values): string {
    $values = !empty($values) ? $values : [0];
    $result = implode(',', array_reverse($values));
    $daily_values = "<span class='peity-data'>$result</span>";
    return '<div class="download-count-sparkline-daily">' . $daily_values . '</div>';
  }

}

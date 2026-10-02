<?php

namespace Drupal\xmlfeedviews\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Default style plugin to render an OPML feed.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "xmlfeedviews",
 *   title = @Translation("XML Feed Views"),
 *   help = @Translation("Generates an XML feed from a view."),
 *   theme = "xmlfeedviews",
 *   display_types = {"feed"}
 * )
 */
class XmlFeedViews extends StylePluginBase {

  /**
   * Does the style plugin support grouping of rows.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * The XML namespaces.
   */
  public array $namespaces;

  /**
   * The channel elements.
   */
  public array $channelElements;

  /**
   * Attach to.
   *
   * @param array $build
   *   Build array.
   * @param int|string $display_id
   *   Display ID.
   * @param \Drupal\Core\Url $feed_url
   *   Feed URL.
   * @param string $title
   *   Title.
   */
  public function attachTo(array &$build, $display_id, Url $feed_url, $title) {
    $url_options = [];
    $input = $this->view->getExposedInput();
    if ($input) {
      $url_options['query'] = $input;
    }
    $url_options['absolute'] = TRUE;

    $url = $feed_url->setOptions($url_options)->toString();

    // Add the XML icon to the view.
    $this->view->feedIcons[] = [
      '#theme' => 'feed_icon',
      '#url' => $url,
      '#title' => $title,
    ];

    // Attach a link to the XML feed, which is an alternate representation.
    $build['#attached']['html_head_link'][][] = [
      'rel' => 'alternate',
      'type' => 'text/xml',
      'title' => $title,
      'href' => $url,
    ];
  }

  /**
   * Define Options.
   *
   * @return array
   *   Return options array.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['xmlfeedviews_head'] = ['default' => ''];
    $options['xmlfeedviews_footer'] = ['default' => ''];

    return $options;
  }

  /**
   * Build Options Form.
   *
   * @param array &$form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['xmlfeedviews_head'] = [
      '#type' => 'textarea',
      '#title' => $this->t('XML Head'),
      '#default_value' => $this->options['xmlfeedviews_head'],
      '#description' => $this->t('This is your XML head.'),
    ];
    $form['xmlfeedviews_footer'] = [
      '#type' => 'textarea',
      '#title' => $this->t('XML Footer'),
      '#default_value' => $this->options['xmlfeedviews_footer'],
      '#description' => $this->t('This is your XML footer.'),
    ];
  }

  /**
   * Return an array of additional XHTML elements to add to the channel.
   *
   * @return array
   *   A render array.
   */
  protected function getChannelElements() {
    return [];
  }

  /**
   * Get XML feed views header.
   *
   * @return string
   *   The string containing the description with the tokens replaced.
   */
  public function getHeader() {
    return $this->options['xmlfeedviews_head'];
  }

  /**
   * Get XML Feed Views Footer.
   *
   * @return string
   *   The string containing the description with the tokens replaced.
   */
  public function getFooter() {
    return $this->options['xmlfeedviews_footer'];
  }

  /**
   * Render the template.
   */
  public function render() {
    if (empty($this->view->rowPlugin)) {
      trigger_error('Drupal\xmlfeedviews\Plugin\views\style\XmlFeedViews: Missing row plugin', E_WARNING);
      return [];
    }
    $rows = [];

    // This will be filled in by the row plugin and is used later on in the
    // theming output.
    $this->namespaces = ['xmlns:dc' => 'http://purl.org/dc/elements/1.1/'];

    // Fetch any additional elements for the channel and merge in their
    // namespaces.
    $this->channelElements = $this->getChannelElements();
    foreach ($this->channelElements as $element) {
      if (isset($element['namespace'])) {
        $this->namespaces = array_merge($this->namespaces, $element['namespace']);
      }
    }

    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
      $rows[] = $this->view->rowPlugin->render($row);
    }

    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#rows' => $rows,
    ];
    unset($this->view->row_index);
    return $build;
  }

}

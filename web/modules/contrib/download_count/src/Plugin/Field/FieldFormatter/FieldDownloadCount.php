<?php

namespace Drupal\download_count\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Database;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Link;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\file\Plugin\Field\FieldFormatter\GenericFileFormatter;

/**
 * Field formatter for download count.
 *
 * @FieldFormatter(
 *  id = "FieldDownloadCount",
 *  label = @Translation("Generic file with download count"),
 *  field_types = {"file"}
 * )
 */
class FieldDownloadCount extends GenericFileFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $entity = $items->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $access = \Drupal::currentUser()->hasPermission('view download counts');

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $item = $file->_referringItem;

      if ($access) {
        $download = Database::getConnection()
          ->query('SELECT COUNT(fid) from {download_count} where fid = :fid AND type = :type AND id = :id', [
            ':fid' => $file->id(),
            ':type' => $entity_type,
            ':id' => $entity->id(),
          ])
          ->fetchField();
        $file->download = $download;
      }

      $url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());

      $options = [
        'attributes' => [
          'type' => $file->getMimeType() . '; length=' . $file->getSize(),
        ],
      ];

      if (empty($item->description)) {
        $link_text = $file->getFilename();
      }
      else {
        $link_text = $item->description;
        $options['attributes']['title'] = Html::escape($file->getFilename());
      }

      // Classes to add to the file field for icons.
      $classes = [
        'file',
        // Add a specific class for each and every mime type.
        'file--mime-' . strtr($file->getMimeType(), ['/' => '-', '.' => '-']),
        // Add a more general class for groups of well known mime types.
        'file--' . file_icon_class($file->getMimeType()),
      ];

      $attributes = new Attribute(['class' => $classes]);
      $url = Link::fromTextAndUrl($this->t('@text', ['@text' => $link_text]), Url::fromUri($url, $options))
        ->toString();

      if (isset($file->download) && $file->download > 0) {
        $count = \Drupal::translation()
          ->formatPlural($file->download, 'Downloaded 1 time', 'Downloaded @count times');
      }
      else {
        $count = $this->t('Never downloaded');
      }

      $element[$delta] = [
        '#theme' => !$access ? 'file_link' : 'download_count_file_field_formatter',
        '#file' => $file,
        '#url' => $url,
        '#classes' => $attributes['class'],
        '#count' => $count,
        '#attached' => [
          'library' => [
            'classy/file',
          ],
        ],
        '#cache' => [
          'tags' => $file->getCacheTags(),
        ],
      ];

      // Pass field item attributes to the theme function.
      if (isset($item->_attributes)) {
        $element[$delta] += ['#attributes' => []];
        $element[$delta]['#attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }

    return $element;
  }

}

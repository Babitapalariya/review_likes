<?php

namespace Drupal\node_to_pdf\Controller;

use Symfony\Component\HttpFoundation\Response;
use Drupal\node\Entity\Node;
use Dompdf\Dompdf;
use Dompdf\Options;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;

class PdfDownloadController extends ControllerBase {

  protected RendererInterface $renderer;
  protected EntityDisplayRepositoryInterface $displayRepository;
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  public function __construct(
    RendererInterface $renderer,
    EntityDisplayRepositoryInterface $displayRepository,
    FileUrlGeneratorInterface $fileUrlGenerator
  ) {
    $this->renderer = $renderer;
    $this->displayRepository = $displayRepository;
    $this->fileUrlGenerator = $fileUrlGenerator;
  }

  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('renderer'),
      $container->get('entity_display.repository'),
      $container->get('file_url_generator')
    );
  }

  public function download(Node $node): Response {
    $fields_rendered = [];

    foreach ($node->getFields() as $field_name => $field) {
      // Skip system/internal fields
      if (in_array($field_name, ['nid', 'vid', 'uid', 'type', 'langcode', 'revision_timestamp', 'revision_uid', 'status'])) {
        continue;
      }

      $field_type = $field->getFieldDefinition()->getType();

      // ✅ IMAGE FIELDS (show actual images)
      if ($field_type === 'image') {
        $images_html = '';
        foreach ($field as $image_item) {
          if (!empty($image_item->entity)) {
            $file_url = $this->fileUrlGenerator->generateAbsoluteString($image_item->entity->getFileUri());
            $alt = $image_item->alt ?? '';
            $images_html .= "<img src='$file_url' alt='$alt' style='max-width: 100%; height: auto; margin-bottom: 10px;'><br>";
          }
        }
        $fields_rendered[$field_name] = $images_html;
      }

      // ✅ ENTITY REFERENCES (media, taxonomy, paragraphs)
      elseif ($field_type === 'entity_reference') {
        $items = [];
        foreach ($field as $item) {
          if (!empty($item->entity)) {
            $entity = $item->entity;
            $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId());

            // Use 'default' or fallback to 'full' view mode
            $view_mode = 'default';
            $view_modes = $this->displayRepository->getViewModes($entity->getEntityTypeId());
            if (!isset($view_modes[$view_mode])) {
              $view_mode = 'full';
            }

            $render_array = $view_builder->view($entity, $view_mode);
            $items[] = $this->renderer->renderRoot($render_array);
          }
        }
        $fields_rendered[$field_name] = implode('<hr>', $items);
      }

      // ✅ REGULAR FIELDS (text, links, etc.)
      else {
        $render_array = $field->view('default');
        $fields_rendered[$field_name] = $this->renderer->renderRoot($render_array);
      }
    }

    // ✅ Build render array
    $build = [
      '#theme' => 'node_to_pdf_template',
      '#title' => $node->label(),
      '#fields' => $fields_rendered,
    ];

    // ✅ Render HTML for Dompdf
    $html = $this->renderer->renderRoot($build);

    // ✅ Setup Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true); // Allow image URLs
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return new Response($dompdf->output(), 200, [
      'Content-Type' => 'application/pdf',
      'Content-Disposition' => 'attachment; filename="' . $node->label() . '.pdf"',
    ]);
  }
}

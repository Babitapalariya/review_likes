<?php

namespace Drupal\role_based_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\file\Entity\File;
use Drupal\Core\Url;



class ProductController extends ControllerBase {

  protected $fileSystem;

  public function __construct(FileSystemInterface $fileSystem) {
    $this->fileSystem = $fileSystem;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system')
    );
  }

  /**
   * Extract product and save node.
   */
  public function extractProduct(Request $request) {
    $url = $request->query->get('url');

    if (empty($url)) {
      $this->messenger()->addError($this->t('No product URL provided.'));
      return $this->redirect('<front>');
      \Drupal::logger('debug')->notice('<pre>@data</pre>', ['@data' => print_r($form_state->getValues(), TRUE)]);

    }

    try {
      $node = $this->extractProductData($url);

      $this->messenger()->addStatus($this->t('Product "%title" imported successfully!', [
        '%title' => $node->label(),
      ]));
 // Redirect to product form route
  $redirectUrl = Url::fromRoute('role_based_registration.product_form')->toString();
  return new RedirectResponse($redirectUrl);
    //   return new RedirectResponse($node->toUrl()->toString());
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Extraction failed: @msg', ['@msg' => $e->getMessage()]));
      return $this->redirect('<front>');
    }
  }


private function extractProductData($url) {
  // Extract external_id
  preg_match('/\/dp\/([A-Z0-9]{10})/', $url, $matches);
  $external_id = $matches[1] ?? md5($url); // fallback for Flipkart

  if (empty($external_id)) {
    throw new \Exception('Could not extract product ID from the URL.');
  }

  // Check duplicate
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
    'type' => 'marchant_products',
    'field_external_id' => $external_id,
  ]);
  if (!empty($nodes)) {
    throw new \Exception('This product already exists in the system.');
  }

  // Validate domain
  if (!preg_match('/amazon\.in|flipkart\.com/', $url)) {
    throw new \Exception('Currently, only Amazon.in and Flipkart.com are supported.');
  }

  // Fetch page
  $client = \Drupal::httpClient();
  $response = $client->request('GET', $url, [
    'headers' => ['User-Agent' => 'Mozilla/5.0'],
    'headers' => [
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
    'Accept-Language' => 'en-US,en;q=0.9',
    'Referer' => 'https://www.flipkart.com/',
    'Cookie' => 'SESSbce6192f9c85846f7f11b345aa5abc44=tBxrUCHeBpvlntZtgrEkXa2MKNvEL-2i5SlLmACPwNyYGKEe',
  ],
  'allow_redirects' => TRUE,
  'timeout' => 30,
// ]);
  ]);
  $html = (string) $response->getBody();

  $dom = new \DOMDocument();
  @$dom->loadHTML($html);
  $xpath = new \DOMXPath($dom);

  $title = '';
  $price = 0;
  $description = '';
  $soldBy = '';
  $fid = NULL;

  if (strpos($url, 'amazon.in') !== FALSE) {
    // === Amazon selectors ===
    $titleNode = $xpath->query("//span[@id='productTitle']");
    $title = $titleNode->length ? trim($titleNode->item(0)->nodeValue) : 'Untitled Product';

    $priceNode = $xpath->query("//span[@class='a-price-whole']");
    $price = $priceNode->length ? preg_replace('/[^0-9.]/', '', $priceNode->item(0)->nodeValue) : 0;

    $descNode = $xpath->query("//div[@id='feature-bullets']//span[@class='a-list-item']");
    foreach ($descNode as $item) {
      $description .= trim($item->nodeValue) . "\n";
    }

    $sellerNode = $xpath->query("//a[@id='bylineInfo']");
    $soldBy = $sellerNode->length ? trim($sellerNode->item(0)->nodeValue) : 'Unknown Seller';

    $imageNode = $xpath->query("//img[@id='landingImage']");
    $imageUrl = $imageNode->length ? $imageNode->item(0)->getAttribute('src') : '';
  }
  else {
    // === Flipkart selectors ===
    $titleNode = $xpath->query("//span[@class='B_NuCI']");
    $title = $titleNode->length ? trim($titleNode->item(0)->nodeValue) : 'Untitled Product';

    $priceNode = $xpath->query("//div[@class='_30jeq3 _16Jk6d']");
    $price = $priceNode->length ? preg_replace('/[^0-9.]/', '', $priceNode->item(0)->nodeValue) : 0;

    $descNode = $xpath->query("//div[@class='_1mXcCf RmoJUa']//p");
    foreach ($descNode as $item) {
      $description .= trim($item->nodeValue) . "\n";
    }

    $sellerNode = $xpath->query("//div[@id='sellerName']//span//span");
    $soldBy = $sellerNode->length ? trim($sellerNode->item(0)->nodeValue) : 'Unknown Seller';

    $imageNode = $xpath->query("//img[@class='_396cs4 _2amPTt _3qGmMb  _3exPp9']");
    if ($imageNode->length === 0) {
      $imageNode = $xpath->query("//img[@class='_396cs4 _2amPTt _3qGmMb']");
    }
    $imageUrl = $imageNode->length ? $imageNode->item(0)->getAttribute('src') : '';
  }

  // Save image
  if (!empty($imageUrl)) {
    $imageData = file_get_contents($imageUrl);
    if ($imageData) {
      $fileRepository = \Drupal::service('file.repository');
      $file = $fileRepository->writeData(
        $imageData,
        'public://product-images/' . basename(parse_url($imageUrl, PHP_URL_PATH)),
        FileSystemInterface::EXISTS_REPLACE
      );
      if ($file) {
        $fid = $file->id();
      }
    }
  }

  // Create node
  $node = Node::create([
    'type'  => 'marchant_products',
    'title' => $title,
    'field_product_url' => $url,
    'field_price' => $price,
    'body'  => $description,
    'field_external_id' => $external_id,
    'field_sold_bys' => $soldBy,
    'field_image' => $fid ? ['target_id' => $fid] : NULL,
    'status' => 1,
  ]);
  $node->save();

  return $node;
}


//   /**
//    * Handle scraping & node creation.
//    */
//   private function extractProductData($url) {


//     // Extract ASIN (Unique Amazon ID) from the URL.
// preg_match('/\/dp\/([A-Z0-9]{10})/', $url, $matches);
// $external_id = $matches[1] ?? '';

// if (empty($external_id)) {
//   throw new \Exception('Could not extract product ID from the URL.');
// }

// // Check if product already exists to avoid duplicates.
// $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
//   'type' => 'marchant_products',
//   'field_external_id' => $external_id,
// ]);

// if (!empty($nodes)) {
//   // Product already exists, return error.
//   throw new \Exception('This product already exists in the system.');
// }
//     // Example: only Amazon/Flipkart allowed
//     if (!preg_match('/amazon\.in|flipkart\.com/', $url)) {
//       throw new \Exception('Currently, only Amazon.in and Flipkart.com are supported.');
//     }

//     $client = \Drupal::httpClient();
//     $response = $client->request('GET', $url, [
//       'headers' => [
//         'User-Agent' => 'Mozilla/5.0',
//       ],
//     ]);
//     $html = (string) $response->getBody();

//     // === Parse HTML with DOM ===
//     $dom = new \DOMDocument();
//     @$dom->loadHTML($html);
//     $xpath = new \DOMXPath($dom);

//     // Title
//     $titleNode = $xpath->query("//span[@id='productTitle']");
//     $title = $titleNode->length ? trim($titleNode->item(0)->nodeValue) : 'Untitled Product';

//     // Price
//     $priceNode = $xpath->query("//span[@class='a-price-whole']");
//     $price = $priceNode->length ? preg_replace('/[^0-9.]/', '', $priceNode->item(0)->nodeValue) : 0;

//     // Description
//     $descNode = $xpath->query("//div[@id='feature-bullets']//span[@class='a-list-item']");
//     $description = '';
//     foreach ($descNode as $item) {
//       $description .= trim($item->nodeValue) . "\n";
//     }

// // Sold By
// $sellerNode = $xpath->query("//a[@id='bylineInfo']");
// $soldBy = $sellerNode->length ? trim($sellerNode->item(0)->nodeValue) : '';

// if (empty($soldBy)) {
//   $sellerNode = $xpath->query("//a[@id='sellerProfileTriggerId']");
//   $soldBy = $sellerNode->length ? trim($sellerNode->item(0)->nodeValue) : 'Unknown Seller';
// }
// // Image
// $imageNode = $xpath->query("//img[@id='landingImage']");
// $imageUrl = $imageNode->length ? $imageNode->item(0)->getAttribute('src') : '';

// if (!empty($imageUrl)) {
//   $imageData = file_get_contents($imageUrl);

//   if ($imageData) {
//     $fileRepository = \Drupal::service('file.repository');
//     $file = $fileRepository->writeData(
//       $imageData,
//       'public://product-images/' . basename(parse_url($imageUrl, PHP_URL_PATH)),
//       FileSystemInterface::EXISTS_REPLACE
//     );

//     if ($file) {
//       $fid = $file->id();
//     }
//   }
// }



//     // $url = $form_state->getValue('elink');
// if (!empty($url)) {
//     // === Create node ===
//     $node = Node::create([
//       'type'  => 'marchant_products',
//       'title' => $title,
//       'field_product_url' =>  $url,
//       'field_price' => $price,
//       'body'  => $description,
//       'field_external_id' => $external_id,
//       'field_sold_bys' => $soldBy,       // add a text field in your content type
//         'field_image' => isset($fid) ? [
//             'target_id' => $fid,
//         ] : NULL,
//       'status' => 1,
//     ]);
// }

//     $node->save();
//     return $node;
//   }
}

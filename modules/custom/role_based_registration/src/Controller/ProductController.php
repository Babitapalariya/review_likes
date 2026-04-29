<?php

namespace Drupal\role_based_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
 /* public function extractProduct(Request $request) {
    $url = $request->query->get('url');

    if (empty($url)) {
      $this->messenger()->addError($this->t('No product URL provided.'));
      return $this->redirect('<front>');
    }

    try {
      $node = $this->extractProductData($url);

      $this->messenger()->addStatus($this->t('Product "%title" imported successfully!', [
        '%title' => $node->label(),
      ]));

      return new RedirectResponse(
        Url::fromRoute('role_based_registration.product_form')->toString()
      );
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Extraction failed: @msg', [
        '@msg' => $e->getMessage(),
      ]));
      return $this->redirect('<front>');
    }
  }*/




  /**
   * Extract and create node.
   */
  /*private function extractProductData($url) {

    // Extract external ID
    preg_match('/\/dp\/([A-Z0-9]{10})/', $url, $matches);
    $external_id = $matches[1] ?? md5($url);

    if (empty($external_id)) {
      throw new \Exception('Could not extract product ID.');
    }

    // Prevent duplicate
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
      'type' => 'marchant_products',
      'field_external_id' => $external_id,
    ]);

    if (!empty($nodes)) {
      throw new \Exception('This product already exists.');
    }

    // Validate domain
    if (!preg_match('/amazon\.in|flipkart\.com/', $url)) {
      throw new \Exception('Only Amazon & Flipkart supported.');
    }

    // Fetch HTML
    $client = \Drupal::httpClient();
    $response = $client->request('GET', $url, [
      'headers' => [
        'User-Agent' => 'Mozilla/5.0',
        'Accept-Language' => 'en-US,en;q=0.9',
      ],
      'timeout' => 30,
    ]);

    $html = (string) $response->getBody();

    $dom = new \DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new \DOMXPath($dom);

    // Initialize
    $title = '';
    $price = 0;
    $description = '';
    $soldBy = '';
    $imageUrl = '';
    $fid = NULL;
    $rating = '';

    // ================= AMAZON =================
    if (strpos($url, 'amazon.in') !== FALSE) {

      // Title
      $titleNode = $xpath->query("//span[@id='productTitle']");
      $title = $titleNode->length ? trim($titleNode->item(0)->nodeValue) : 'Untitled';

      // Price
      $priceNode = $xpath->query("//span[@class='a-price-whole']");
      $price = $priceNode->length ? preg_replace('/[^0-9.]/', '', $priceNode->item(0)->nodeValue) : 0;

      // Description
      $descNode = $xpath->query("//div[@id='feature-bullets']//span[@class='a-list-item']");
      foreach ($descNode as $item) {
        $description .= trim($item->nodeValue) . "\n";
      }

      // Seller
      $sellerNode = $xpath->query("//a[@id='bylineInfo']");
      $soldBy = $sellerNode->length ? trim($sellerNode->item(0)->nodeValue) : 'Unknown';

      // Image
      $imageNode = $xpath->query("//img[@id='landingImage']");
      $imageUrl = $imageNode->length ? $imageNode->item(0)->getAttribute('src') : '';

      // ⭐ Rating (primary)
      $ratingNode = $xpath->query("//span[@id='acrPopover']/@title");
      if ($ratingNode->length) {
        preg_match('/([0-9.]+)/', $ratingNode->item(0)->nodeValue, $matches);
        $rating = $matches[1] ?? '';
      }

      // ⭐ Rating fallback
      if (empty($rating)) {
        $ratingNodeAlt = $xpath->query("//i[@data-hook='average-star-rating']//span");
        if ($ratingNodeAlt->length) {
          preg_match('/([0-9.]+)/', $ratingNodeAlt->item(0)->nodeValue, $matches);
          $rating = $matches[1] ?? '';
        }
      }
    }

    // ================= FLIPKART =================
    else {

      // Title
      $titleNode = $xpath->query("//span[@class='B_NuCI']");
      $title = $titleNode->length ? trim($titleNode->item(0)->nodeValue) : 'Untitled';

      // Price
      $priceNode = $xpath->query("//div[@class='_30jeq3 _16Jk6d']");
      $price = $priceNode->length ? preg_replace('/[^0-9.]/', '', $priceNode->item(0)->nodeValue) : 0;

      // Description
      $descNode = $xpath->query("//div[@class='_1mXcCf RmoJUa']//p");
      foreach ($descNode as $item) {
        $description .= trim($item->nodeValue) . "\n";
      }

      // Seller
      $sellerNode = $xpath->query("//div[@id='sellerName']//span//span");
      $soldBy = $sellerNode->length ? trim($sellerNode->item(0)->nodeValue) : 'Unknown';

      // Image
      $imageNode = $xpath->query("//img[contains(@class,'_396cs4')]");
      $imageUrl = $imageNode->length ? $imageNode->item(0)->getAttribute('src') : '';

      // ⭐ Rating
      $ratingNode = $xpath->query("//div[contains(@class,'_3LWZlK')]");
      if ($ratingNode->length) {
        $rating = trim($ratingNode->item(0)->nodeValue);
      }
    }

    // Save Image
    if (!empty($imageUrl)) {
      $imageData = @file_get_contents($imageUrl);

      if ($imageData) {
        $file = \Drupal::service('file.repository')->writeData(
          $imageData,
          'public://product-images/' . basename(parse_url($imageUrl, PHP_URL_PATH)),
          FileSystemInterface::EXISTS_REPLACE
        );

        if ($file) {
          $fid = $file->id();
        }
      }
    }

    // Debug rating (optional)
    \Drupal::logger('product_debug')->notice('Rating: @r', ['@r' => $rating]);

    // Create node
    $node = Node::create([
      'type' => 'marchant_products',
      'title' => $title,
      'field_product_url' => $url,
      'field_price' => $price,
      'body' => $description,
      'field_external_id' => $external_id,
      'field_sold_bys' => $soldBy,
      'field_image' => $fid ? ['target_id' => $fid] : NULL,

      // ✅ Rating saved here
      'field_rating_new' => $rating,

      'status' => 1,
    ]);

    $node->save();

    return $node;
  }
}*/


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








public function extractProduct(Request $request) {
  $url = $request->query->get('url');

  if (empty($url)) {
    $this->messenger()->addError($this->t('No product URL provided.'));
    return $this->redirect('<front>');
  }

  try {
    $data = $this->extractProductData($url);

    // ✅ Store data in session (NOT saving node)
    \Drupal::request()->getSession()->set('extracted_product_data', $data);

    $this->messenger()->addStatus($this->t('Product data extracted successfully!'));

    return new RedirectResponse(
      Url::fromRoute('role_based_registration.product_form')->toString()
    );
  }
  catch (\Exception $e) {
    $this->messenger()->addError($this->t('Extraction failed: @msg', [
      '@msg' => $e->getMessage(),
    ]));
    return $this->redirect('<front>');
  }
}




public function extractProductData($url) {

  // Extract external ID
  preg_match('/\/dp\/([A-Z0-9]{10})/', $url, $matches);
  $external_id = $matches[1] ?? md5($url);

  if (empty($external_id)) {
    throw new \Exception('Could not extract product ID.');
  }

  // Prevent duplicate
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
    'type' => 'marchant_products',
    'field_external_id' => $external_id,
  ]);

  if (!empty($nodes)) {
    throw new \Exception('This product already exists.');
  }

  // Validate domain
  if (!preg_match('/amazon\.|ebay\./', $url)) {
    throw new \Exception('Only Amazon & eBay supported.');
  }

  // Fetch HTML
  $client = \Drupal::httpClient();
  $response = $client->request('GET', $url, [
    'headers' => [
      'User-Agent' => 'Mozilla/5.0',
      'Accept-Language' => 'en-US,en;q=0.9',
    ],
    'timeout' => 30,
  ]);

  $html = (string) $response->getBody();

  $dom = new \DOMDocument();
  @$dom->loadHTML($html);
  $xpath = new \DOMXPath($dom);

  // Default values
  $title = '';
  $price = 0;
  $description = '';
  $soldBy = '';
  $rating = '';
  $imageUrls = [];

  // ================= AMAZON =================
  if (strpos($url, 'amazon.') !== FALSE) {

    // Title
    $titleNode = $xpath->query("//span[@id='productTitle']");
    $title = $titleNode->length ? trim($titleNode->item(0)->nodeValue) : 'Untitled';

    // Price
    $priceNode = $xpath->query("//span[@class='a-price-whole']");
    $price = $priceNode->length ? preg_replace('/[^0-9]/', '', $priceNode->item(0)->nodeValue) : 0;

    // Description
    $descNode = $xpath->query("//div[@id='feature-bullets']//span[@class='a-list-item']");
    foreach ($descNode as $item) {
      $description .= trim($item->nodeValue) . "\n";
    }

    // Seller
    $sellerNode = $xpath->query("//a[@id='bylineInfo']");
    $soldBy = $sellerNode->length ? trim($sellerNode->item(0)->nodeValue) : 'Unknown';

    // ✅ MULTIPLE IMAGES
    $imageNodes = $xpath->query("//img[@data-a-dynamic-image]");

    foreach ($imageNodes as $img) {
      $data = $img->getAttribute('data-a-dynamic-image');

      if (!empty($data)) {
        $json = json_decode($data, TRUE);

        if (is_array($json)) {
          foreach ($json as $imgUrl => $size) {
            $imageUrls[] = $imgUrl;
          }
        }
      }
    }

    // Remove duplicates + limit
    $imageUrls = array_unique($imageUrls);
    $imageUrls = array_slice($imageUrls, 0, 6);

    // Rating
    $ratingNode = $xpath->query("//span[@id='acrPopover']/@title");
    if ($ratingNode->length) {
      preg_match('/([0-9.]+)/', $ratingNode->item(0)->nodeValue, $matches);
      $rating = $matches[1] ?? '';
    }
  }

  // ================= EBAY =================
  elseif (strpos($url, 'ebay.') !== FALSE) {

    // Title
    $titleNode = $xpath->query("//h1");
    if ($titleNode->length) {
      $title = trim($titleNode->item(0)->nodeValue);
    }

    // Price
    $priceNode = $xpath->query("//span[contains(text(),'$') or contains(text(),'₹')]");
    if ($priceNode->length) {
      $price = preg_replace('/[^0-9.]/', '', $priceNode->item(0)->nodeValue);
    }

    // Image
    $imageUrl = '';

    $imageNode = $xpath->query("//img[contains(@src,'ebayimg')]");
    if ($imageNode->length) {
      $imageUrl = $imageNode->item(0)->getAttribute('src');
    }

    // Fallback
    if (empty($imageUrl)) {
      preg_match('/https:\/\/i\.ebayimg\.com\/[^"]+/', $html, $matches);
      if (!empty($matches[0])) {
        $imageUrl = $matches[0];
      }
    }

    // ✅ ADD INTO ARRAY
    if (!empty($imageUrl)) {
      $imageUrls[] = $imageUrl;
    }

    // Seller
    $sellerNode = $xpath->query("//span[contains(text(),'Seller')]/following::span");
    if ($sellerNode->length) {
      $soldBy = trim($sellerNode->item(0)->nodeValue);
    }

    // Description
    $descNode = $xpath->query("//meta[@name='description']/@content");
    if ($descNode->length) {
      $description = trim($descNode->item(0)->nodeValue);
    }
  }

  // Return data
  return [
    'title' => $title,
    'description' => $description,
    'price' => $price,
    'sold_by' => $soldBy,
    'image_urls' => $imageUrls,
    'rating' => $rating,
    'external_id' => $external_id,
    'product_url' => $url,
  ];
}
}

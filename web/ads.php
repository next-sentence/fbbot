<?php
/**
 * Copyright (c) 2015-present, Facebook, Inc. All rights reserved.
 *
 * You are hereby granted a non-exclusive, worldwide, royalty-free license to
 * use, copy, modify, and distribute this software in source code or binary
 * form for use in connection with the web services and APIs provided by
 * Facebook.
 *
 * As with any software that integrates with the Facebook platform, your use
 * of this software is subject to the Facebook Developer Principles and
 * Policies [http://developers.facebook.com/policy/]. This copyright notice
 * shall be included in all copies or substantial portions of the software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 */

define("STDOUT", fopen('log.txt', 'w'));

require __DIR__ . './../vendor/autoload.php';

use FacebookAds\Object\Business;
use FacebookAds\Object\ProductCatalog;
use FacebookAds\Object\ProductFeed;
use FacebookAds\Object\ProductSet;
use FacebookAds\Object\ExternalEventSource;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Campaign;
use FacebookAds\Object\AdSet;
use FacebookAds\Object\AdCreative;
use FacebookAds\Object\Ad;
use FacebookAds\Object\AdPreview;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;

$access_token = 'EAAEHSjditZCkBAORsezdutWcyGTxO8Kv3xygKa1HXaVBUu2fphIZC6TEwmMYnFE7fuA8HwXFhZBMKUp8uesgDDhAkm7kJoE8FgrSmTvfiiROsZCcXNNPbSisppxsEQWR9ZCkWOdSZAs93byid8ZCUnRUZCQUZB02m8aC9vP20ASK0pO6fUwc7SUcq';
$app_secret = '3707d6ae889a6acc245c319bf9993f15';
$ad_account_id = '102529790511603';
$business_id = '355160638243382';
$page_id = '1885788065007710';
$pixel_id = '1929733453907753';
$app_id = '1929733453907753';

$api = Api::init($app_id, $app_secret, $access_token);
$api->setLogger(new CurlLogger());

$fields = array();
$params = array(
    'name' => 'Test Catalog',
);
$product_catalog = (new Business($business_id))->createProductCatalog(
    $fields,
    $params
);
$product_catalog_id = $product_catalog->id;
echo 'product_catalog_id: ' . $product_catalog_id . "\n\n";

$fields = array();
$params = array(
    'name' => 'Test Feed',
    'schedule' => array('interval' => 'DAILY', 'url' => 'https://origincache.facebook.com/developers/resources/?id=dpa_product_catalog_sample_feed.csv', 'hour' => '22'),
);
echo json_encode((new ProductCatalog($product_catalog_id))->createProductFeed(
    $fields,
    $params
)->getData(), JSON_PRETTY_PRINT);

$fields = array();
$params = array(
    'name' => 'All Product',
);
$product_set = (new ProductCatalog($product_catalog_id))->createProductSet(
    $fields,
    $params
);

//$pixel = new \FacebookAds\Object\AdsPixel(null, $ad_account_id);
//$pixel->{\FacebookAds\Object\Fields\AdsPixelFields::NAME} = 'My WCA Pixel';
//$pixel->create();

$product_set_id = $product_set->id;
echo 'product_set_id: ' . $product_set_id . "\n\n";

$fields = array();
$params = array(
    'external_event_sources' => array($pixel_id),
);
echo json_encode((new ProductCatalog($product_catalog_id))->createExternalEventSource(
    $fields,
    $params
)->getData(), JSON_PRETTY_PRINT);

//$fields = array();
//$params = array(
//    'objective' => 'PRODUCT_CATALOG_SALES',
//    'promoted_object' => array('product_catalog_id' => $product_catalog_id),
//    'status' => 'PAUSED',
//    'name' => 'My Campaign',
//);
//$campaign = (new AdAccount($ad_account_id))->createCampaign(
//    $fields,
//    $params
//);
//$campaign_id = $campaign->id;
//echo 'campaign_id: ' . $campaign_id . "\n\n";
//
//$fields = array();
//$params = array(
//    'status' => 'PAUSED',
//    'targeting' => array('geo_locations' => array('countries' => array('US'))),
//    'daily_budget' => '1000',
//    'billing_event' => 'IMPRESSIONS',
//    'bid_amount' => '20',
//    'campaign_id' => $campaign_id,
//    'optimization_goal' => 'OFFSITE_CONVERSIONS',
//    'promoted_object' => array('product_set_id' => $product_set_id),
//    'name' => 'My AdSet',
//);
//$ad_set = (new AdAccount($ad_account_id))->createAdSet(
//    $fields,
//    $params
//);
//$ad_set_id = $ad_set->id;
//echo 'ad_set_id: ' . $ad_set_id . "\n\n";
//
//$fields = array();
//$params = array(
//    'url_tags' => 'utm_source=facebook',
//    'object_story_spec' => array('page_id' => $page_id, 'template_data' => array('call_to_action' => array('type' => 'SHOP_NOW'), 'link' => 'www.example.com', 'name' => 'array(array(product.name)) - array(array(product.price))', 'description' => 'array(array(product.description))', 'message' => 'array(array(product.name | titleize))')),
//    'name' => 'My Creative',
//    'product_set_id' => $product_set_id,
//    'applink_treatment' => 'web_only',
//);
//$creative = (new AdAccount($ad_account_id))->createAdCreative(
//    $fields,
//    $params
//);
//$creative_id = $creative->id;
//echo 'creative_id: ' . $creative_id . "\n\n";
//
//$fields = array();
//$params = array(
//    'tracking_specs' => array(array('action_type' => array('offsite_conversion'), 'fb_pixel' => array($pixel_id))),
//    'status' => 'PAUSED',
//    'adset_id' => $ad_set_id,
//    'name' => 'My Ad',
//    'creative' => array('creative_id' => $creative_id),
//);
//$ad = (new AdAccount($ad_account_id))->createAd(
//    $fields,
//    $params
//);
//$ad_id = $ad->id;
//echo 'ad_id: ' . $ad_id . "\n\n";
//
//$fields = array();
//$params = array(
//    'ad_format' => 'DESKTOP_FEED_STANDARD',
//);
//echo json_encode((new Ad($ad_id))->getPreviews(
//    $fields,
//    $params
//)->getData(), JSON_PRETTY_PRINT);
//

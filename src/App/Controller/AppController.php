<?php

namespace App\Controller;

use App\Entity\Config;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Facebook\Extensions\Element;
use BotMan\Drivers\Facebook\Extensions\ElementButton;
use BotMan\Drivers\Facebook\Extensions\GenericTemplate;
use BotMan\Drivers\Facebook\FacebookDriver;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AppController extends Controller
{
    /**
     * @Route("/", name="app_homepage")
     */
    public function indexAction(Request $request)
    {

        $session = $request->getSession();

        $fb = new \Facebook\Facebook([
            'app_id' => $this->getParameter('fb.app_id'),
            'app_secret' => $this->getParameter('fb.app_secret'),
            'default_graph_version' => 'v2.10',
        ]);

        $helper = $fb->getRedirectLoginHelper();

        if ($request->get('state')) {
            $helper->getPersistentDataHandler()->set('state', $request->get('state'));
        }

        $pages = [];
        $permissions = ['manage_pages']; // optional

        try {
            $accessToken = $helper->getAccessToken();
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            throw new \Exception($e->getMessage());
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            throw new \Exception($e->getMessage());
        }

        if (isset($accessToken)) {
            if ($session->get('facebook_access_token')) {
                $fb->setDefaultAccessToken($session->get('facebook_access_token'));
            } else {
                // getting short-lived access token
                $session->set('facebook_access_token', (string)$accessToken);

                // OAuth 2.0 client handler
                $oAuth2Client = $fb->getOAuth2Client();

                // Exchanges a short-lived access token for a long-lived one
                $longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($session->get('facebook_access_token'));

                $session->set('facebook_access_token', (string)$longLivedAccessToken);

                // setting default access token to be used in script
                $fb->setDefaultAccessToken($session->get('facebook_access_token'));
            }

            // validating user access token

            // validating user access token
            try {
                $user = $fb->get('/me');
                $user = $user->getGraphNode()->asArray();
            } catch(\Facebook\Exceptions\FacebookResponseException $e) {
                // When Graph returns an error
                echo 'Graph returned an error: ' . $e->getMessage();
                session_destroy();
                // if access token is invalid or expired you can simply redirect to login page using header() function
                exit;
            } catch(\Facebook\Exceptions\FacebookSDKException $e) {
                // When validation fails or other local issues
                echo 'Facebook SDK returned an error: ' . $e->getMessage();
                exit;
            }

            $pages = $fb->get('/me/accounts');
            $pages = $pages->getGraphEdge()->asArray();
        }

        return $this->render('index.html.twig', [
            'loginUrl' => $helper->getLoginUrl( $this->getParameter('app.url'). $this->generateUrl('app_homepage'), $permissions),
            'pages' => $pages
        ]);
    }

    /**
     * @Route("/subscribe", name="subscribe")
     * @Method({"GET", "POST"})
     */
    public function subscribeAction(Request $request)
    {
        $session = $request->getSession();

        $fb = new \Facebook\Facebook([
            'app_id' => $this->getParameter('fb.app_id'),
            'app_secret' => $this->getParameter('fb.app_secret'),
            'default_graph_version' => 'v2.10',
        ]);

        $helper = $fb->getRedirectLoginHelper();

        $pages = [];
        $permissions = ['manage_pages'];

        try {
            $accessToken = $helper->getAccessToken();
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            throw new \Exception($e->getMessage());
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            throw new \Exception($e->getMessage());
        }

        if ($accessToken === null) {
            if ($session->get('facebook_access_token')) {
                $fb->setDefaultAccessToken($session->get('facebook_access_token'));
            } else {
                // getting short-lived access token
                $session->set('facebook_access_token', (string)$accessToken);

                // OAuth 2.0 client handler
                $oAuth2Client = $fb->getOAuth2Client();

                // Exchanges a short-lived access token for a long-lived one
                $longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($session->get('facebook_access_token'));

                $session->set('facebook_access_token', (string)$longLivedAccessToken);

                // setting default access token to be used in script
                $fb->setDefaultAccessToken($session->get('facebook_access_token'));
            }

            // validating user access token
            try {
                $user = $fb->get('/me');
                $user = $user->getGraphNode()->asArray();
            } catch(\Facebook\Exceptions\FacebookResponseException $e) {
                // When Graph returns an error
                echo 'Graph returned an error: ' . $e->getMessage();
                session_destroy();
                // if access token is invalid or expired you can simply redirect to login page using header() function
                exit;
            } catch(\Facebook\Exceptions\FacebookSDKException $e) {
                // When validation fails or other local issues
                echo 'Facebook SDK returned an error: ' . $e->getMessage();
                exit;
            }

            $pages = $fb->get('/me/accounts');
            $pages = $pages->getGraphEdge()->asArray();
        }

        if ($request->isMethod('POST')) {

            $params = [
                'fields' => 'access_token',
                'client_id' => $this->getParameter('fb.app_id'),
                'client_secret' => $this->getParameter('fb.app_secret'),
                'grant_type' => 'client_credentials',
                'scope' => 'pages_messaging, pages_messaging_phone_number, pages_messaging_subscriptions,
                            messages, messaging_postbacks, messaging_optins, message_deliveries, message_reads, 
                            messaging_payments, messaging_pre_checkouts, messaging_checkout_updates, messaging_account_linking, 
                            messaging_referrals, message_echoes, standby, messaging_handovers, messaging_policy_enforcement'
            ];
            //messages, messaging_postbacks, messaging_optins, message_deliveries, message_reads,
            // messaging_payments, messaging_pre_checkouts, messaging_checkout_updates,
            // messaging_account_linking, messaging_referrals, message_echoes, standby, messaging_handovers, messaging_policy_enforcement

            //https://www.facebook.com/v2.6/dialog/oauth?response_type=token&display=popup&client_id=350300872106773&redirect_uri=https%3A%2F%2Fdevelopers.facebook.com%2Ftools%2Fexplorer%2Fcallback&scope=
            //manage_pages
            //pages_messaging
            //pages_messaging_phone_number
            //Cpages_messaging_subscriptions

            $us = $fb->get('/oauth/access_token?' . http_build_query($params));
            $us = $us->getGraphNode()->asArray();

            $page = $fb->get('/' . $request->get('page') . '?fields=access_token, name, id');
            $page = $page->getGraphNode()->asArray();

            $subscribe = $fb->post('/' . $page['id'] . '/subscribed_apps', ['app_id' => $this->getParameter('fb.app_id')], $page['access_token']);
            $subscribe = $subscribe->getGraphNode()->asArray();
            print_r($subscribe);

            $sub = $fb->post('/' . $this->getParameter('fb.app_id') . '/subscriptions',
                [
                    'object' => 'page',
                    'verify_token' => 'test',
                    'callback_url' => $this->getParameter('app.url'). $this->generateUrl('app_webhook'),
//                    'fields' => ['about', 'picture'],
                    'include_values' => true
                ],
                $us['access_token']
            );
            $sub = $sub->getGraphNode()->asArray();
            print_r($sub);
            print_r($page);
        }


        return $this->render('index.html.twig', [
            'loginUrl' => $helper->getLoginUrl( $this->getParameter('app.url'). $this->generateUrl('app_homepage'), $permissions),
            'pages' => $pages
        ]);
    }

    /**
     * @Route("/webhook", name="app_webhook")
     */
    public function webHookAction(Request $request)
    {
        $config = [
            'facebook' => [
                'token' => 'EAAEZBmMcL4xUBAF2A1LcBZAYj3gN4dL8Eq596rtDkcGb6ZAB3SCBZCmqW52sq9axnZAtZATqhfwcg7MJgZBrjrvVdBqnp3FyQzdMoRZCDdYlNpaC8XZCY2oa1wyH4K4d5nVC9GnCiRqhpLUbkJGqVeJZBRnWyI6QaZCzJhaUX63Nnv71AZDZD',
                'app_secret' => $this->getParameter('fb.app_secret'),
                'verification'=>'test',
            ]];

        $config['facebook']['token'] = "EAAEZBmMcL4xUBAF2A1LcBZAYj3gN4dL8Eq596rtDkcGb6ZAB3SCBZCmqW52sq9axnZAtZATqhfwcg7MJgZBrjrvVdBqnp3FyQzdMoRZCDdYlNpaC8XZCY2oa1wyH4K4d5nVC9GnCiRqhpLUbkJGqVeJZBRnWyI6QaZCzJhaUX63Nnv71AZDZD";

        DriverManager::loadDriver(FacebookDriver::class);

        $botman = BotManFactory::create($config);

        $botman->hears('foo', function (BotMan $bot) {
            $bot->reply('Hello World');
        });

        $botman->hears('noroc', function (BotMan $bot) {
            $bot->reply('norocel');
        });

        $botman->hears('{text}', function (BotMan $bot, $text) {
            $bot->reply($text);
        });

        $botman->on('messaging_optins', function($payload, BotMan $bot) {

            $ad = $payload;
            $bot->say(
                GenericTemplate::create()
                    ->addImageAspectRatio(GenericTemplate::RATIO_SQUARE)
                    ->addElements([
                        Element::create('BotMan Documentation')
                            ->subtitle('All about BotMan')
                            ->image('http://botman.io/img/botman-body.png')
                            ->addButton(ElementButton::create('visit')->url('http://botman.io'))
                            ->addButton(ElementButton::create('tell me more')
                                ->payload('tellmemore')->type('postback')),
                        Element::create('BotMan Laravel Starter')
                            ->subtitle('This is the best way to start with Laravel and BotMan')
                            ->image('http://botman.io/img/botman-body.png')
                            ->addButton(ElementButton::create('visit')
                                ->url('https://github.com/mpociot/botman-laravel-starter')
                            )
                    ]),
                '1450167398384668',
                FacebookDriver::class
            );
        });

        $botman->on('messaging_referrals', function($payload, BotMan $bot) {

            $bot->say('welcome', $payload);
        });

        $botman->on('messaging_optins', function($payload, BotMan $bot) {
            $bot->say('welcome', $payload);
        });

        $botman->listen();

        return new Response();
    }

    /**
     * @Route("/send", name="app_send")
     */
    public function sendAction(Request $request)
    {
        $config = [
            'facebook' => [
                'token' => 'EAAEZBmMcL4xUBAJU7cHTVz3f1a5btlwGzT7p10dgdjbeFTqe770tfZA49rHvrjEEcHkTDfgPmQfkaV9wrvlDpqfhvZCmN2EMUYl6ZCu94vqIQuZB0YzQcnHpIyfD7h8HF55PQat9bvSotDEXjeZBlr3QqZBg1UDw2f5bCjMnZC8ZBYAZDZD',
                'app_secret' => $this->getParameter('fb.app_secret'),
                'verification'=>'test',
            ]];

        DriverManager::loadDriver(FacebookDriver::class);

        $botman = BotManFactory::create($config);

//        $botman->on('messaging_optins', function($payload, BotMan $botman) {
        $botman->say(
                GenericTemplate::create()
                    ->addImageAspectRatio(GenericTemplate::RATIO_SQUARE)
                    ->addElements([
                        Element::create('BotMan Documentation')
                            ->subtitle('All about BotMan')
                            ->image('http://botman.io/img/botman-body.png')
                            ->addButton(ElementButton::create('visit')->url('http://botman.io'))
                            ->addButton(ElementButton::create('tell me more')
                                ->payload('tellmemore')->type('postback')),
                        Element::create('BotMan Laravel Starter')
                            ->subtitle('This is the best way to start with Laravel and BotMan')
                            ->image('http://botman.io/img/botman-body.png')
                            ->addButton(ElementButton::create('visit')
                                ->url('https://github.com/mpociot/botman-laravel-starter')
                            )
                    ]),
                '1642945535776920',
                FacebookDriver::class
            );
//        });



        $botman->listen();


        return new JsonResponse(true);
    }

    /**
     * @Route("/pixel", name="app_pixel")
     */
    public function pixelAction(Request $request)
    {
        return $this->render('pixel.html.twig', []);
    }

    /**
     * @Route("/checkbox", name="app_checkbox")
     */
    public function checkboxAction(Request $request)
    {
        return $this->render('checkbox.html.twig', [
            'origin' => $this->generateUrl('app_webhook', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'appId' => $this->getParameter('fb.app_id'),
            'pageId' => '125784381475358 ',
            'uniqueId' => rand(100000, 999999),
        ]);
    }
}
//https://www.facebook.com/tr/?id=350300872106773&
//ev=MessengerCheckboxUserConfirmation&
//dl=https%3A%2F%2Fb4c10fb5.ngrok.io%2Fcheckbox&
//rl=&if=false&ts=1512650733262&cd%5Bapp_id%5D=350300872106773&cd%5Bpage_id%5D=125784381475358%20&cd%5B
//ref%5D=webhook&cd%5Buser_ref%5D=329864&sw=1920&sh=1080&dt=4xe3gcu2rh8apqgzerhbmtnqrbf4j1tp
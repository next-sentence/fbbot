<?php

namespace App\Controller;

use App\Entity\Config;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Facebook\FacebookDriver;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
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
                'scope' => 'pages_messaging, pages_messaging_phone_number, pages_messaging_subscriptions'
            ];

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
                'token' => 'EAAEZBmMcL4xUBALKtcficMH05A87inhoWQyHEFYftftA6A4b8zi1ni5BRnp3bCgU5PDAIwucPOKuDqYEXpK1f5Yv6GDBb7Fgz7yudZAGZCF7CBnAlbGMtaG5Mhr8wZBJ5mBryyppMtiYAIOMgV2AekQ90ZAWc7EZCrWijPYZCrVfAZDZD',
                'app_secret' => $this->getParameter('fb.app_secret'),
                'verification'=>'test',
            ]];

        $config['facebook']['token'] = "EAAEZBmMcL4xUBAFSHptCeALMBoHvmXXC7kH2g0K7KCnt4a8KZAAZBpyuLqBBKExX8854cMsKdZCvsIZBMZCE9TpgsaIshz9ndNV1ZA3p88hSorF3gjjrI1ItcEceHd2APxUKnu54hRwbihv44SKhZC5K1J5WuBnyYNBhFgsfW9YQwQZDZD";

        DriverManager::loadDriver(FacebookDriver::class);

        $botman = BotManFactory::create($config);

        $botman->hears('foo', function (BotMan $bot) {
            $bot->reply('Hello World');
        });

        $botman->listen();

        return new Response();
    }

    /**
     * @Route("/pixel", name="app_pixel")
     */
    public function pixelAction(Request $request)
    {
        return $this->render('pixel.html.twig', []);
    }
}
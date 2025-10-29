<?php

namespace Tualo\Office\Paypal\Routes;

use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\Basic\Route as BasicRoute;
use Tualo\Office\Basic\IRoute;
use Stripe\StripeClient;
use Stripe\Webhook as StripeWebhook;
use Stripe\Exception\SignatureVerificationException;

class Webhook extends \Tualo\Office\Basic\RouteWrapper
{

    public static function register()
    {
        BasicRoute::add('/paypal/webhook', function ($matches) {
            try {
                $db = App::get('session')->getDB();


                $payload = @file_get_contents('php://input');
                $db->direct(
                    'insert into paypal_webhook ( eventtype,eventdata) values ( {eventtype},{eventdata})',
                    [
                        'eventtype' => 'incoming',
                        'eventdata' => $payload
                    ]
                );



                http_response_code(200);
                exit();
            } catch (\Exception $e) {
                echo $e->getMessage();
                http_response_code(400);
            }
        }, ['get', 'post', 'put', 'delete'], true);
    }
}

<?php

namespace Tualo\Office\Paypal\Routes;

use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\Basic\Route as BasicRoute;
use Tualo\Office\Basic\IRoute;
use Tualo\Office\Paypal\API;

class Test extends \Tualo\Office\Basic\RouteWrapper
{

    public static function register()
    {
        BasicRoute::add('/paypal/auth', function ($matches) {
            try {
                API::init();
                $data = API::auth();
                App::result('success', true);
                App::result('data', $data);
                App::contenttype('application/json');
            } catch (\Exception $e) {
                echo $e->getMessage();
                http_response_code(400);
            }
        }, ['get'], true);

        BasicRoute::add('/paypal/profile', function ($matches) {
            try {
                API::init();
                $data = API::userProfile();
                App::result('success', true);
                App::result('data', $data);
                App::contenttype('application/json');
            } catch (\Exception $e) {
                echo $e->getMessage();
                http_response_code(400);
            }
        }, ['get'], true);

        BasicRoute::add('/paypal/createorder', function ($matches) {
            try {
                API::init();
                $data = API::createOrder(
                    10,
                    'EUR',
                    'RN1623712',
                    '13718'
                );
                App::result('success', true);
                App::result('data', $data);
                App::contenttype('application/json');
            } catch (\Exception $e) {
                echo $e->getMessage();
                http_response_code(400);
            }
        }, ['get'], true);

        BasicRoute::add('/paypal/details', function ($matches) {
            try {
                API::init();
                $data = API::orderDetails(
                    $_REQUEST['id'],
                );
                App::result('success', true);
                App::result('data', $data);
                App::contenttype('application/json');
            } catch (\Exception $e) {
                echo $e->getMessage();
                http_response_code(400);
            }
        }, ['get'], true);

        BasicRoute::add('/paypal/authorize', function ($matches) {
            try {
                API::init();
                $data = API::authorizeOrder(
                    $_REQUEST['id'],
                );
                App::result('success', true);
                App::result('data', $data);
                App::contenttype('application/json');
            } catch (\Exception $e) {
                echo $e->getMessage();
                http_response_code(400);
            }
        }, ['get'], true);

        BasicRoute::add('/paypal/capture', function ($matches) {
            try {
                API::init();
                $data = API::captureOrder(
                    $_REQUEST['id'],
                );
                App::result('success', true);
                App::result('data', $data);
                App::contenttype('application/json');
            } catch (\Exception $e) {
                echo $e->getMessage();
                http_response_code(400);
            }
        }, ['get'], true);
    }
}

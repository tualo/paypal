<?php
namespace Tualo\Office\Paypal;

use Tualo\Office\Basic\TualoApplication as App;

use Ramsey\Uuid\Uuid;
use GuzzleHttp\Client;

class API {

    private static $ENV = null;
    private static $gateway = null;
    
    public static function init():void{
        self::getEnvironment();
    }

    public static function addEnvrionment(string $id, string $val)
    {
        self::$ENV[$id] = $val;
        $db = App::get('session')->getDB();
        try {
            if (!is_null($db)) {
                $db->direct('insert into paypal_environment (id,val) values ({id},{val}) on duplicate key update val=values(val)', [
                    'id' => $id,
                    'val' => $val
                ]);
            }
        } catch (\Exception $e) {
        }
    }

    public static function getEnvironment(): array
    {
        if (is_null(self::$ENV)) {
            $db = App::get('session')->getDB();
            try {
                self::$ENV = [];
                if (!is_null($db)) {
                    $data = $db->direct('select id,val from paypal_environment');
                    foreach ($data as $d) {
                        self::$ENV[$d['id']] = $d['val'];
                    }
                }
            } catch (\Exception $e) {
            }
        }
        return self::$ENV;
    }

    public static function replacer($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::replacer($value);
            }
            return $data;
        } else if (is_string($data)) {
            $env = self::getEnvironment();
            foreach ($env as $key => $value) {
                $data = str_replace('{{' . $key . '}}', $value, $data);
            }
            return $data;
        }
        return $data;
    }
    public static function env($key, $default = null)
    {
        $env = self::getEnvironment();
        if (isset($env[$key])) {
            return $env[$key];
        }else if (!is_null($default)) {
            return $default;
        }
        throw new \Exception('Environment ' . $key . ' not found!');
    }
    public static function client(bool $token=false):Client{
        $options = [
            'base_uri' => self::env('base_url'),
            'timeout'  => 2.0,
        ];
        if ($token){
            $options['headers'] = [
                'Authorization' => 'Bearer ' . self::env('access_token')
            ];
        }
        return new Client($options);
    }
    public static function auth()
    {
        
        $response = self::client()->post('/v1/oauth2/token', [
            'auth' => [self::env('CLIENT_ID'), self::env('CLIENT_SECRET')],
            'form_params' => [
                'grant_type' => 'client_credentials'
            ]
        ]);
        $code = $response->getStatusCode(); // 200
        $reason = $response->getReasonPhrase(); // OK

        if ($code != 200) {
            throw new \Exception($reason);
        }
        $result = json_decode($response->getBody()->getContents(), true);
        if (isset($result['access_token'])) {
            self::addEnvrionment('access_token', $result['access_token']);
            self::addEnvrionment('app_id', $result['app_id']);
            self::addEnvrionment('access_token_expires_at', time()+intval($result['expires_in']));
            self::addEnvrionment('nonce', $result['nonce']);
            self::addEnvrionment('scope', $result['scope']);
        }
        return $result;
    }

    public static function userProfile(){
        $response = self::client(true)->get('/v1/identity/openidconnect/userinfo?schema=openid');
        $code = $response->getStatusCode(); // 200
        $reason = $response->getReasonPhrase(); // OK

        if ($code != 200) {
            throw new \Exception($reason);
        }
        $result = json_decode($response->getBody()->getContents(), true);
        return $result;
    }


    public static function createOrder(
        float $value,
        string $curreny,
        string $invoicenumber,
        string $custom_id,

    ){

        $options = [
            'base_uri' => self::env('base_url'),
            'timeout'  => 2.0,
        ];
        $options['headers'] = [
            'Authorization' => 'Bearer ' . self::env('access_token'),
            'PayPal-Request-Id' => time()
        ];

        $o = '{
            "intent": "CAPTURE",
            "purchase_units": [
              {
                "amount": {
                  "currency_code": "USD",
                  "value": "100.00"
                },
                "shipping": {
                    "type": "SHIPPING",
                    "address": {
                      "address_line_1": "2211 N First Street",
                      "address_line_2": "Building 17",
                      "admin_area_2": "San Jose",
                      "admin_area_1": "CA",
                      "postal_code": "95131",
                      "country_code": "US"
                    }
                }
              }
            ],

            
            "payment_source": {
              "paypal": {
                "experience_context": {
                  "payment_method_preference": "IMMEDIATE_PAYMENT_REQUIRED",
                  "brand_name": "EXAMPLE INC",
                  "locale": "en-US",
                  "landing_page": "LOGIN",
                  "shipping_preference": "SET_PROVIDED_ADDRESS",
                  "user_action": "PAY_NOW",
                  "return_url": "https://world-contact.systems/returnUrl",
                  "cancel_url": "https://world-contact.systems/cancelUrl"
                }
              }
            }
          }' ;

        $json = json_decode($o,true);
        $json['purchase_units'][0]['amount']['value'] = number_format($value,2,'.','');
        $json['purchase_units'][0]['amount']['currency_code'] = $curreny;
        /*
        $json['purchase_units'][0]['invoice_id'] = $invoicenumber;
        $json['purchase_units'][0]['custom_id'] = $custom_id;
        */

        $json['purchase_units'][0]['shipping']['address']['address_line_1'] = 'Thomas Hoffmann';
        $json['purchase_units'][0]['shipping']['address']['address_line_2'] = 'Hauptstr. 1';
        $json['purchase_units'][0]['shipping']['address']['admin_area_2'] = 'Hamburg';
        $json['purchase_units'][0]['shipping']['address']['admin_area_1'] = 'Hamburg';
        $json['purchase_units'][0]['shipping']['address']['postal_code'] = '20095';
        $json['purchase_units'][0]['shipping']['address']['country_code'] = 'DE';



        $json['payment_source']['paypal']['experience_context']['brand_name'] = self::env('brand_name','tualo solutions GmbH');
        //$json['payment_source']['paypal']['experience_context']['locale'] = self::env('locale','de_DE');
        $json['payment_source']['paypal']['experience_context']['landing_page'] = self::env('landing_page','LOGIN');
        $json['payment_source']['paypal']['experience_context']['shipping_preference'] = self::env('shipping_preference','SET_PROVIDED_ADDRESS');
        $json['payment_source']['paypal']['experience_context']['user_action'] = self::env('user_action','PAY_NOW');
        $json['payment_source']['paypal']['experience_context']['return_url'] = self::env('return_url','https://world-contact.systems/returnUrl');
        $json['payment_source']['paypal']['experience_context']['cancel_url'] = self::env('cancel_url','https://world-contact.systems/cancelUrl');

        $response = (new Client($options))->post('/v2/checkout/orders',[
            'json' => $json
        ]
        );

        $code = $response->getStatusCode(); // 200
        $reason = $response->getReasonPhrase(); // OK

        if ($code != 200) {
            throw new \Exception($reason);
        }
        $result = json_decode($response->getBody()->getContents(), true);
        return $result;
    }
    

    public static function orderDetails(
        string $authorization_id
    ):array
    {
        $response = self::client(true)->get('/v2/checkout/orders/'.$authorization_id.'');
        $code = $response->getStatusCode(); // 200
        $reason = $response->getReasonPhrase(); // OK

        if ($code != 200) {
            throw new \Exception($reason);
        }
        $result = json_decode($response->getBody()->getContents(), true);
        return $result;
    }

    public static function authorizeOrder(
        string $authorization_id
    ):array
    {
        $details = self::orderDetails($authorization_id);

        $response = self::client(true)->post('/v2/checkout/orders/'.$authorization_id.'/authorize',[
            'json' => [
                'payment_source' => $details['payment_source']
            ]
        ]);
        $code = $response->getStatusCode(); // 200
        $reason = $response->getReasonPhrase(); // OK

        if ($code != 200) {
            throw new \Exception($reason);
        }
        $result = json_decode($response->getBody()->getContents(), true);
        return $result;
    }

    public static function captureOrder(
        string $authorization_id
    ):array
    {
        $details = self::orderDetails($authorization_id);

        $response = self::client(true)->post('/v2/checkout/orders/'.$authorization_id.'/capture',[
            'json' => [
                'payment_source' => $details['payment_source']
            ]
        ]);
        $code = $response->getStatusCode(); // 200
        $reason = $response->getReasonPhrase(); // OK

        if ($code != 200) {
            throw new \Exception($reason);
        }
        $result = json_decode($response->getBody()->getContents(), true);
        return $result;
    }

}
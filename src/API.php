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
                $db->direct('insert into paypal_environments (id,val) values ({id},{val}) on duplicate key update val=values(val)', [
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
                if (!is_null($db)) {
                    $data = $db->direct('select id,val from paypal_environments');
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
    public static function env($key)
    {
        $env = self::getEnvironment();
        if (isset($env[$key])) {
            return $env[$key];
        }
        throw new \Exception('Environment ' . $key . ' not found!');
    }
    
    public static function auth()
    {


        $client = new Client(
            [
                'base_uri' => self::env('sign_base_url'),
                'timeout'  => 2.0,
            ]
        );
        $response = $client->post('/api/v2/auth', [
            'json' => [
                'api_key' => self::env('api_key'),
                'api_secret' => self::env('api_secret')
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
            self::addEnvrionment('access_token_expires_at', $result['access_token_expires_at']);

            self::addEnvrionment('refresh_token', $result['refresh_token']);
            self::addEnvrionment('refresh_token_expires_at', $result['refresh_token_expires_at']);
        }
        return $result;
    }

}
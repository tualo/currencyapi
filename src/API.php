<?php

namespace Tualo\Office\CurrencyAPI;

use Tualo\Office\Basic\TualoApplication;
use Ramsey\Uuid\Uuid;
use GuzzleHttp\Client;

class API
{

    private static $ENV = null;

    public static function addEnvrionment(string $id, string $val)
    {
        self::$ENV[$id] = $val;
        $db = TualoApplication::get('session')->getDB();
        try {
            if (!is_null($db)) {
                $db->direct('insert into currencyapi_environments (id,val) values ({id},{val}) on duplicate key update val=values(val)', [
                    'id' => $id,
                    'val' => $val
                ]);
            }
        } catch (\Exception $e) {
        }
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

    

    public static function getEnvironment(): array
    {
        if (is_null(self::$ENV)) {
            $db = TualoApplication::get('session')->getDB();
            try {
                if (!is_null($db)) {
                    $data = $db->direct('select id,val from currencyapi_environments');
                    foreach ($data as $d) {
                        self::$ENV[$d['id']] = $d['val'];
                    }
                }
            } catch (\Exception $e) {
            }
        }
        return self::$ENV;
    }

    public static function env($key)
    {
        $env = self::getEnvironment();
        if (isset($env[$key])) {
            return $env[$key];
        }
        throw new \Exception('Environment ' . $key . ' not found!');
    }



    public static function getDateRange(int $start,int $stop,string $base_currency,array $currencies,string $accuracy='day')
    {
        /*
        curl -G https://api.currencyapi.com/v3/range?datetime_start=2021-11-30T23:59:59Z&datetime_end=2021-12-31T23:59:59Z&accuracy=day \
    -H "apikey: YOUR-API-KEY"
        */
        $client = new Client(
            [
                'base_uri' => self::env('url'),
                'timeout'  => 2.0,
                'headers' => [
                    'apikey' => self::env('apikey')
                ]
            ]
        );
        $response = $client->get('/v3/range', [
            'query' => [
                'datetime_start' => date('Y-m-d\TH:i:s\Z',$start),
                'datetime_end' => date('Y-m-d\TH:i:s\Z',$stop),
                'accuracy' => $accuracy,
                'base_currency' => $base_currency,
                'currencies' => implode(',',$currencies)
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
<?php
namespace Tualo\Office\CurrencyAPI\Routes;
use Tualo\Office\Basic\TualoApplication;
use Tualo\Office\Basic\Route;
use Tualo\Office\Basic\IRoute;
use Tualo\Office\CurrencyAPI\API;


class Base implements IRoute{
    public static function register(){

        Route::add('/currencyapi/historical/(?P<start>[\d\-\_]+)/(?P<stop>[\d\-\_]+)/(?P<currencies>[\w\-\_]+)',function($matches){
            TualoApplication::contenttype('application/json');
            try{
                $result = API::getDateRange(
                    strtotime($matches['start']),
                    strtotime($matches['stop']),
                    'EUR',
                    explode('-',$matches['currencies'])
                );

                TualoApplication::result('time',time() );
                TualoApplication::result('data',$result );
                TualoApplication::result('success',true );

            }catch(\Exception $e){
                TualoApplication::result('msg', $e->getMessage());
            }
        },array('get','post'),true);


        Route::add('/currencyapi/historical/(?P<date>[\d\-\_]+)/(?P<currencies>[\w\-\_]+)',function($matches){
            TualoApplication::contenttype('application/json');
            try{
                $result = API::getDate(
                    strtotime($matches['date']),
                    'EUR',
                    explode('-',$matches['currencies'])
                );

                $sql ='insert into currencyapi_historical_day 
                (base_currency,target_currency,`date`,rate) 
                values ({base_currency},{target_currency},{date},{rate})
                on duplicate key update rate=values(rate)';
                foreach($result['data'] as $key=>$r){
                    TualoApplication::get('session')->getDB()->direct($sql,[
                        'base_currency'=>'EUR',
                        'target_currency'=>$r['code'],
                        'date'=>$matches['date'],
                        'rate'=>$r['value']
                    ]);
                }
                TualoApplication::result('time',time() );
                TualoApplication::result('data',$result );
                TualoApplication::result('success',true );

            }catch(\Exception $e){
                TualoApplication::result('msg', $e->getMessage());
            }
        },array('get','post'),true);

        Route::add('/currencyapi/historical/range/(?P<year>[\d\-\_]+)/(?P<currencies>[\w\-\_]+)',function($matches){
            TualoApplication::contenttype('application/json');
            try{



                $x = [];
                $date = new \DateTimeImmutable($matches['year'].'-01-01');
                $interval = new \DateInterval('P1D');

                for ($i=0;$i<366;$i++){
                    

                    // $x[] = $date->format('Y-m-d');
                    $date = $date->add($interval);
                    
                    $result = API::getDate(
                        strtotime($date->format('Y-m-d')),
                        'EUR',
                        explode('-',$matches['currencies'])
                    );

                    $sql ='insert into currencyapi_historical_day 
                    (base_currency,target_currency,`date`,rate) 
                    values ({base_currency},{target_currency},{date},{rate})
                    on duplicate key update rate=values(rate)';
                    foreach($result['data'] as $key=>$r){
                        TualoApplication::get('session')->getDB()->direct($sql,[
                            'base_currency'=>'EUR',
                            'target_currency'=>$r['code'],
                            'date'=>$date->format('Y-m-d'),
                            'rate'=>$r['value']
                        ]);

                    }
                    $x[]=$result;
                    
                }
                TualoApplication::result('time',time() );
                TualoApplication::result('data',$x );
                TualoApplication::result('success',true );

            }catch(\Exception $e){
                TualoApplication::result('msg', $e->getMessage());
            }
        },array('get','post'),true);
    }
}

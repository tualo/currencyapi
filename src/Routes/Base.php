<?php
namespace Tualo\Office\CurrencyAPI\Routes;
use Tualo\Office\Basic\TualoApplication;
use Tualo\Office\Basic\Route;
use Tualo\Office\Basic\IRoute;
use Tualo\Office\CurrencyAPI\API;


class Base implements IRoute{
    public static function register(){


        Route::add('/currencyapi/historical/(?P<start>[\w\-\_]+)/(?P<stop>[\w\-\_]+)//(?P<currencies>[\w\-\_]+)',function($matches){
            TualoApplication::contenttype('application/json');
            try{
                $result = API::getDateRange($matches['start'],$matches['stop'],'EUR',explode('-',$matches['currencies']));

                TualoApplication::result('time',time() );
                TualoApplication::result('data',$result );
                TualoApplication::result('success',true );

            }catch(\Exception $e){
                TualoApplication::result('msg', $e->getMessage());
            }
        },array('get','post'),true);
    }
}
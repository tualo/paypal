<?php

namespace Tualo\Office\Paypal\Middlewares;
use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\Basic\IMiddleware;

class Middleware implements IMiddleware{
    public static function register(){
        
        App::use('paypal',function(){
            try{
               
                
            }catch(\Exception $e){
                App::set('maintanceMode','on');
                App::addError($e->getMessage());
            }
        },200);
    }
}
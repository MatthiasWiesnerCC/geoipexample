<?php
require_once __DIR__.'/../vendor/autoload.php'; 

$app = new Silex\Application(); 

$app->get('/', function() use($app) { 
    return print_r(geoip_record_by_name('php.net'), true); 
});

$app->run(); 

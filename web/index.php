<?php
require_once __DIR__.'/../vendor/autoload.php'; 

$app = new Silex\Application(); 
$app['debug'] = true;

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

$app->get('/geoip/{domainName}', function ($domainName) use ($app) {
    return $app['twig']->render('geoip.twig', array(
        'data' => geoip_record_by_name($domainName),
    ));
})
->bind('geoip');

$app->get('/', function() use($app) { 
    return "Hello to geoip example - type in your browser http://geoipexample.cloudcontrolled.com/geoip/{domainToCheck}"; 
});

$app->run(); 

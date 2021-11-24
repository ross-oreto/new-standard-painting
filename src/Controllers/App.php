<?php

namespace Alex\Nsp\Controllers;

use Base;
use Oreto\F3Willow\Psr7\Psr7;
use Oreto\F3Willow\Routing\Routes;
use Oreto\F3Willow\Willow;

class App extends Willow {
    static function routes(): Routes {
        return Routes::create(self::class)
            ->GET("home", "/")->handler('index')
            ->GET("schedule", "/schedule")->handler('schedule')
            ->GET("address", "/address/@address")->handler('address')->ajax()
            ->build();
    }

    function schedule(Base $f3) {
        echo $this->render("schedule");
    }

    function address(Base $f3) {
        $address = urlencode($f3->get("PARAMS.address"));
        $curl = curl_init();
        $apiUrl = "https://api.openrouteservice.org/geocode/search";
        $region = "whosonfirst:region:85688701";

        $apiKey = $f3->get("openroute.apikey");
        curl_setopt_array($curl, array(
            CURLOPT_URL => "$apiUrl?api_key=$apiKey&text=$address&boundary.gid=$region&layers=address&size=3",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));
        $response = curl_exec($curl);
        $json = json_decode($response);

        if (sizeof($json->{'features'}) == 1) {
            header("Content-Type: ".Psr7::APPLICATION_JSON, true, 200);
            echo json_encode($json->{'features'}[0]);
        } else {
            header("Content-Type: text/plain", true, 400);
            echo Willow::dict("schedule.address.not.found");
        }
        curl_close($curl);
    }
}
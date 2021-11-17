<?php

namespace Alex\Nsp;

use Alex\Nsp\Routing\Routes;

class App extends Willow {
    static function routes(): Routes {
        return Routes::create(self::class)
            ->GET("home", "/")->handler('index')
            ->GET("schedule", "/schedule")->handler('schedule')
            ->build();
    }

    function schedule($f3) {
        echo $this->render("schedule", $f3);
    }
}
<?php

namespace Alex\Nsp;

class ErrorHandler {
    static function handle($f3) {
        echo match ($f3->get('ERROR.code')) {
            404 => self::handle404($f3),
            default => self::handle500($f3),
        };
    }

    static function handle404($f3): string {
        return \Template::instance()->render('views/_404.htm');
    }

    static function handle500($f3): string {
        $f3->set('error_code', $f3->get('ERROR.code'));
        $f3->set('error_message', $f3->get('ERROR.text'));
        $f3->set('error_trace', $f3->get('ERROR.trace'));
        return \Template::instance()->render('views/_500.htm');
    }
}
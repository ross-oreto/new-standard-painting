<?php

namespace Alex\Nsp\Controllers;

use Base;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\Extra\SpoofCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use Image;
use JetBrains\PhpStorm\ArrayShape;
use Oreto\F3Willow\Psr7\Psr7;
use Oreto\F3Willow\Routing\Routes;
use Oreto\F3Willow\Willow;
use SMTP;

class App extends Willow {
    static function routes(): Routes {
        return Routes::create(self::class)
            ->GET("home", "/")->handler('index')
            ->GET("address", "/address/@address")->handler('address')->ajax()
            ->GET("captcha", "/captcha")->handler('captcha')
            ->GET("validate_captcha", "/validate-captcha")->handler('validateCaptcha')->ajax()
            ->POST("estimate", "/")->handler('estimate')->ajax()
            ->GET("new", "/new")->handler('newEstimate')
            ->build();
    }

    function beforeRoute(Base $f3) {
        $lang = Willow::get("GET.lang", Willow::get("SESSION.lang"));
        if ($lang) {
           $f3->set('SESSION.lang', $lang);
           $f3->set('LANGUAGE', $lang);
        }
    }

    public function index(Base $f3) {
        $success = $f3->get("SESSION.schedule.success") != null;
        $f3->set("success", $success);
        echo $this->render("home");
    }

    function captcha(Base $f3) {
        $img = new Image();
        $img->captcha('captcha.ttf',25,5,'SESSION.captcha_code');
        $img->render();
    }

    private static function validCaptcha(Base $f3, string|null $code = null): bool {
        return strcasecmp($f3->get("SESSION.captcha_code")
            , $code == null ? $f3->get('POST.captcha') : $code) == 0;
    }
    function validateCaptcha(Base $f3) {
        $valid = self::validCaptcha($f3, $f3->get('GET.captcha'));
        header("Content-Type: text/plain", true, 200);
        echo $valid ? "true" : Willow::dict("schedule.captcha.mismatch");
    }

    function address(Base $f3) {
        $address = urlencode($f3->get("PARAMS.address"));
        $curl = curl_init();
        $apiUrl = "https://geocode.search.hereapi.com/v1/geocode";

        $apiKey = $f3->get("here.apikey");
        curl_setopt_array($curl, array(
            CURLOPT_URL => "$apiUrl?apiKey=$apiKey&q=$address&limit=3",
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

        if (sizeof($json->{'items'}) == 1) {
            $item = $json->{'items'}[0];
            $f3->set("SESSION.address", $item->address->label);
            $f3->set("SESSION.position", $item->position);
            header("Content-Type: ".Psr7::APPLICATION_JSON, true, 200);
            echo json_encode($item);
        } else {
            header("Content-Type: text/plain", true, 400);
            echo Willow::dict("schedule.address.not.found");
        }
        curl_close($curl);
    }

    #[ArrayShape(['minutes' => "float", 'miles' => "float"])]
    protected function findRoute(Base $f3, string $destination): array {
        $curl = curl_init();
        $apiUrl = "https://router.hereapi.com/v8/routes";

        $apiKey = $f3->get("here.apikey");
        curl_setopt_array($curl, array(
            CURLOPT_URL => "$apiUrl?apiKey=$apiKey&origin=36.17369,-86.76352&destination=$destination&transportMode=car&return=summary",
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

        $routes = $json->{'routes'} ?? null;
        if ($routes && sizeof($routes) > 0) {
            $route = $routes[0]?->sections[0];
            // convert seconds to minutes
            $minutes = round($route?->summary?->duration / 60, 1);
            // convert meters to miles
            $miles = round($route?->summary?->length / 1609.344, 1);
            return array('minutes' => $minutes, 'miles' => $miles);
        } else {
            return array('minutes' => null, 'miles' => null);
        }
    }

    function estimate(Base $f3) {
        $form = $f3->get('POST');
        $errors = $this->validation($f3, $form);

        if (sizeof($errors) > 0) {
            header("Content-Type: ".Psr7::APPLICATION_JSON, true, 400);
            echo json_encode($errors);
        } else {
            $route = $this->findRoute($f3, $form['geocode']);
            // everything is okay, send emails.
            if ($this->sendEmail($f3, $form, $route)) {
                $f3->set('SESSION.schedule.success', $form);
                $note1 = Willow::dict('schedule.success.notification1', $form['name']);
                $note2 = Willow::dict('schedule.success.notification2', $form['email']);
                $note3 = Willow::dict('schedule.success.notification3');
                $newDescription = Willow::dict('schedule.success.new.description');
                $new = Willow::dict('schedule.success.new.button');
                $notification = "<div class='notification is-success'>
<p>$note1</p>
<p>$note2</p>
<p>$note3</p>
<p>
    $newDescription
</p>
<div class='pt-3'>
    <a class='button is-link' href='/new'>$new</a>
</div>
</div>";
                if ($route['miles'] && $route['miles'] > 35) {
                   $warning = Willow::dict('schedule.success.notification.warning', $form['address']);
                   $notification = $notification."<div class='notification is-warning'>
<p>$warning</p>
</div>";
                }
                echo $notification;
            } else {
                header("Content-Type: ".Psr7::APPLICATION_JSON, true, 500);
                $this->log->error("smtp send failed");
                echo json_encode(array("error", "smtp send failed"));
            }
        }
    }

    public function newEstimate(Base $f3) {
        $f3->set("SESSION.schedule.success", null);
        $f3->reroute('@home');
    }

    protected function sendEmail(Base $f3, array $form, array $route): bool {
//        $mode = self::isProd() ? '' : " ** ".self::get("mode", "dev");
//        $conf = $f3->get("smtp");
//        $smtp = $this->getSmtp($f3, $conf);
//        $smtp->set('Content-Type', "text/html; charset=UTF-8");
//        $smtp->set("To", "\"Alex Riegling\" <${conf['user']}>");
//        $smtp->set("From", "\"newstandardpainting.com\" <${conf['user']}>");
//        $smtp->set('Subject', "New estimate requested for ${form['name']}$mode");
//        $ok = $smtp->send($this->createInternalEmail($form, $route));
//
//        $smtp->set("To", "\"${form['name']}\" <${form['email']}>");
//        $smtp->set("From", "\"newstandardpainting.com\" <${conf['user']}>");
//        $smtp->set('Subject', Willow::dict("schedule.success.email.subject", $form['address']).$mode);
//        return $ok && $smtp->send($this->createExternalEmail($form));
//
        return true;
    }

    protected function getSmtp(Base $f3, array|null $conf = null): SMTP {
        $conf = $conf == null ? $f3->get("smtp") : $conf;
        return new SMTP ( $conf['host'], $conf['port'], $conf['scheme'], $conf['user'], $conf['pass'] );
    }

    protected function createExternalEmail(array $form): string {
        $greeting = Willow::dict("schedule.success.email.greeting", $form['name']);
        $header = Willow::dict("schedule.success.email.header");
        $body = Willow::dict("schedule.success.email.body");
       return "<html lang='en'><body>
<p>${greeting}</p>
<h3>${header}</h3>
<p>${body}</p>
</body></html>";
    }

    protected function createInternalEmail(array $form, array $route): string {
        $border = "style='border:1px solid black;'";
        $preferPhone = array_key_exists('contact', $form) ? 'yes' : 'no';
        $distance =  $route['miles']
            ? "${route['miles']} miles (${route['minutes']} minutes)"
            : "Unknown";
        return "<html lang='en'><body><table style='width: 100%;'>
  <tr>
    <td $border>Name</td>
    <td $border>${form['name']}</td>
  </tr>
  <tr>
    <td $border>Email</td> 
    <td $border>${form['email']}</td>
  </tr>
  <tr>
    <td $border>Address</td> 
    <td $border>${form['address']}</td>
  </tr>
  <tr>
    <td $border>Distance</td> 
    <td $border>${distance}</td>
  </tr>
  <tr>
    <td $border>Phone</td> 
    <td $border>${form['phone']}</td>
  </tr>
  <tr>
    <td $border>Prefers phone</td> 
    <td $border>$preferPhone</td>
  </tr>
  <tr>
    <td $border>Comments</td> 
    <td $border>${form['comments']}</td>
  </tr>
</table>
</body></html>";
    }

    protected function validation(Base $f3, array $form): array {
        $errors = array();

//        if (!self::validCaptcha($f3)) {
//            $errors['captcha'] = false;
//        }
        if (!$form['name']) {
            $errors['name'] = false;
        }
        if (!$form['address']) {
            $errors['address'] = false;
        }
//        else if ($form['address'] != $f3->get("SESSION.address")) {
//            $errors['address'] = false;
//        }
//        if (!$form['geocode']) {
//            $errors['address'] = false;
//        } else if ($form['geocode'] != $f3->get("SESSION.position")->lat.','.$f3->get("SESSION.position")->lng) {
//            $errors['address'] = false;
//        }
        if (!$form['email']) {
            $errors['email'] = Willow::dict("schedule.email.required");
        } else if ($form['email'] != $form['confirm-email']) {
            $errors['email'] = Willow::dict("schedule.email.match");
        } else {
            $validator = new EmailValidator();
            $multipleValidations = new MultipleValidationWithAnd([
                new RFCValidation()
                , new DNSCheckValidation()
                , new SpoofCheckValidation()
            ]);
            if (!$validator->isValid($form['email'], $multipleValidations)) {
                $errors['email'] = Willow::dict("schedule.email.invalid");
            }
        }
        if (!$form['phone'] && array_key_exists('contact', $form)) {
            $errors['phone'] = false;
        }
        return $errors;
    }
}
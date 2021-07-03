<?php

namespace App\Helpers;

use App\Models\Common\Company;
use App\Models\Common\RequestLog;
use App\Models\Common\Setting;
use Auth;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Log;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class Helper
{

    public static function getUsername(Request $request)
    {

        $username = "";

        if (isset($request->mobile)) {
            $username = 'mobile';
        } else if (isset($request->email)) {
            $username = 'email';
        }

        return $username;
    }

    public static function dateFormat($company_id = null)
    {
        $setting    = Setting::where('company_id', 1)->first();
        $settings   = json_decode(json_encode($setting->settings_data));
        $siteConfig = isset($settings->site->date_format) ? $settings->site->date_format : 0;
        if ('1' == $siteConfig) {
            return "d-m-Y H:i:s";
        } else {
            return "d-m-Y g:i A";
        }
    }

    public static function currencyFormat($value = '', $symbol = '')
    {
        if ("" == $value) {
            return $symbol . number_format(0, 2, '.', '');
        } else {
            return $symbol . number_format($value, 2, '.', '');
        }
    }

    public static function decimalRoundOff($value)
    {
        return number_format($value, 2, '.', '');
    }

    public static function qrCode($data, $file, $company_id, $path = 'qr_code/', $size = 500, $margin = 10)
    {
        //return true;
        $qrCode = new QrCode();
        $qrCode->setText($data);
        $qrCode->setSize($size);
        $qrCode->setWriterByName('png');
        $qrCode->setMargin($margin);
        $qrCode->setEncoding('UTF-8');
        $qrCode->setErrorCorrectionLevel(new ErrorCorrectionLevel(ErrorCorrectionLevel::HIGH));

        $qrCode->setRoundBlockSize(true);
        $qrCode->setValidateResult(false);
        $qrCode->setWriterOptions(['exclude_xml_declaration' => true]);
        $filePath = 'app/public/' . $company_id . '/' . $path;

        $filePath = 'app/public/' . $company_id . '/' . $path;

        if (!file_exists(app()->basePath('storage/' . $filePath))) {
            mkdir(app()->basePath('storage/' . $filePath), 0777, true);
        }

        $qrCode->writeFile(app()->basePath('storage/' . $filePath) . $file);

        return url() . '/storage/' . $company_id . '/' . $path . $file;
    }

    public static function log_file($user_log)
    {

        if (!empty(Auth::user())) {
            $user = Auth::user();
        }
        $datetime = \Carbon\Carbon::now();

        $organization      = Company::findOrFail($user->company_id);
        $organization_name = $organization->company_name;

        $path = $organization_name . '/logs/' . $datetime->format('Y-m-d');

        $path_array = explode('/', $path);

        $public_path = '';

        $public_path = 'app/public/' . $user->company_id . '/' . $path;
        if (!file_exists(storage_path($public_path))) {
            mkdir(storage_path($public_path), 0777, true);
        }

        $userFile = storage_path($public_path) . "/" . $user->id . ".txt";

        $logfile = fopen($userFile, "a+");

        if (trim(file_get_contents($userFile)) == false) {
            fwrite($logfile, json_encode($user_log));
        } else {
            fwrite($logfile, "||" . json_encode($user_log));
        }

        fclose($logfile);
    }

    public static function upload_file($picture, $path, $file = null, $company_id = null)
    {
        if (null == $file) {
            $file_name = time();
            $file_name .= rand();
            $file_name = sha1($file_name);

            $file = $file_name . '.' . $picture->getClientOriginalExtension();
        }

        if (!empty(Auth::user())) {
            $company_id = Auth::user()->company_id;
        }

        $path = $company_id . '/' . $path;

        if (!file_exists(app()->basePath('storage/app/public/' . $path))) {
            mkdir(app()->basePath('storage/app/public/' . $path), 0777, true);
        }

        return url() . '/storage/' . $picture->storeAs($path, $file);
    }

    public static function upload_providerfile($picture, $path, $file = null, $company_id = null)
    {
        if (null == $file) {
            $file_name = time();
            $file_name .= rand();
            $file_name = sha1($file_name);

            $file = $file_name . '.' . $picture->getClientOriginalExtension();
        }

        $path = ((null == $company_id) ? Auth::guard('provider')->user()->company_id : $company_id) . '/' . $path;

        if (!file_exists(app()->basePath('storage/app/public/' . $path))) {
            mkdir(app()->basePath('storage/app/public/' . $path), 0777, true);
        }

        return url() . '/storage/' . $picture->storeAs($path, $file);
    }

    public static function getGuard()
    {
        if (Auth::guard('admin')->check()) {
            return strtoupper("admin");
        } else if (Auth::guard('provider')->check()) {
            return strtoupper("provider");
        } else if (Auth::guard('user')->check()) {
            return strtoupper("user");
        } else if (Auth::guard('shop')->check()) {
            return strtoupper("shop");
        }
    }

    public static function curl($url)
    {
        // return $url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $return = curl_exec($ch);
        curl_close($ch);
        return $return;

        // $curl = curl_init();

        // curl_setopt_array($curl, array(
        //     CURLOPT_URL => $url,
        //     CURLOPT_RETURNTRANSFER => true,
        //     CURLOPT_ENCODING => "",
        //     CURLOPT_MAXREDIRS => 10,
        //     CURLOPT_TIMEOUT => 30,
        //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //     CURLOPT_CUSTOMREQUEST => "GET",
        //     CURLOPT_HTTPHEADER => array(
        //     "cache-control: no-cache",
        //     ),
        // ));

        // $response = curl_exec($curl);
        // $err = curl_error($curl);
        // curl_close($curl);

        // return $response;
    }

    public static function generate_booking_id($prefix)
    {
        return $prefix . mt_rand(100000, 999999);
    }

    public static function setting($company_id = null)
    {

        if (Auth::guard(strtolower(self::getGuard()))->user() != null) {
            $id = (null == $company_id) ? Auth::guard(strtolower(self::getGuard()))->user()->company_id : $company_id;
        } else {
            $id = 1;
        }
        $setting             = Setting::where('company_id', $id)->first();
        $settings            = json_decode(json_encode($setting->settings_data));
        $settings->demo_mode = $setting->demo_mode;
        return $settings;
    }

    public static function getAddress($latitude, $longitude)
    {

        if (!empty($latitude) && !empty($longitude)) {
            //Send request and receive json data by address
            $geocodeFromLatLong = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?latlng=' . trim($latitude) . ',' . trim($longitude) . '&sensor=false&key=' . config('constants.map_key'));
            $output             = getDistanceMap(trim($latitude), trim($longitude));
            $status             = $output->status;
            //Get address from json data
            $address = ("OK" == $status) ? $output->results[0]->formatted_address : '';
            //Return address of the given latitude and longitude
            if (!empty($address)) {
                return $address;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public static function getDistanceMap($source, $destination)
    {

        $settings   = Helper::setting();
        $siteConfig = $settings->site;

        $map = file_get_contents('https://maps.googleapis.com/maps/api/distancematrix/json?origins=' . implode('|', $source) . '&destinations=' . implode('|', $destination) . '&sensor=false&key=' . $siteConfig->server_key);
        return json_decode($map);
    }

    public static function getDistanceBetweenLocation($s_latitude, $s_longitude, $d_latitude, $d_longitude, $earthRadius = 6371)
    {

        return (6371 * acos(cos(deg2rad($s_latitude)) * cos(deg2rad($d_latitude)) * cos(deg2rad($d_longitude) - deg2rad($s_longitude)) + sin(deg2rad($s_latitude)) * sin(deg2rad($d_latitude))));
    }

    public static function my_encrypt($passphrase, $encrypt)
    {

        $salt = openssl_random_pseudo_bytes(128);
        $iv   = openssl_random_pseudo_bytes(16);
        //on PHP7 can use random_bytes() istead openssl_random_pseudo_bytes()
        //or PHP5x see : https://github.com/paragonie/random_compat

        $iterations = 999;
        $key        = hash_pbkdf2("sha1", $passphrase, $salt, $iterations, 64);

        $encrypted_data = openssl_encrypt($encrypt, 'aes-128-cbc', hex2bin($key), OPENSSL_RAW_DATA, $iv);

        $data = ["ciphertext" => base64_encode($encrypted_data), "iv" => bin2hex($iv), "salt" => bin2hex($salt)];

        return $data;
    }

    public static function encryptResponse($response = [])
    {

        $status       = !empty($response['status']) ? $response['status'] : 200;
        $title        = !empty($response['title']) ? $response['title'] : self::getStatus($status);
        $message      = !empty($response['message']) ? $response['message'] : '';
        $responseData = !empty($response['data']) ? self::my_encrypt('FbcCY2yCFBwVCUE9R+6kJ4fAL4BJxxjd', json_encode($response['data'])) : [];
        $error        = !empty($response['error']) ? $response['error'] : [];

        if ((401 != $status) && (405 != $status) && (422 != $status)) {
            RequestLog::create(['data' => json_encode([
                'request'                  => app('request')->request->all(),
                'response'                 => $message,
                'error'                    => $error,
                'responseCode'             => $status,
                $_SERVER['REQUEST_METHOD'] => $_SERVER['REQUEST_URI'] . " " . $_SERVER['SERVER_PROTOCOL'],
                'host'                     => $_SERVER['HTTP_HOST'],
                'ip'                       => $_SERVER['REMOTE_ADDR'],
                'user_agent'               => $_SERVER['HTTP_USER_AGENT'],
                'date'                     => \Carbon\Carbon::now()->format('Y-m-d H:i:s')])]);
        }

        return response()->json(['statusCode' => (string) $status, 'title' => $title, 'message' => $message, 'responseData' => $responseData, 'error' => $error], $status);
    }

    public static function getResponse($response = [])
    {

        $status       = !empty($response['status']) ? $response['status'] : 200;
        $title        = !empty($response['title']) ? $response['title'] : self::getStatus($status);
        $message      = !empty($response['message']) ? $response['message'] : '';
        $responseData = !empty($response['data']) ? $response['data'] : (object) [];
        $error        = !empty($response['error']) ? $response['error'] : [];

        if ((401 != $status) && (405 != $status) && (422 != $status)) {
            app('request')->request->remove('picture');
            app('request')->request->remove('file');
            app('request')->request->remove('vehicle_image');
            app('request')->request->remove('vehicle_marker');

            RequestLog::create(['data' => json_encode([
                'request'                  => app('request')->request->all(),
                'response'                 => $message,
                'error'                    => $error,
                'responseCode'             => $status,
                $_SERVER['REQUEST_METHOD'] => $_SERVER['REQUEST_URI'] . " " . $_SERVER['SERVER_PROTOCOL'],
                'host'                     => $_SERVER['HTTP_HOST'],
                'ip'                       => $_SERVER['REMOTE_ADDR'],
                'user_agent'               => $_SERVER['HTTP_USER_AGENT'],
                'date'                     => \Carbon\Carbon::now()->format('Y-m-d H:i:s')])]);
        }

        return response()->json(['statusCode' => (string) $status, 'title' => $title, 'message' => $message, 'responseData' => $responseData, 'error' => $error], $status);
    }

    public static function getStatus($code)
    {

        switch ($code) {
            case 200:
                return "OK";
                break;

            case 201:
                return "Created";
                break;

            case 204:
                return "No Content";
                break;

            case 301:
                return "Moved Permanently";
                break;

            case 400:
                return "Bad Request";
                break;

            case 401:
                return "Unauthorised";
                break;

            case 403:
                return "Forbidden";
                break;

            case 404:
                return "Not Found";
                break;

            case 405:
                return "Method Not Allowed";
                break;

            case 422:
                return "Unprocessable Entity";
                break;

            case 500:
                return "Internal Server Error";
                break;

            case 502:
                return "Bad Gateway";
                break;

            case 503:
                return "Service Unavailable";
                break;
        }
    }

    public static function delete_picture($picture)
    {
        $url = app()->basePath('storage/') . $picture;
        @unlink($url);
        return true;
    }

    public static function send_sms($companyId, $plusCodeMobileNumber, $smsMessage)
    {
        //  SEND OTP TO REGISTER MEMBER
        $settings     = json_decode(json_encode(Setting::where('company_id', $companyId)->first()->settings_data));
        $siteConfig   = $settings->site;
        $accountSid   = $siteConfig->sms_account_sid;
        $authToken    = $siteConfig->sms_auth_token;
        $twilioNumber = $siteConfig->sms_from_number;

        $client = new Client($accountSid, $authToken);
        // $tousernumber = '+17577932902';
        $tousernumber = $plusCodeMobileNumber;
        try {
            $client->messages->create(
                $tousernumber,
                [
                    "body" => $smsMessage,
                    "from" => $twilioNumber,
                    //   On US phone numbers, you could send an image as well!
                    //  'mediaUrl' => $imageUrl
                ]
            );
            Log::info('Message sent to ' . $plusCodeMobileNumber . 'from ' . $twilioNumber);
            return true;
        } catch (TwilioException $e) {
            Log::error(
                'Could not send SMS notification.' .
                ' Twilio replied with: ' . $e
            );
            return $e->getMessage();
        }
    }

    // public static function send_sms($companyId,$plusCodeMobileNumber, $smsMessage) {
    //     //  SEND OTP TO REGISTER MEMBER
    //     $settings = json_decode(json_encode(Setting::where('company_id',$companyId)->first()->settings_data));
    //     $siteConfig = $settings->site;
    //     $accountSid =$siteConfig->sms_account_sid;
    //     $authToken = $siteConfig->sms_auth_token;
    //     $twilioNumber = $siteConfig->sms_from_number;

    //     $client = new Client($accountSid, $authToken);
    //     // $tousernumber = '+17577932902';
    //     $tousernumber = $plusCodeMobileNumber ;
    //     try {
    //         $client->messages->create(
    //             $tousernumber,
    //             [
    //                 "body" => $smsMessage,
    //                 "from" => $twilioNumber
    //                 //   On US phone numbers, you could send an image as well!
    //                 //  'mediaUrl' => $imageUrl
    //             ]
    //         );
    //         Log::info('Message sent to ' . $plusCodeMobileNumber.'from '. $twilioNumber);
    //         return true;
    //     } catch (TwilioException $e) {
    //         Log::error(
    //             'Could not send SMS notification.' .
    //             ' Twilio replied with: ' . $e
    //         );
    //         return $e->getMessage();
    //     }

    // }

    public static function siteRegisterMail($user)
    {

        $settings = json_decode(json_encode(Setting::where('company_id', $user->company_id)->first()->settings_data));

        Mail::send('mails.welcome', ['user' => $user, 'settings' => $settings], function ($mail) use ($user, $settings) {
            $mail->from($settings->site->mail_from_address, $settings->site->mail_from_name);
            $mail->to($user->email, $user->first_name . ' ' . $user->last_name)->subject('Welcome');
        });

        return true;
    }

    public static function send_emails($templateFile, $toEmail, $subject, $data)
    {
        try {
            //dd($data['salt_key']);
            if (isset($data['salt_key'])) {
                $settings = json_decode(json_encode(Setting::where('company_id', $data['salt_key'])->first()->settings_data));
            } else {
                if (!empty(Auth::user())) {
                    $company_id = Auth::user()->company_id;
                } else if (!empty(Auth::guard('shop')->user())) {
                    $company_id = Auth::guard('shop')->user()->company_id;
                } else {
                }
                $settings = json_decode(json_encode(Setting::where('company_id', $company_id)->first()->settings_data));
            }
            $data['settings'] = $settings;
            $mail             = Mail::send("$templateFile", $data, function ($message) use ($data, $toEmail, $subject, $settings) {
                $message->from($settings->site->mail_from_address, $settings->site->mail_from_name);
                $message->to($toEmail)->subject($subject);
            });

            if (count(Mail::failures()) > 0) {
                throw new \Exception('Error: Mail sent failed!');
            } else {
                return true;
            }
        } catch (\Throwable $e) {
            dd($e);

            throw new \Exception($e->getMessage());
        }
    }

    public static function send_invoice_emails_job($templateFile, $toEmail, $subject, $data)
    {
        try {

            $settings = Helper::setting();
            $mail     = Mail::send($templateFile, ['data' => $data, 'settings' => $settings], function ($message) use ($data, $toEmail, $subject, $settings) {
                $message->to($toEmail)->subject($subject);
            });

            // dd(Mail::failures());

            if (count(Mail::failures()) > 0) {
                throw new \Exception('Error: Mail sent failed!');
            } else {
                return true;
            }
        } catch (\Throwable $e) {
            dd($e);

            throw new \Exception($e->getMessage());
        }
    }

    public static function send_service_invoice_emails_job($templateFile, $toEmail, $subject, $data)
    {
        try {

            $settings = Helper::setting();
            //dd($data['body']['user']);
            $mail = Mail::send($templateFile, ['data' => $data, 'settings' => $settings], function ($message) use ($data, $toEmail, $subject, $settings) {

                $message->to($toEmail)->subject($subject);
            });

            // dd(Mail::failures());

            if (count(Mail::failures()) > 0) {
                throw new \Exception('Error: Mail sent failed!');
            } else {
                return true;
            }
        } catch (\Throwable $e) {
            dd($e);

            throw new \Exception($e->getMessage());
        }
    }

    public static function send_order_invoice_emails_job($templateFile, $storeOrder, $subject, $data)
    {
        try {

            /*if(isset($data['salt_key'])){
            $settings = json_decode(json_encode(Setting::where('company_id',$data['salt_key'])->first()->settings_data));
            }else{
            if(!empty(Auth::user())){
            $company_id = Auth::user()->company_id;
            }
            else if(!empty(Auth::guard('shop')->user())){
            $company_id = Auth::guard('shop')->user()->company_id;
            }else{

            }
            $settings = json_decode(json_encode(Setting::where('company_id',$company_id)->first()->settings_data));
            }
            $data['settings'] = $settings;*/

            $settings = Helper::setting();
            //dd($data['body']->request->order_invoice->items);
            /*$setting = json_decode($settings);
            $siteConfig = $setting->site;*/
            $mail = Mail::send($templateFile, ['data' => $data, 'settings' => $settings, 'storeOrder' => $storeOrder], function ($message) use ($data, $storeOrder, $subject, $settings) {
                //$message->from($settings->site->mail_from_address, $settings->site->mail_from_name);
                $message->to($storeOrder->user->email)->subject($subject);
            });

            // dd(Mail::failures());

            if (count(Mail::failures()) > 0) {
                throw new \Exception('Error: Mail sent failed!');
            } else {
                return true;
            }
        } catch (\Throwable $e) {
            dd($e);

            throw new \Exception($e->getMessage());
        }
    }

    public static function send_emails_job($templateFile, $toEmail, $subject, $data)
    {
        try {

            $mail = Mail::send($templateFile, $data, function ($message) use ($data, $toEmail, $subject) {
                $message->from("dharhs", "Ampro");
                $message->to($toEmail)->subject($subject);
            });

            // dd(Mail::failures());

            if (count(Mail::failures()) > 0) {
                throw new \Exception('Error: Mail sent failed!');
            } else {
                return true;
            }
        } catch (\Throwable $e) {
            dd($e);

            throw new \Exception($e->getMessage());
        }
    }

    public static function getServices()
    {
        $services = [
            ['name' => 'Medicine', 'picture' => 'https://api.pega10x.com/storage/1/home_services/pills.png'],
            ['name' => 'Nutritions', 'picture' => 'https://api.pega10x.com/storage/1/home_services/plan.png'],
            ['name' => 'Lab', 'picture' => 'https://api.pega10x.com/storage/1/home_services/lab.png'],
            ['name' => 'Covid Essential', 'picture' => 'https://api.pega10x.com/storage/1/home_services/nurse.png'],
            ['name' => 'Our Products', 'picture' => 'https://api.pega10x.com/storage/1/home_services/package.png'],
            ['name' => 'Cashback Offer', 'picture' => 'https://api.pega10x.com/storage/1/home_services/cashback.png'],
            ['name' => 'Medical Device', 'picture' => 'https://api.pega10x.com/storage/1/home_services/healthcare-device.png'],
        ];
        return $services;
    }
}

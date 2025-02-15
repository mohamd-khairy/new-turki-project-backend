<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Http;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount([
            "type" => "service_account",
            "project_id" => "turkieshop-c8917",
            "private_key_id" => "fd39c1d9be682ab7e936241af45f3561344cb3be",
            "private_key" => "-----BEGIN PRIVATE KEY-----\nMIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDD5HysdpLUFYY5\nrpuO+IhlV0GcksCCmc/J95X25xI+Ovjb2Wmp4p5ygeJvMnnfGs2fffXPWeRGtOXw\n/EIirP68KXtCGjkXyiiGAVxLzgN36dNOWX9zuYhx1W1MSkcVrtKXnxk0OnJoER9G\nyA7PZTX5b5Qpbu7NWUCX9mYPUb0h/qLPSvvwYr8vAYunUvpiedB95SkCetGo6y6A\nIWuNbGWXd26doPG17wImJGobPZn/aSRLwHyDFERBj1me3gU4dkQPp+5UfqEB4niV\nJO3x6KWKLITfeBwlc89dqWLC8ja0/0RBB/XhdV2tOYBW1iX3oUwHB1Gf71w7JiuX\n/WWrZN+/AgMBAAECggEAWFpa3RKSAPRAWQ3m/aIdKtAjOLJ7/6vOK4Lu8bCg6s6A\nZfB2lvgujOkGLy8uBrG5IoGWd9JMgpOezoWIcsliD44KGPNo4tD8XAyLC2m86L3e\n34zATnrVDrq7lFhAHYh/VYGdxY/DACsQ10TuYR5+LKXlxpZRQO9Lkf7BY5FzY7wC\niROPPNC00uBdyHf49L5Yj5eTLu3QKgspHT9fUZiI1Ux91IVwQVCZOC/hHPi+EXQM\n1tPfUeKhMyxDWeIYYj6XIfO/lrVxgucNdNjJCOdmym+Whzr++KejLqCZN4vhhsqA\nFOTHQdCs8ElO3YCyCsajP/t2sAwWsX8WlKA7x2J9aQKBgQDqw9l4OhaZ16/5Svzd\nqL3YEKcQVqIs0U5+/jx6MH3U2GjtztYYMzgcXwi/y4ZOZAXrPE8rPcVk2GSvXluE\nowdtUb8V+UZn7gqLApTuqmZ9R8eM+8M80yM0OqGX8E9tXKWFn3Nqf1T7zbbjvhl9\nBHv7xtgrxjvFHYV6D/MtqIAtqwKBgQDVnIRdsoErYZRxicxX+bdrwtWkbHnylw+7\ncmek9YZq3bifgnjyp5NObRsVGTLQKA2BhmYrgPaKw2yEG/d2RBVwvZ/xnY9Ubvl8\nIuGiKasxrMUTZ+pXZ4c2eMx33Hk5Mz+FdXbR4DVwQAW04fmkvJPrGQGCVT9gFu9X\nEs1orcP6PQKBgGkLMOdGto4noCmfj/1uX2OqL9ZzrST4knLoNw9FW0g9fNXLUqiJ\nYnXvX+7RlkrFHpDe712dyhERchu10KVMfSpBBYtDemlObZE9mn4f6LPtxjAjBnzU\nzE+2XE+ryx5X8ggUDIR+bPwuU8MbcDQsKX3Cvz72+A9+4hZ3xIuNdaz5AoGAUYwF\n8CskKqZ+3/VGIFPBlQ71Nmb/CwBmTh33uT7OCOAKCkLp32Df2HHIg/5xqouP1GG1\ngWgjNogyViDDENAfC0Io3DlVLVuMPLqoPpr/suANAEKMcL+iG8Zz8FInqRGKb22P\nZcHdRLP8ObiG8D/ZjEeojtPydMFsr2YLKqojhdUCgYAl4B7LYlMgcluKpERb3PUs\nDT2l61DVELGI8vax8aXDvjK1zOGhDh2GlykOFntVJiEORsxvZyStNgVYVh/zJPbj\n1hT3lx35KduOC2tgnRe22jagNzsX0+WIvMlMNjnGLgMbKBPpehY5D8gSYW0FcVwg\nkuC+8WvSouIXpiN2qhCLcw==\n-----END PRIVATE KEY-----\n",
            "client_email" => "firebase-adminsdk-0v51v@turkieshop-c8917.iam.gserviceaccount.com",
            "client_id" => "111505459015040569863",
            "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
            "token_uri" => "https://oauth2.googleapis.com/token",
            "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
            "client_x509_cert_url" => "https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-0v51v%40turkieshop-c8917.iam.gserviceaccount.com",
            "universe_domain" => "googleapis.com"
        ]);
        $this->messaging = $factory->createMessaging();
    }

    public function sendFcmNotification($deviceToken, $title, $body, $data = [])
    {
        $serverKey = 'AAAAg5KibHs:APA91bH3MCxkfFwNzvk46bk4hPPmsQcnRas2549F9K7IxWARe9IC-liRLsTG4PRVprp1MqGdX89YI1YU6KJDXkELxHQH2zq6vJWiUjKm1TmXa1ZTC0o8qnYBkkvLUv2SigpitRsO2eL9'; // Replace with your Firebase Server Key

        $url = 'https://fcm.googleapis.com/fcm/send';

        $payload = [
            'to' => $deviceToken, // Device token
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default', // Sound for both Android and iOS
            ],
            'data' => [],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'key=' . $serverKey,
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

        dd($response->json());
        // return $response->json();
    }



    public function sendNotification($deviceToken, $title, $body, $data = [])
    {
        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData(is_array($data) ? $data : [])
            ->withAndroidConfig([
                'notification' => [
                    'sound' => 'default', // Sound for Android
                ],
            ])
            ->withApnsConfig([
                'payload' => [
                    'aps' => [
                        'sound' => 'default', // Sound for iOS
                    ],
                ],
            ]);

        return $this->messaging->send($message);
    }
}

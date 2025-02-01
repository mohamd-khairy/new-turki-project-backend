<?php

return [
    /**
     * ------------------------------------------------------------------------
     * Credentials / Service Account
     * ------------------------------------------------------------------------
     *
     * In order to access a Firebase project and its related services using a
     * server SDK, requests must be authenticated. For server-to-server
     * communication this is done with a Service Account.
     *
     * If you don't already have generated a Service Account, you can do so by
     * following the instructions from the official documentation pages at
     *
     * https://firebase.google.com/docs/admin/setup#initialize_the_sdk
     *
     * Once you have downloaded the Service Account JSON file, you can use it
     * to configure the package.
     *
     * If you don't provide credentials, the Firebase Admin SDK will try to
     * autodiscover them
     *
     * - by checking the environment variable FIREBASE_CREDENTIALS
     * - by checking the environment variable GOOGLE_APPLICATION_CREDENTIALS
     * - by trying to find Google's well known file
     * - by checking if the application is running on GCE/GCP
     *
     * If no credentials file can be found, an exception will be thrown the
     * first time you try to access a component of the Firebase Admin SDK.
     *
     */
    'credentials' => [
        "type" =>"service_account",
        "project_id" =>"turkieshop-c8917",
        "private_key_id" =>"fd39c1d9be682ab7e936241af45f3561344cb3be",
        "private_key" =>"-----BEGIN PRIVATE KEY-----\nMIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDD5HysdpLUFYY5\nrpuO+IhlV0GcksCCmc/J95X25xI+Ovjb2Wmp4p5ygeJvMnnfGs2fffXPWeRGtOXw\n/EIirP68KXtCGjkXyiiGAVxLzgN36dNOWX9zuYhx1W1MSkcVrtKXnxk0OnJoER9G\nyA7PZTX5b5Qpbu7NWUCX9mYPUb0h/qLPSvvwYr8vAYunUvpiedB95SkCetGo6y6A\nIWuNbGWXd26doPG17wImJGobPZn/aSRLwHyDFERBj1me3gU4dkQPp+5UfqEB4niV\nJO3x6KWKLITfeBwlc89dqWLC8ja0/0RBB/XhdV2tOYBW1iX3oUwHB1Gf71w7JiuX\n/WWrZN+/AgMBAAECggEAWFpa3RKSAPRAWQ3m/aIdKtAjOLJ7/6vOK4Lu8bCg6s6A\nZfB2lvgujOkGLy8uBrG5IoGWd9JMgpOezoWIcsliD44KGPNo4tD8XAyLC2m86L3e\n34zATnrVDrq7lFhAHYh/VYGdxY/DACsQ10TuYR5+LKXlxpZRQO9Lkf7BY5FzY7wC\niROPPNC00uBdyHf49L5Yj5eTLu3QKgspHT9fUZiI1Ux91IVwQVCZOC/hHPi+EXQM\n1tPfUeKhMyxDWeIYYj6XIfO/lrVxgucNdNjJCOdmym+Whzr++KejLqCZN4vhhsqA\nFOTHQdCs8ElO3YCyCsajP/t2sAwWsX8WlKA7x2J9aQKBgQDqw9l4OhaZ16/5Svzd\nqL3YEKcQVqIs0U5+/jx6MH3U2GjtztYYMzgcXwi/y4ZOZAXrPE8rPcVk2GSvXluE\nowdtUb8V+UZn7gqLApTuqmZ9R8eM+8M80yM0OqGX8E9tXKWFn3Nqf1T7zbbjvhl9\nBHv7xtgrxjvFHYV6D/MtqIAtqwKBgQDVnIRdsoErYZRxicxX+bdrwtWkbHnylw+7\ncmek9YZq3bifgnjyp5NObRsVGTLQKA2BhmYrgPaKw2yEG/d2RBVwvZ/xnY9Ubvl8\nIuGiKasxrMUTZ+pXZ4c2eMx33Hk5Mz+FdXbR4DVwQAW04fmkvJPrGQGCVT9gFu9X\nEs1orcP6PQKBgGkLMOdGto4noCmfj/1uX2OqL9ZzrST4knLoNw9FW0g9fNXLUqiJ\nYnXvX+7RlkrFHpDe712dyhERchu10KVMfSpBBYtDemlObZE9mn4f6LPtxjAjBnzU\nzE+2XE+ryx5X8ggUDIR+bPwuU8MbcDQsKX3Cvz72+A9+4hZ3xIuNdaz5AoGAUYwF\n8CskKqZ+3/VGIFPBlQ71Nmb/CwBmTh33uT7OCOAKCkLp32Df2HHIg/5xqouP1GG1\ngWgjNogyViDDENAfC0Io3DlVLVuMPLqoPpr/suANAEKMcL+iG8Zz8FInqRGKb22P\nZcHdRLP8ObiG8D/ZjEeojtPydMFsr2YLKqojhdUCgYAl4B7LYlMgcluKpERb3PUs\nDT2l61DVELGI8vax8aXDvjK1zOGhDh2GlykOFntVJiEORsxvZyStNgVYVh/zJPbj\n1hT3lx35KduOC2tgnRe22jagNzsX0+WIvMlMNjnGLgMbKBPpehY5D8gSYW0FcVwg\nkuC+8WvSouIXpiN2qhCLcw==\n-----END PRIVATE KEY-----\n",
        "client_email" =>"firebase-adminsdk-0v51v@turkieshop-c8917.iam.gserviceaccount.com",
        "client_id" =>"111505459015040569863",
        "auth_uri" =>"https://accounts.google.com/o/oauth2/auth",
        "token_uri" =>"https://oauth2.googleapis.com/token",
        "auth_provider_x509_cert_url" =>"https://www.googleapis.com/oauth2/v1/certs",
        "client_x509_cert_url" =>"https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-0v51v%40turkieshop-c8917.iam.gserviceaccount.com",
        "universe_domain" =>"googleapis.com"
    ],

    // 'credentials' => [
    //     'file' => env('FIREBASE_CREDENTIALS'),

    //     /**
    //      * If you want to prevent the auto discovery of credentials, set the
    //      * following parameter to false. If you disable it, you must
    //      * provide a credentials file.
    //      */
    //     'auto_discovery' => true,
    // ],

    /**
     * ------------------------------------------------------------------------
     * Firebase Realtime Database
     * ------------------------------------------------------------------------
     */

    'database' => [

        /**
         * In most of the cases the project ID defined in the credentials file
         * determines the URL of your project's Realtime Database. If the
         * connection to the Realtime Database fails, you can override
         * its URL with the value you see at
         *
         * https://console.firebase.google.com/u/1/project/_/database
         *
         * Please make sure that you use a full URL like, for example,
         * https://my-project-id.firebaseio.com
         */
        'url' => env('FIREBASE_DATABASE_URL'),

    ],

    'dynamic_links' => [

        /**
         * Dynamic links can be built with any URL prefix registered on
         *
         * https://console.firebase.google.com/u/1/project/_/durablelinks/links/
         *
         * You can define one of those domains as the default for new Dynamic
         * Links created within your project.
         *
         * The value must be a valid domain, for example,
         * https://example.page.link
         */
        'default_domain' => env('FIREBASE_DYNAMIC_LINKS_DEFAULT_DOMAIN')
    ],

    /**
     * ------------------------------------------------------------------------
     * Firebase Cloud Storage
     * ------------------------------------------------------------------------
     */

    'storage' => [

        /**
         * Your project's default storage bucket usually uses the project ID
         * as its name. If you have multiple storage buckets and want to
         * use another one as the default for your application, you can
         * override it here.
         */

        'default_bucket' => env('FIREBASE_STORAGE_DEFAULT_BUCKET'),

    ],

    /**
     * ------------------------------------------------------------------------
     * Caching
     * ------------------------------------------------------------------------
     *
     * The Firebase Admin SDK can cache some data returned from the Firebase
     * API, for example Google's public keys used to verify ID tokens.
     *
     */

    'cache_store' => env('FIREBASE_CACHE_STORE', 'file'),

    /**
     * ------------------------------------------------------------------------
     * Logging
     * ------------------------------------------------------------------------
     *
     * Enable logging of HTTP interaction for insights and/or debugging.
     *
     * Log channels are defined in config/logging.php
     *
     * Successful HTTP messages are logged with the log level 'info'.
     * Failed HTTP messages are logged with the the log level 'notice'.
     *
     * Note: Using the same channel for simple and debug logs will result in
     * two entries per request and response.
     */

    'logging' => [
        'http_log_channel' => env('FIREBASE_HTTP_LOG_CHANNEL', null),
        'http_debug_log_channel' => env('FIREBASE_HTTP_DEBUG_LOG_CHANNEL', null),
    ],

    /**
     * ------------------------------------------------------------------------
     * Debug (deprecated)
     * ------------------------------------------------------------------------
     *
     * Enable debugging of HTTP requests made directly from the SDK.
     */
    'debug' => env('FIREBASE_ENABLE_DEBUG', false),
];

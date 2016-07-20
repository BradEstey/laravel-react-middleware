<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP Request Timeouts
    |--------------------------------------------------------------------------
    |
    | These options set the maximum amount of time to wait for a resonse
    | from your Node.js application. The "connect_timeout" key determines
    | how long to wait for a connection to the server. The "timeout" key
    | is the maximum amount of time to wait for the response. Timeouts are
    | set to 0 by default, which waits indefinitely. I highly recommend that
    | you change these to be more aggressive.
    |
    */

    'timeout' => 0,
    'connect_timeout' => 0,

    /*
    |--------------------------------------------------------------------------
    | Node.js Application Location
    |--------------------------------------------------------------------------
    |
    | Use the two variables below to set where your Node.js application
    | is located in relation to this application server.
    |
    */

    'host' => 'http://localhost',
    'port' => 3000,
    
];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GitHub Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for GitHub API integration
    |
    */

    'token' => env('GITHUB_TOKEN', null),
    
    /*
    |--------------------------------------------------------------------------
    | SSL Verification
    |--------------------------------------------------------------------------
    |
    | Whether to verify SSL certificates when making requests to GitHub API
    | Set to false for development environments with SSL issues
    |
    */
    'verify_ssl' => env('GITHUB_VERIFY_SSL', true),
];

<?php

return [
  "request_status" => [
      "PENDING" => "Pending",
      "SENT" => "Sent",
      "APPROVED" => "Approved"
  ],
  "assigned_status" => [
    "DEFAULT" => 0, 
    "WAITING" => 1, 
    "ACCEPT"  => 2,  
    "REJECT"  => 3,
    "WAITINGCHECKOUT" => 4, 
    "WAITINGCHECKIN" => 5, 
  ],
  "status_id" => [
    "PENDING" => 1,
    "BROKEN" => 3,
    "ASSIGN" => 4,
    "READY_TO_DEPLOY" => 5,
  ],
  "asset_history" => [
    "CHECK_IN_TYPE" => 1,
    "CHECK_OUT_TYPE" => 0
  ],
  "permission_status" => [
    "ALLOW" => 1,
    "REFUSE" => -1,
    "INHERITANCE" => 0,
  ],
  "seats" =>[
    "MIN" => 0,
  ],
  "status_tax_token" => [
    "NOT_ACTIVE" => 0,
    "ASSIGN" => 1
  ],
  "update_type" => [
    "DEFAULT" => 0,
    "ACCEPT_CHECKOUT" => 1,
    "ACCEPT_CHECKIN" => 2,
    "REJECT_CHECKOUT" => 3,
    "REJECT_CHECKIN" => 4
  ],
  "mail_type" => [
    "CHECKIN" => "checkin",
    "CHECKOUT" => "checkout"
  ],
  "release_notes" => [
    // Valid release types
    'valid_types' => ['ALL', 'BE', 'FE'],
    'default_type' => 'ALL',
    
    // Pagination settings
    'default_page' => 1,
    'default_page_size' => 5,
    'max_page_size' => 50,
    'min_page_size' => 1,
    
    // GitHub API fetch settings
    'fetch_base_limit' => 300,      // Default fetched items each repo
    'fetch_limit_min' => 200,
    'fetch_limit_max' => 300,
    'max_pages_to_fetch' => 5,
    
    // Cache settings
    'cache_duration_hours' => 1,    // Cache 1 hour
    'cache_key_prefix' => 'github_releases_', 
    
    // Input validation
    'invalid_string_values' => ['null', 'undefined', ''],
  ],
];
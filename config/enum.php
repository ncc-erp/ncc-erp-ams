<?php

return [
  "w2_request_type" => [
    "DEVICE" => "Device Request",
    "EQUIPMENT" => "Office Equipment Request",
  ],
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
];
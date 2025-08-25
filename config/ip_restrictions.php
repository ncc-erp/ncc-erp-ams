<?php

return [
    'hrm' => array_merge(
        array_filter(explode(',', env('HRM_ALLOWED_IPS', '')))
    ),
]; 
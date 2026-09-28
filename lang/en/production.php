<?php

return [
    'dashboard_retrieved' => 'Production dashboard retrieved successfully.',
    'machine_not_found' => 'Machine not found.',
    'machine_performance_retrieved' => 'Machine performance retrieved successfully.',
    'orders_retrieved' => 'Production order list retrieved successfully.',
    'result_created' => 'Production result created successfully.',
    'dataset_unavailable' => 'Production dataset has not been imported into manufacturing_test.',
    'validation' => [
        'failed' => 'Validation failed. Please review the provided data',
        'work_order_not_found' => 'The production order was not found.',
        'work_order_must_be_running' => 'The production order must have a status of :status.',
        'production_date_not_after_today' => 'The production date field must be a date before or equal to today.',
        'messages' => [
            'wo_number.required' => 'The production order field is required.',
            'wo_number.string' => 'The production order must be a string.',
            'wo_number.max' => 'The production order may not be greater than 20 characters.',
            'wo_number.exists' => 'The selected production order is invalid.',
            'production_date.required' => 'The production date field is required.',
            'production_date.date_format' => 'The production date field must match the format Y-m-d H:i:s.',
            'production_finish.date_format' => 'The production finish field must match the format Y-m-d H:i:s.',
            'production_finish.after_or_equal' => 'The production finish field must be a date after or equal to production date.',
            'qty_good.required' => 'The good quantity field is required.',
            'qty_good.integer' => 'The good quantity must be an integer.',
            'qty_good.min' => 'The good quantity must be at least 0.',
            'qty_reject.required' => 'The reject quantity field is required.',
            'qty_reject.integer' => 'The reject quantity must be an integer.',
            'qty_reject.min' => 'The reject quantity must be at least 0.',
            'runtime_minutes.integer' => 'The runtime minutes must be an integer.',
            'runtime_minutes.min' => 'The runtime minutes must be at least 0.',
        ],
    ],
];

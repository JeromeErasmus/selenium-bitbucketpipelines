<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 3/05/2016
 * Time: 1:54 PM
 */

/**
 * Permission types:
 *
 * any: **CAUTION** overrides all permissions if a method has this
 * single: allow for a single entity to be retrieved
 * list: allow for a collection to be retrieved
 * own: allow entity which is owned by the logged in user
 * own-admin: allow entity for which the logged in user is an admin of that entity
 *
 * agency: allow for job where the agency is the primary agency of that user
 * linked: allow for job where the agency is a linked agency of that user
 *
 * agency-comment: allow logged in user to access actions on agency comments
 * requirement-comment: allow logged in user to access actions on requirement comments
 *
 */

return [
    'allowedRoutes' => [
        'advertiser' => [
            'GET' => ['list'],
            'POST' => ['any'],
            'PATCH' => [],
            'DELETE' => []
        ],
        'chargeCode' => [
            'GET' => ['any'],
        ],
        'oasDashboard' => [
            'GET' => ['any'],
            'POST' => [],
            'PATCH' => [],
            'DELETE' => []
        ],
        'comment' => [
            'GET' => ['station'],
            'POST' => ['station'],
            'PATCH' => ['station']
        ],
        'country' => [
            'GET' => ['list'],
        ],
        'keyNumber' => [
            'GET' => ['all'],
            'POST' => [],
            'PATCH' => [],
        ],
        'job' => [
            'GET' => ['single','any'],
            'PATCH' => [],
            'DELETE' => [],
            'POST' => []
        ],
        'login' => [
            'GET' => ['own'],
        ],
        'network' => [
            'GET' => ['list', 'own'],
        ],
        'requirement' => [
            'GET' => ['any'],
        ],
        'tvcRequirement' => [
            'GET' => ['any'],
        ],
        'state' => [
            'GET' => ['any'],
        ]
    ],
    'entityNames' => [
        'job' => [
            'single' => 'id',
            'list' => []
        ],
        'keyNumber' => [
            'single' => 'jobId',
            'list' => []
        ],
        'country' => [
            'list' => []
        ],
        'advertiser' => [
            'list' => []
        ],
    ]
];


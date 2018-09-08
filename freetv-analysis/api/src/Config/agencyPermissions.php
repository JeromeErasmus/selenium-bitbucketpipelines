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
        'jobStatus' => [
            'GET' => ['any'],
            'PATCH' => ['any'],
        ],
        'advertiser' => [
            'GET' => ['list'],
            'POST' => ['any'],
            'PATCH' => [],
            'DELETE' => []
        ],
        'oasDashboard' => [
            'GET' => ['any'],
            'POST' => [],
            'PATCH' => [],
            'DELETE' => []
        ],
        'agency' => [
            'GET' => ['single', 'list', 'own', 'agency', 'linked'],
            'PATCH' => ['own-admin'],
            'POST' => ['any']
        ],
        'agencyGroup' => [
            'GET' => ['list']

        ],
        'agencyUser' => [
            'GET' => ['single','list','own', 'agency'],
            'POST' => ['agency'],
            'PATCH' => ['agency','own', 'unlink-own']
        ],
        'chargeCode' => [
            'GET' => ['any'],
        ],
        'comment' => [
            'GET' => ['single', 'list', 'own', 'linked', 'agency', 'requirement-comment','agency-comment'],
            'POST' => ['own', 'linked', 'agency', 'requirement-comment', 'agency-comment'],
            'PATCH' => ['own', 'linked', 'agency', 'requirement-comment', 'agency-comment'],
        ],
        'contact' => [
            'GET' => ['single', 'list', 'own','agency','linked'],
            'POST' => ['own-admin','agency','own','linked'],
            'PATCH' => ['own-admin','agency','own'],
            'DELETE' => ['own-admin','agency','own'],
        ],
        'country' => [
            'GET' => ['any'],
        ],
        'creditCardScheme' => [
            'GET' => ['list'],
        ],
        'document' => [
            'GET' => ['single','list','tvc-upload','agency-upload', 'own', 'agency'],
            'POST' => ['own', 'agency', 'linked', 'tvc-upload', 'agency-upload']
        ],
        'keyNumber' => [
            'GET' => ['single','list','own', 'agency','linked'],
            'POST' => ['own', 'linked', 'agency'],
            'PATCH' => ['own', 'linked', 'agency'],
        ],
        'job' => [
            'GET' => ['single','list','own','agency','linked', 'draft'],
            'PATCH' => ['own', 'agency', 'linked'],
            'DELETE' => ['own', 'agency'],
            'POST' => ['agency', 'linked']
        ],
        'jobDeclaration' => [
            'GET' => ['own'],
            'POST' => ['own'],
            'PATCH' => ['own'],
            'DELETE' => ['own']
        ],
        'jobRevision' => [
            'POST' => ['own', 'agency']
        ],
        'login' => [
            'GET' => ['own', 'agency']
        ],
        'network' => [
            'GET' => ['any'],
        ],
        'orderForm' => [
            'GET' => ['own']
        ],
        'payment' => [
            'GET' => ['list']
        ],
        'requirement' => [
            'GET' => ['single', 'list', 'own','agency','linked'],
        ],
        'tvcFormat' => [
            'GET' => ['list'],
        ],
        'tvcRequirement' => [
            'GET' => ['any'],
        ],
        'email' => [
            'POST' => ['any'],
        ],
        'state' => [
            'GET' => ['any'],
        ]
    ],
    'entityNames' => [
        'agencyUser' => [
            'single' => 'userId',
            'list' => ['agencyId']
        ],
        'job' => [
            'single' => 'id',
            'list' => []
        ],
        'country' => [
            'list' => []
        ],
        'tvcformat' => [
            'list' => [],
        ],
    ]
];


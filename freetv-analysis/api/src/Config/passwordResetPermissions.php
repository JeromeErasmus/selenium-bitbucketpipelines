<?php
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
        'agencyUser' => [
            'GET' => ['single'],
            'PATCH' => ['agenciesToLink','password','agencyId']
        ],
        'agency' => [
            'GET' => ['single']
        ],
    ],
    'entityNames' => [
        'agencyUser' => [
            'single' => 'userId',
            'list' => ['agencyId']
        ],
    ]
];


<?php

//get config data from db
global $DB, $CFG;
$host = $DB->get_record('config', ['name' => 'block_walletsend_domain_url'])->value;
$xapikey = $DB->get_record('config', ['name' => 'block_walletsend_api_key'])->value;
$connectoraddress = $DB->get_record('config', ['name' => 'block_walletsend_connector_address'])->value;

$id = createConnectorAttribute($host, $xapikey, $connectoraddress);

$validatedItems = [
    [
        "@type" => "RequestItemGroup",
        "mustBeAccepted" => true,
        "title" => "Shared Attributes",
        "items" => [
            [
                "@type" => "ShareAttributeRequestItem",
                "mustBeAccepted" => true,
                "attribute" => [
                    "@type" => "IdentityAttribute",
                    "owner" => $connectoraddress,
                    "value" => [
                        "@type" => "DisplayName",
                        "value" => "Trainspot2 THL Test Connector"
                    ]
                ],
                "sourceAttributeId" => $id
            ]
        ]
    ],
    [
        "@type" => "RequestItemGroup",
        "mustBeAccepted" => true,
        "title" => "Requested Attributes",
        "items" => [

            [
                "@type" => "ReadAttributeRequestItem",
                "mustBeAccepted" => true,
                "query" => [
                    "@type" => "IdentityAttributeQuery",
                    "valueType" => "GivenName"
                ]
            ],
            [
                "@type" => "ReadAttributeRequestItem",
                "mustBeAccepted" => true,
                "query" => [
                    "@type" => "IdentityAttributeQuery",
                    "valueType" => "Surname"
                ]
            ],
            [
                "@type" => "ReadAttributeRequestItem",
                "mustBeAccepted" => true,
                "query" => [
                    "@type" => "IdentityAttributeQuery",
                    "valueType" => "EMailAddress"
                ]
            ]

        ]
    ]
];

global $relationshipData;
$relationshipData = [
    "maxNumberOfAllocations" => 1,
    "expiresAt" => "2024-12-31T00:00:00.000Z",
    "content" => [
        "@type" => "RelationshipTemplateContent",
        "title" => "Connector  Contact",
        "onNewRelationship" => [
            "items" => $validatedItems
        ]
    ]
];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default WhatsApp Provider
    |--------------------------------------------------------------------------
    |
    | The key of the active provider. Must exist in the `providers` map
    | below. Controls which class WhatsAppManager::gateway() resolves.
    |
    | Unlike config/payment.php's `default_gateway`, this has NO implicit
    | default value here ("mock" or otherwise). If WHATSAPP_PROVIDER is not
    | set, WhatsAppManager throws WhatsAppConfigurationException rather than
    | silently resolving to the mock provider -- see WhatsAppManager's
    | docblock for why (MockWhatsAppGateway fakes a successful send, so a
    | silent default here could make a misconfigured production environment
    | look like it sent a real message when it did not).
    |
    | Current options: 'mock' (set WHATSAPP_PROVIDER=mock explicitly to use it),
    |                  'meta' (requires the 'meta' credentials below to be set)
    |
    */
    'default_provider' => env('WHATSAPP_PROVIDER'),

    /*
    |--------------------------------------------------------------------------
    | Provider Class Map
    |--------------------------------------------------------------------------
    |
    | Maps provider keys (WHATSAPP_PROVIDER env value) to concrete
    | implementation classes. Each class must implement
    | App\WhatsApp\Contracts\WhatsAppGatewayInterface.
    |
    | No real credentials belong in this array itself -- MetaCloudApiGateway
    | reads its own access token/phone-number-ID/Graph version from the
    | 'meta' block below, at call time, not from anything stored here.
    |
    */
    'providers' => [
        'mock' => \App\WhatsApp\Gateways\MockWhatsAppGateway::class,
        'meta' => \App\WhatsApp\Gateways\MetaCloudApiGateway::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Meta WhatsApp Cloud API Credentials
    |--------------------------------------------------------------------------
    |
    | Read only by MetaCloudApiGateway, and only when 'default_provider' is
    | actually set to 'meta'. All three keys are required -- none has a
    | fallback value -- and MetaCloudApiGateway validates all three BEFORE
    | making any HTTP call (see its resolveConfig()), throwing
    | WhatsAppConfigurationException if any is missing.
    |
    | graph_version deliberately has NO guessed default (e.g. no "vXX.X"
    | placeholder). This codebase was inspected before adding this key and
    | does not pin a Meta Graph API version anywhere else, so hardcoding one
    | here would be an invented fact. Set WHATSAPP_META_GRAPH_VERSION
    | explicitly to the version shown in your Meta app dashboard
    | (e.g. "v21.0") when this provider is actually configured.
    |
    | No values are set here and none belong in this file -- provide them
    | via .env only. This phase does not set or edit .env.
    |
    */
    'meta' => [
        'access_token' => env('WHATSAPP_META_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_META_PHONE_NUMBER_ID'),
        'graph_version' => env('WHATSAPP_META_GRAPH_VERSION'),
    ],

];

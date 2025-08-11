<?php

namespace PHPFrista;

class StatusCode
{
    const OK                    = 'OK';
    const UNREGISTERED          = 'UNREGISTERED';
    const ALREADY_REGISTERED    = 'ALREADY_REGISTERED';
    const INVALID_ID            = 'INVALID_ID';
    const INVALID_ENCODING      = 'INVALID_ENCODING';
    const INVALID_IMAGE         = 'INVALID_IMAGE';
    const AUTH_FAILED           = 'AUTH_FAILED';
    const SERVER_UNREACHABLE    = 'SERVER_UNREACHABLE';
    const INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';
    const INTEGRATION_ERROR     = 'INTEGRATION_ERROR';
}

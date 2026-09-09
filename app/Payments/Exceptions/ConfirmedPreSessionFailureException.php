<?php

namespace App\Payments\Exceptions;

/**
 * Thrown by a gateway's createSession() when the failure is provably a
 * pre-session (pre-provider-contact) failure: no external checkout session
 * could possibly have been created, because the gateway never reached the
 * provider before failing (e.g. local configuration is missing/invalid).
 *
 * This is the semantic, type-based signal that PaymentSessionStarter uses to
 * distinguish a safe-to-release local failure from an ambiguous outcome
 * where a provider session might exist. Gateways MUST NOT throw this
 * exception for any failure that occurs during or after an actual network
 * call to the provider (timeouts, connection errors, non-2xx responses,
 * malformed responses) -- those remain indeterminate on purpose, so a
 * duplicate external checkout session is never risked.
 */
class ConfirmedPreSessionFailureException extends PaymentException {}

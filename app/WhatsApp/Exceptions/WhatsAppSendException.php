<?php

namespace App\WhatsApp\Exceptions;

/**
 * Thrown by a WhatsAppGatewayInterface implementation when it attempted to
 * actually send a document message to a provider and that attempt failed
 * (e.g. a real Meta Cloud API adapter's HTTP call fails or the provider
 * rejects the request).
 *
 * Concrete gateways should throw one of the two subclasses below rather
 * than this class directly, so a future orchestration service can
 * distinguish the two cases by type instead of parsing exception messages:
 *
 *   - WhatsAppConfirmedSendFailureException -- provably nothing was
 *     accepted by the provider (e.g. a non-2xx rejection). Safe to allow a
 *     fresh attempt later.
 *   - WhatsAppIndeterminateSendException -- ambiguous outcome (e.g. a
 *     connection timeout, or a 2xx response missing the expected id). Must
 *     NEVER be automatically retried or resent.
 *
 * This base class remains directly catchable (and, in principle, directly
 * throwable, matching how App\Payments\Exceptions\PaymentException is both
 * a base class and thrown directly elsewhere in this codebase) for any
 * caller that only needs to know "the send failed" without caring which
 * kind.
 *
 * Not thrown by MockWhatsAppGateway, which never performs real network I/O
 * and never fails a well-formed send. Not used for configuration/resolution
 * failures (see WhatsAppConfigurationException, which deliberately stays
 * outside this hierarchy -- a missing/invalid provider configuration is
 * detected before any send is even attempted, so it is not "a send that
 * failed" in the sense this class and its subclasses describe) or for
 * invalid message construction (see WhatsAppException, thrown directly by
 * WhatsAppDocumentMessage's constructor).
 */
class WhatsAppSendException extends WhatsAppException {}

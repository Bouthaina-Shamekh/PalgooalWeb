<?php

namespace App\WhatsApp\Exceptions;

/**
 * Thrown when a WhatsApp send attempt's outcome is AMBIGUOUS: the request
 * may or may not have reached/been processed by the provider before the
 * failure occurred (a connection timeout/drop after transmission may have
 * begun, or a successful-looking HTTP response missing the id needed to
 * confirm acceptance).
 *
 * Must NEVER be automatically retried or resent -- doing so risks a
 * duplicate delivery to the customer. A future orchestration service can
 * only resolve this through manual/admin reconciliation, mirroring how
 * App\Models\DomainProvisioningAttempt::STATUS_INDETERMINATE and
 * PaymentSessionStarter::recordIndeterminateFailure() are already handled
 * for the analogous situation in domain provisioning and payments.
 */
class WhatsAppIndeterminateSendException extends WhatsAppSendException {}

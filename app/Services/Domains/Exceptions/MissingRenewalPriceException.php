<?php

namespace App\Services\Domains\Exceptions;

/**
 * Thrown when DomainRenewalService cannot resolve a trusted, explicit
 * customer-facing renewal price (renew.sale) for a domain.
 *
 * TLD-3B — Strict Sale-Only Renewal Pricing: renewal quoting has no
 * fallback to cost, to register-action pricing, or to any hard-coded
 * value. This exception is the single, explicit failure signal used by
 * both the manual renewal flow and the auto-renewal batch job — never a
 * silent generic error, never a synthetic price.
 */
class MissingRenewalPriceException extends \RuntimeException {}

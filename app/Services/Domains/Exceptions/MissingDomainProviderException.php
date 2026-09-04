<?php

namespace App\Services\Domains\Exceptions;

/**
 * Thrown when DomainRenewalService cannot resolve a trusted renewal quote
 * because the domain itself has no trusted provider identity
 * (Domain.provider_id is null — an external/unmanaged domain), or the
 * provider that Domain.provider_id points to is missing or inactive.
 *
 * TLD-3D — Hybrid Provider Identity: managed renewal pricing never falls
 * back to resolving a provider by Domain.registrar's type string once
 * provider_id is the source of truth. A null provider_id is a legitimate,
 * intentional state for manually-added/external domains (reason:
 * domain_provider_missing) — it must fail safely here rather than silently
 * guessing a provider.
 *
 * Extends MissingRenewalPriceException so it is caught wherever the
 * existing "no trusted renewal price" failure path already is (the manual
 * renewal controller and the auto-renew batch job), without those call
 * sites needing to change.
 */
class MissingDomainProviderException extends MissingRenewalPriceException {}

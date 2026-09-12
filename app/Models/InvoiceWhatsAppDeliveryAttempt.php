<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InvoiceWhatsAppDeliveryAttempt — audit + idempotency record for every
 * deliberate "send this invoice PDF over WhatsApp" action.
 *
 * Phase 4 (WhatsApp Invoice Delivery) — schema/model only. No orchestration,
 * job, sending, or reconciliation logic lives here yet; this model is a
 * plain data record, mirroring App\Models\PaymentAttempt and
 * App\Models\DomainProvisioningAttempt in shape and intent.
 *
 * Status machine (approved design):
 *   pending           -- row claimed; not yet picked up by a queue worker
 *   processing        -- a job has started rendering/sending
 *   sent              -- provider ACCEPTED the message (provider_message_id
 *                        set). This means accepted-for-delivery, NOT
 *                        delivered/read -- no webhook/receipt tracking
 *                        exists yet, so this status must never be read as
 *                        anything stronger than "the provider took it."
 *   confirmed_failed  -- provably nothing was accepted by the provider
 *                        (pre-provider-contact failure, or a provider
 *                        response that explicitly rejected the request).
 *                        Safe for a fresh attempt to be created later.
 *   indeterminate     -- ambiguous outcome (e.g. a connection timeout, or a
 *                        2xx response missing the expected id) -- the
 *                        request MAY have reached the provider. Must NEVER
 *                        be automatically retried or resent; requires
 *                        future manual/admin reconciliation (not
 *                        implemented in this phase).
 *
 * FROZEN PDF ARTIFACT (Phase 4): document_hash and document_byte_length are
 * the real SHA-256 hex digest and real byte length of the ACTUAL PDF bytes
 * that were rendered once at claim() time and persisted to a private disk
 * at documentArtifactPath() -- never a fingerprint of unrelated timestamps.
 * execute() never re-renders; it loads the same frozen bytes from that
 * path, re-verifies they still match document_hash/document_byte_length,
 * and sends exactly those bytes. The artifact is intentionally NOT deleted
 * after a successful send (it is the only exact record of what was
 * transmitted) -- a retention/deletion policy is a separate, later concern.
 *
 * Security: failure_message and provider_response must only ever contain
 * sanitized data (no access token, no Authorization header, no raw
 * unparsed provider payload) -- see App\WhatsApp\Gateways\
 * MetaCloudApiGateway, whose WhatsAppSendException messages and
 * WhatsAppSendResult::raw are already verified (by that phase's own tests)
 * to never contain the access token. The frozen PDF artifact itself may
 * contain customer PII (name, address, phone, email) -- it must only ever
 * live on ARTIFACT_DISK (a private, non-public disk) and must never be
 * logged, exposed via a public URL, or served through any unauthenticated
 * route.
 *
 * @property int                                  $id
 * @property int|null                             $invoice_id
 * @property string                               $attempt_uuid
 * @property string                               $recipient_snapshot
 * @property string                               $normalized_recipient
 * @property string                               $document_filename
 * @property string                               $document_hash
 * @property int                                  $document_byte_length
 * @property string                               $provider
 * @property string|null                          $provider_message_id
 * @property string                               $status
 * @property string|null                          $failure_code
 * @property string|null                          $failure_message
 * @property array|null                           $provider_response
 * @property int|null                             $requested_by_user_id
 * @property \Illuminate\Support\Carbon|null      $started_at
 * @property \Illuminate\Support\Carbon|null      $finished_at
 * @property \Illuminate\Support\Carbon           $created_at
 * @property \Illuminate\Support\Carbon           $updated_at
 */
class InvoiceWhatsAppDeliveryAttempt extends Model
{
    // ── Status constants ──────────────────────────────────────────────────

    /** Row claimed; not yet picked up by a queue worker. */
    public const STATUS_PENDING = 'pending';

    /** A job has started rendering/verifying/sending. */
    public const STATUS_PROCESSING = 'processing';

    /** Provider accepted the message -- accepted-for-delivery, not delivered/read. */
    public const STATUS_SENT = 'sent';

    /** Provably nothing was accepted by the provider -- safe for a fresh attempt. */
    public const STATUS_CONFIRMED_FAILED = 'confirmed_failed';

    /** Ambiguous outcome -- the provider may have received the request. Never auto-retried. */
    public const STATUS_INDETERMINATE = 'indeterminate';

    // ── Frozen PDF artifact storage (Phase 4) ───────────────────────────────

    /**
     * Private Laravel filesystem disk storing every attempt's frozen PDF
     * artifact. Must never be a publicly served disk: config/filesystems.php
     * defines this disk's root as storage_path('app/private') with no "url"
     * key and no storage:link entry, unlike the "public" disk -- so nothing
     * written here is ever reachable by a public URL.
     */
    public const ARTIFACT_DISK = 'local';

    /**
     * Directory (within ARTIFACT_DISK) holding every attempt's frozen PDF,
     * one file per attempt named after its attempt_uuid.
     */
    protected const ARTIFACT_DIRECTORY = 'invoice-whatsapp-deliveries';

    /**
     * Deterministic, private storage path for a delivery attempt's frozen
     * PDF artifact, derived ONLY from its attempt_uuid -- e.g.
     * "invoice-whatsapp-deliveries/8f14e...-uuid....pdf". Deliberately never
     * built from customer/phone input (attempt_uuid is a server-generated
     * UUID), and deliberately not stored as its own database column: since
     * the path is fully derivable from attempt_uuid (already a column),
     * persisting it again would be redundant state that could drift.
     */
    public static function documentArtifactPathFor(string $attemptUuid): string
    {
        return self::ARTIFACT_DIRECTORY . '/' . $attemptUuid . '.pdf';
    }

    /**
     * This attempt's own frozen PDF artifact path. See
     * documentArtifactPathFor().
     */
    public function documentArtifactPath(): string
    {
        return self::documentArtifactPathFor($this->attempt_uuid);
    }

    // ── Model configuration ───────────────────────────────────────────────

    /**
     * Explicit override: Eloquent's default snake_case convention would
     * guess "invoice_whats_app_delivery_attempts" (Str::snake() splits
     * "WhatsApp" into "whats_app"), not the "invoice_whatsapp_delivery_
     * attempts" table name actually created by this phase's migration.
     */
    protected $table = 'invoice_whatsapp_delivery_attempts';

    protected $fillable = [
        'invoice_id',
        'attempt_uuid',
        'recipient_snapshot',
        'normalized_recipient',
        'document_filename',
        'document_hash',
        'document_byte_length',
        'provider',
        'provider_message_id',
        'status',
        'failure_code',
        'failure_message',
        'provider_response',
        'requested_by_user_id',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'document_byte_length' => 'integer',
        'provider_response' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
}

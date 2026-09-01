<?php

namespace Tests\Unit\Payments;

use App\Models\PaymentGateway;
use App\Payments\DTOs\WebhookEvent;
use App\Payments\Exceptions\PaymentException;
use App\Payments\Exceptions\WebhookVerificationException;
use App\Payments\Gateways\LahzaGateway;
use Tests\TestCase;

/**
 * Provider Contract Test — Lahza webhook signature verification.
 *
 * P1-13A2 (fix): LahzaGateway::verifyWebhook() previously computed
 * HMAC-SHA512, but Lahza's own documentation (docs.lahza.io/payments/webhooks)
 * — corroborated by an independent community integration package — specifies
 * HMAC-SHA256 over the exact raw request body, hex-encoded, sent in the
 * `x-lahza-signature` header. See P1-13A1/P1-13A2 reports.
 *
 * This test calls the REAL LahzaGateway::verifyWebhook() implementation —
 * nothing here is mocked. It is deliberately separate from the Feature tests
 * under tests/Feature/*.php (CheckoutPaymentTest, PaymentWebhookRaceTest,
 * PaymentExternalSuccessEvidenceTest, PaymentAttemptIdentityTest, etc.),
 * which mock PaymentGatewayInterface::verifyWebhook() entirely and therefore
 * only exercise PaymentWebhookController's branching logic — never the
 * actual cryptographic contract with Lahza. Those tests remain valid for
 * what they test; they simply do not (and were never meant to) prove
 * provider-signature compatibility.
 */
class LahzaGatewayWebhookSignatureTest extends TestCase
{
    private const WEBHOOK_SECRET = 'whsec_test_only_5f8a2e91c4';

    private const PAYLOAD = '{"event":"charge.success","data":{"id":"txn_test123","reference":"attempt-uuid-abc","amount":5000,"currency":"USD","status":"success"}}';

    private function gateway(bool $withSecret = true): LahzaGateway
    {
        $config = new PaymentGateway();
        $config->driver = 'lahza';
        $config->name = 'Lahza (test)';
        $config->mode = 'sandbox';

        if ($withSecret) {
            $config->webhook_secret = self::WEBHOOK_SECRET;
        }

        return new LahzaGateway($config);
    }

    public function test_valid_sha256_signature_is_accepted_by_the_real_implementation(): void
    {
        $signature = hash_hmac('sha256', self::PAYLOAD, self::WEBHOOK_SECRET);

        $event = $this->gateway()->verifyWebhook(self::PAYLOAD, $signature);

        $this->assertInstanceOf(WebhookEvent::class, $event);
        $this->assertSame(WebhookEvent::TYPE_PAYMENT_SUCCEEDED, $event->type);
        $this->assertSame('attempt-uuid-abc', $event->sessionId);
        $this->assertSame('txn_test123', $event->transactionId);
        $this->assertSame(5000, $event->amountCents);
        $this->assertSame('USD', $event->currency);
    }

    public function test_legacy_sha512_signature_is_rejected(): void
    {
        // Regression guard: the pre-P1-13A2 implementation used HMAC-SHA512.
        // A signature computed the OLD way must now be rejected, so the
        // codebase can never silently slide back to the wrong algorithm.
        $legacySignature = hash_hmac('sha512', self::PAYLOAD, self::WEBHOOK_SECRET);

        $this->expectException(WebhookVerificationException::class);

        $this->gateway()->verifyWebhook(self::PAYLOAD, $legacySignature);
    }

    public function test_correct_algorithm_with_wrong_secret_is_rejected(): void
    {
        $signature = hash_hmac('sha256', self::PAYLOAD, 'a-completely-different-secret');

        $this->expectException(WebhookVerificationException::class);

        $this->gateway()->verifyWebhook(self::PAYLOAD, $signature);
    }

    public function test_modified_payload_is_rejected_even_though_the_signature_was_valid_for_the_original(): void
    {
        $signature = hash_hmac('sha256', self::PAYLOAD, self::WEBHOOK_SECRET);

        // One byte changed: amount 5000 -> 5001. Same secret, same algorithm —
        // the signature now belongs to a different payload than the one
        // actually being verified.
        $modifiedPayload = str_replace('"amount":5000', '"amount":5001', self::PAYLOAD);
        $this->assertNotSame(self::PAYLOAD, $modifiedPayload);

        $this->expectException(WebhookVerificationException::class);

        $this->gateway()->verifyWebhook($modifiedPayload, $signature);
    }

    public function test_json_whitespace_formatting_changes_the_signature_no_normalization_occurs(): void
    {
        $compact = '{"a":1}';
        $spaced  = '{ "a": 1 }';

        $compactSignature = hash_hmac('sha256', $compact, self::WEBHOOK_SECRET);
        $spacedSignature  = hash_hmac('sha256', $spaced, self::WEBHOOK_SECRET);

        $this->assertNotSame($compactSignature, $spacedSignature);

        // Cross-check through the real implementation: the compact payload's
        // signature must NOT verify against the differently-formatted (but
        // JSON-equivalent) spaced payload. Proves the HMAC binds to exact
        // raw bytes, never a re-encoded/normalized form.
        $this->expectException(WebhookVerificationException::class);

        $this->gateway()->verifyWebhook($spaced, $compactSignature);
    }

    public function test_malformed_json_with_a_valid_signature_passes_verification_then_fails_to_parse(): void
    {
        $malformedPayload = '{not valid json';
        $signature = hash_hmac('sha256', $malformedPayload, self::WEBHOOK_SECRET);

        try {
            $this->gateway()->verifyWebhook($malformedPayload, $signature);
            $this->fail('Expected a PaymentException for malformed JSON after successful signature verification.');
        } catch (WebhookVerificationException $e) {
            $this->fail('Signature verification must succeed before JSON parsing is attempted, but got: ' . $e->getMessage());
        } catch (PaymentException $e) {
            // Current-contract behavior, unchanged by this phase: verification
            // passes (no WebhookVerificationException), and the failure is the
            // generic PaymentException raised once json_decode() returns non-array.
            $this->assertNotInstanceOf(WebhookVerificationException::class, $e);
        }
    }

    public function test_uppercase_hex_signature_is_currently_accepted_because_the_header_is_lowercased_before_comparison(): void
    {
        // Documents ACTUAL current behavior (not a claim about what Lahza sends).
        // LahzaGateway::verifyWebhook() calls strtolower($signatureHeader) before
        // hash_equals(), so an uppercase-hex header still verifies correctly today.
        $signature = hash_hmac('sha256', self::PAYLOAD, self::WEBHOOK_SECRET);
        $uppercaseSignature = strtoupper($signature);
        $this->assertNotSame($signature, $uppercaseSignature);

        $event = $this->gateway()->verifyWebhook(self::PAYLOAD, $uppercaseSignature);

        $this->assertInstanceOf(WebhookEvent::class, $event);
    }

    public function test_empty_signature_header_is_rejected(): void
    {
        $this->expectException(WebhookVerificationException::class);

        $this->gateway()->verifyWebhook(self::PAYLOAD, '');
    }

    public function test_empty_webhook_secret_is_rejected_before_any_hash_is_computed(): void
    {
        // Guard predates P1-13A2 and must remain unchanged by this phase.
        $signature = hash_hmac('sha256', self::PAYLOAD, self::WEBHOOK_SECRET);

        $this->expectException(WebhookVerificationException::class);

        $this->gateway(withSecret: false)->verifyWebhook(self::PAYLOAD, $signature);
    }
}

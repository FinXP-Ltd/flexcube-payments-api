<?php

namespace Finxp\Flexcube\Tests\Mocks;

class DefaultSigner implements Signer
{
    public function signatureHeaderName(): string
    {
        return 'Signature';
    }

    public function calculateSignature(array $payload, string $secret): string
    {
        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);

        return hash_hmac('sha256', $payloadJson, $secret);
    }

    public function validateHash(string $hash, string $calculatedHash): bool
    {
        return hash_equals($hash, $calculatedHash);
    }
}

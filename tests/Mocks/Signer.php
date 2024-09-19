<?php

namespace Finxp\Flexcube\Tests\Mocks;

interface Signer
{
    public function signatureHeaderName(): string;

    public function calculateSignature(array $payload, string $secret): string;

    public function validateHash(string $hash, string $calculatedHash): bool;
}

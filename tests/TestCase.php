<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @var array{private: string, public: string}|null
     */
    private static ?array $passportKeys = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurePassportKeysForTesting();
    }

    private function configurePassportKeysForTesting(): void
    {
        if (! class_exists(\Laravel\Passport\Passport::class)) {
            return;
        }

        if (self::$passportKeys === null) {
            $privateKey = \phpseclib3\Crypt\RSA::createKey(2048);
            $publicKey = $privateKey->getPublicKey();

            self::$passportKeys = [
                'private' => $privateKey->toString('PKCS8'),
                'public' => $publicKey->toString('PKCS8'),
            ];
        }

        config([
            'passport.private_key' => self::$passportKeys['private'],
            'passport.public_key' => self::$passportKeys['public'],
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Queue\Contracts;

use App\Models\Mongo\Message;

interface MessageLogWriterInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function debug(Message $message, string $event, array $context = []): void;

    /**
     * @param array<string, mixed> $context
     */
    public function info(Message $message, string $event, array $context = []): void;

    /**
     * @param array<string, mixed> $context
     */
    public function warning(Message $message, string $event, array $context = []): void;

    /**
     * @param array<string, mixed> $context
     */
    public function error(Message $message, string $event, array $context = []): void;
}

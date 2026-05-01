<?php

namespace App\Providers;

use App\Queue\Contracts\MessageQueueRecorderInterface;
use App\Queue\Contracts\MessageLogWriterInterface;
use App\Queue\Contracts\MessageStoreInterface;
use App\Queue\Contracts\QueueControlInterface;
use App\Events\WhatsApp\MessageDelivered;
use App\Events\WhatsApp\MessageFailed;
use App\Events\WhatsApp\MessageProcessing;
use App\Events\WhatsApp\MessageQueued;
use App\Events\WhatsApp\MessageReceived;
use App\Events\WhatsApp\MessageSent;
use App\Events\WhatsApp\MessageWaiting;
use App\Listeners\WhatsApp\DispatchOutgoingWebhook;
use App\Queue\DatabaseQueueControl;
use App\Queue\MongoMessageLogWriter;
use App\Queue\MongoMessageQueueRecorder;
use App\Queue\MongoMessageStore;
use App\Queue\RedisQueueControl;
use App\Repositories\Contracts\TenantRepository;
use App\Repositories\Eloquent\EloquentTenantRepository;
use App\Services\WhatsApp\Contracts\WhatsAppProviderInterface;
use App\Services\WhatsApp\Contracts\SessionEventRecorderInterface;
use App\Services\WhatsApp\FakeWhatsAppProvider;
use App\Services\WhatsApp\MongoSessionEventRecorder;
use App\Services\Webhooks\Contracts\WebhookEventStoreInterface;
use App\Services\Webhooks\Contracts\OutgoingWebhookLogStoreInterface;
use App\Services\Webhooks\MongoWebhookEventStore;
use App\Services\Webhooks\MongoOutgoingWebhookLogStore;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->bind(TenantRepository::class, EloquentTenantRepository::class);
        $this->app->bind(MessageQueueRecorderInterface::class, MongoMessageQueueRecorder::class);
        $this->app->bind(MessageStoreInterface::class, MongoMessageStore::class);
        $this->app->bind(MessageLogWriterInterface::class, MongoMessageLogWriter::class);
        $this->app->bind(WhatsAppProviderInterface::class, FakeWhatsAppProvider::class);
        $this->app->bind(SessionEventRecorderInterface::class, MongoSessionEventRecorder::class);
        $this->app->bind(WebhookEventStoreInterface::class, MongoWebhookEventStore::class);
        $this->app->bind(OutgoingWebhookLogStoreInterface::class, MongoOutgoingWebhookLogStore::class);
        $this->app->bind(QueueControlInterface::class, function (): QueueControlInterface {
            $whatsAppDriver = (string) config('whatsapp.queue.driver', 'default');
            $defaultQueueConnection = (string) config('queue.default', 'database');

            if ($whatsAppDriver === 'redis' || ($whatsAppDriver === 'default' && $defaultQueueConnection === 'redis')) {
                return $this->app->make(RedisQueueControl::class);
            }

            return $this->app->make(DatabaseQueueControl::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen([
            MessageQueued::class,
            MessageWaiting::class,
            MessageProcessing::class,
            MessageSent::class,
            MessageDelivered::class,
            MessageFailed::class,
            MessageReceived::class,
        ], DispatchOutgoingWebhook::class);
    }
}

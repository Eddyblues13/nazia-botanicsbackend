<?php

namespace Tests\Feature;

use App\Mail\NewsletterWelcome;
use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_subscribes_an_address_and_sends_the_welcome(): void
    {
        Mail::fake();

        $this->postJson('/api/newsletter', ['email' => 'reader@example.com'])
            ->assertCreated();

        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'reader@example.com']);
        Mail::assertSent(NewsletterWelcome::class, fn ($m) => $m->hasTo('reader@example.com'));
    }

    public function test_it_stores_the_address_in_lower_case(): void
    {
        Mail::fake();

        $this->postJson('/api/newsletter', ['email' => 'Reader@Example.COM'])->assertCreated();

        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'reader@example.com']);
    }

    public function test_subscribing_twice_does_not_send_the_welcome_again(): void
    {
        Mail::fake();

        $this->postJson('/api/newsletter', ['email' => 'reader@example.com'])->assertCreated();
        $this->postJson('/api/newsletter', ['email' => 'reader@example.com'])->assertCreated();

        $this->assertSame(1, NewsletterSubscriber::count());
        Mail::assertSentTimes(NewsletterWelcome::class, 1);
    }

    public function test_resubscribing_clears_an_earlier_opt_out(): void
    {
        Mail::fake();

        NewsletterSubscriber::create([
            'email' => 'reader@example.com',
            'source' => 'popup',
            'unsubscribed_at' => now(),
        ]);

        $this->postJson('/api/newsletter', ['email' => 'reader@example.com'])->assertCreated();

        $this->assertNull(NewsletterSubscriber::first()->unsubscribed_at);
    }

    public function test_it_rejects_an_invalid_address(): void
    {
        Mail::fake();

        $this->postJson('/api/newsletter', ['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        Mail::assertNothingSent();
    }
}

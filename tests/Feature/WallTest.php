<?php

namespace Tests\Feature;

use App\Wall\Comments;
use EduLazaro\Laratox\Facades\ToxicFilter;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class WallTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        File::delete(storage_path('app/comments.json'));
        config(['laratox.key' => 'tf_test_demo', 'services.toxicfilter.webhook_secret' => 'whsec_demo']);
    }

    public function test_an_allowed_comment_is_published(): void
    {
        $fake = ToxicFilter::fake();

        $this->post('/comments', ['name' => 'Ana', 'body' => 'Lovely post, thanks.'])->assertRedirect('/');

        $this->get('/')->assertSee('Lovely post, thanks.');
        $fake->assertSent(fn ($request) => $request['body']['reference'] === 'comment_1');
    }

    public function test_a_comment_in_review_is_held(): void
    {
        ToxicFilter::fake()->shouldReview('toxicity', 'Contempt aimed at the reader.');

        $this->post('/comments', ['name' => 'Bo', 'body' => 'You clearly know nothing.'])
            ->assertRedirect('/')
            ->assertSessionHas('held', true);

        $this->get('/')->assertDontSee('You clearly know nothing.');
        $this->assertSame('held', app(Comments::class)->find(1)['status']);
    }

    public function test_a_blocked_comment_is_refused_with_the_reason(): void
    {
        ToxicFilter::fake()->shouldBlock('spam', 'Contains a referral link.');

        $this->from('/')->post('/comments', ['name' => 'Cy', 'body' => 'Free followers at spam.example'])
            ->assertRedirect('/')
            ->assertSessionHasErrors(['body' => 'The body could not be accepted: contains a referral link.']);

        $this->assertNull(app(Comments::class)->find(1));
    }

    public function test_an_approval_from_toxicfilter_publishes_the_held_comment(): void
    {
        app(Comments::class)->add(1, 'Bo', 'Held until a person looks.', 'held', 'mod_1');

        $body = json_encode(['event' => 'moderation.resolved', 'data' => ['reference' => 'comment_1', 'action' => 'approved']]);
        $time = time();
        $signature = "t={$time},v1=" . hash_hmac('sha256', "{$time}.{$body}", 'whsec_demo');

        $this->call('POST', '/webhooks/toxicfilter', [], [], [], ['HTTP_X_TOXICFILTER_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $body)
            ->assertNoContent();

        $this->get('/')->assertSee('Held until a person looks.');
    }

    public function test_an_unsigned_webhook_is_refused(): void
    {
        $this->postJson('/webhooks/toxicfilter', ['event' => 'moderation.resolved'])->assertStatus(400);
    }
}

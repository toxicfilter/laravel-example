<?php

namespace App\Http\Controllers;

use App\Wall\Comments;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ToxicFilter\Webhooks;

/**
 * A person decided on a held comment in ToxicFilter: publish it or drop it.
 */
class ToxicFilterWebhookController extends Controller
{
    /**
     * @param Request $request
     * @param Comments $comments
     * @return Response
     */
    public function __invoke(Request $request, Comments $comments): Response
    {
        $event = Webhooks::event(
            $request->getContent(),                                // the RAW body
            (string) $request->header('X-ToxicFilter-Signature', ''),
            (string) config('services.toxicfilter.webhook_secret'),
        );

        if ($event === null) {
            return response('', 400);
        }

        if ($event['event'] === 'moderation.resolved' && preg_match('/^comment_(\d+)$/', $event['data']['reference'] ?? '', $m)) {
            $event['data']['action'] === 'approved'
                ? $comments->publish((int) $m[1])
                : $comments->remove((int) $m[1]);
        }

        return response('', 204);
    }
}

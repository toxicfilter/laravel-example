<?php

namespace App\Http\Controllers;

use App\Wall\Comments;
use EduLazaro\Laratox\Rules\Moderated;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The wall: the comments, the form, and the moderation of what is posted.
 */
class WallController extends Controller
{
    /**
     * @param Comments $comments
     */
    public function __construct(private Comments $comments)
    {
    }

    /**
     * @return View
     */
    public function index(): View
    {
        return view('wall', ['comments' => $this->comments->published()]);
    }

    /**
     * Moderates the comment in validation: a block fails the field with ToxicFilter's
     * reason, a review passes and is held, an allow is published.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $id = $this->comments->nextId();
        $rule = Moderated::text()->surface('comment')->reference("comment_{$id}");

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'body' => ['bail', 'required', 'string', 'max:2000', $rule],
        ]);

        // No verdict means the API could not answer and the rule let the field through
        // (`laratox.rule.on_error` is `allow` by default). Hold it rather than publish it unread.
        $verdict = $rule->verdict();
        $held = $verdict === null || $verdict->needsReview();

        $this->comments->add($id, $data['name'], $data['body'], $held ? 'held' : 'published', $verdict?->id());

        return redirect()->route('wall')->with('held', $held);
    }
}

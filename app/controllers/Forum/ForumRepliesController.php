<?php

use Lio\Forum\Replies\ReplyForm;

class ForumRepliesController extends BaseController implements
    \Lio\Forum\Replies\ReplyCreatorListener,
    \Lio\Forum\Replies\ReplyUpdaterListener,
    \Lio\Forum\Replies\ReplyDeleterListener
{
    protected $tags;
    protected $sections;

    protected $repliesPerPage = 20;

    public function __construct(
        \Lio\Forum\Threads\ThreadRepository $threads,
        \Lio\Forum\Replies\ReplyRepository $replies,
        \Lio\Tags\TagRepository $tags,
        \Lio\Forum\SectionCountManager $sections
    ) {
        $this->threads  = $threads;
        $this->replies  = $replies;
        $this->tags     = $tags;
        $this->sections = $sections;

        $this->prepareViewData();
    }

    // bounces the user to the correct page of a thread for the indicated comment
    public function getReplyRedirect($threadSlug, $replyId)
    {
        $reply = $this->replies->requireById($replyId);

        if ( ! $reply->isOwnedBy(Auth::user())) {
            return Redirect::to('/');
        }

        $generator = App::make('Lio\Forum\Replies\ReplyQueryStringGenerator');
        $queryString = $generator->generate($reply, $this->repliesPerPage);

        return Redirect::to(action('ForumThreadsController@getShowThread', [$thread]) . $queryString);
    }

    // reply to a thread
    public function postCreateReply($threadSlug)
    {
        $thread = $this->threads->requireBySlug($threadSlug);

        return App::make('Lio\Forum\Replies\ReplyCreator')->create($this, [
            'body'   => Input::get('body'),
            'author' => Auth::user(),
        ], $thread->id, new ReplyForm);
    }

    public function replyCreationError($errors)
    {
        return $this->redirectBack(['errors' => $errors]);
    }

    public function replyCreated($reply)
    {
        return $this->redirectTo($reply->getPresenter()->viewReplyUrl());
    }

    // edit a reply
    public function getEditReply($replyId)
    {
        $reply = $this->replies->requireById($replyId);

        if ( ! $reply->isOwnedBy(Auth::user())) {
            return Redirect::to('/');
        }

        $this->view('forum.replies.edit', compact('reply'));
    }

    public function postEditReply($replyId)
    {
        $reply = $this->replies->requireById($replyId);

        if ( ! $reply->isOwnedBy(Auth::user())) {
            return Redirect::to('/');
        }

        return App::make('Lio\Forum\Replies\ReplyUpdater')->update($reply, $this, [
            'body' => Input::get('body'),
        ], new ReplyForm);
    }

    // observer methods
    public function replyUpdateError($errors)
    {
        return $this->redirectBack(['errors' => $errors]);
    }

    public function replyUpdated($reply)
    {
        return $this->redirectAction('ForumThreadsController@getShowThread', [$reply->thread->slug]);
    }

    // reply deletion
    public function getDelete($replyId)
    {
        $reply = $this->replies->requireById($replyId);

        if ( ! $reply->isOwnedBy(Auth::user())) {
            return Redirect::to('/');
        }

        $this->view('forum.replies.delete', compact('reply'));
    }

    public function postDelete($replyId)
    {
        $reply = $this->replies->requireById($replyId);

        if ( ! $reply->isOwnedBy(Auth::user())) {
            return Redirect::to('/');
        }

        return App::make('Lio\Forum\Replies\ReplyDeleter')->delete($this, $reply);
    }

    // observer methods
    public function replyDeleted($thread)
    {
        return Redirect::action('ForumThreadsController@getShowThread', [$thread->slug]);
    }

    // ------------------------- //
    private function prepareViewData()
    {
        $forumSections = Config::get('forum.sections');
        $sectionCounts = $this->sections->getCounts(Session::get('forum_last_visited'));
        View::share(compact('forumSections', 'sectionCounts'));
    }

    private function prepareForumSections()
    {
        $forumSections = Config::get('forum.sections');
        dd($forumSections);

        foreach($forumSections as $sectionTitle => $sectionTags) {
            if(str_contains($sectionTags, Input::get('tags'))) {

            }
        }
    }
}
<?php

namespace Plugins\Post\Services;

use Illuminate\Support\Facades\DB;
use Plugins\Post\Models\PollVote;
use Plugins\Post\Models\Post;

class PollVoteService
{
    /**
     * Cast a vote on a poll option.
     * Returns ['counts' => [option_id => int], 'user_vote' => option_id|null, 'user_votes' => string[]]
     */
    public function vote(Post $post, int $userId, string $optionId): array
    {
        // Validate option exists in meta
        $options = $post->meta['options'] ?? [];
        $optionIdx = collect($options)->search(fn ($o) => $o['id'] === $optionId);
        abort_if($optionIdx === false, 422, 'Invalid option');

        DB::transaction(function () use ($post, $userId, $optionId, $optionIdx) {
            $allowMultiple = $post->meta['allow_multiple'] ?? false;

            if (! $allowMultiple) {
                $existing = PollVote::where('post_id', $post->id)->where('user_id', $userId)->lockForUpdate()->first();

                if ($existing) {
                    if ($existing->option_id === $optionId) {
                        abort(422, 'Already voted');
                    }
                    // Change vote: remove old, decrement old count
                    $oldIdx = collect($post->meta['options'])->search(fn ($o) => $o['id'] === $existing->option_id);
                    if ($oldIdx !== false) {
                        // MySQL JSON_SET — no-op on SQLite (counts() reads from poll_votes anyway)
                        try {
                            DB::statement(
                                "UPDATE social_posts SET meta = JSON_SET(meta, CONCAT('$.options[', ?, '].votes_count'), GREATEST(0, CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, CONCAT('$.options[', ?, '].votes_count'))) AS UNSIGNED) - 1)) WHERE id = ?",
                                [$oldIdx, $oldIdx, $post->id]
                            );
                        } catch (\Exception $e) {
                            // SQLite doesn't support MySQL JSON functions — ignored
                        }
                    }
                    $existing->delete();
                }
            } else {
                $alreadyVoted = PollVote::where('post_id', $post->id)
                    ->where('user_id', $userId)->where('option_id', $optionId)->exists();
                abort_if($alreadyVoted, 422, 'Already voted for this option');
            }

            PollVote::create(['post_id' => $post->id, 'user_id' => $userId, 'option_id' => $optionId]);

            // Increment votes_count for the chosen option in meta (MySQL only; no-op on SQLite)
            try {
                DB::statement(
                    "UPDATE social_posts SET meta = JSON_SET(meta, CONCAT('$.options[', ?, '].votes_count'), CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, CONCAT('$.options[', ?, '].votes_count'))) AS UNSIGNED) + 1) WHERE id = ?",
                    [$optionIdx, $optionIdx, $post->id]
                );
            } catch (\Exception $e) {
                // SQLite doesn't support MySQL JSON functions — ignored
            }
        });

        return $this->counts($post, $userId);
    }

    /**
     * Remove all votes by this user on this poll.
     */
    public function removeVote(Post $post, int $userId): void
    {
        $votes = PollVote::where('post_id', $post->id)->where('user_id', $userId)->get();
        abort_if($votes->isEmpty(), 404);

        DB::transaction(function () use ($post, $votes) {
            foreach ($votes as $vote) {
                $idx = collect($post->meta['options'])->search(fn ($o) => $o['id'] === $vote->option_id);
                if ($idx !== false) {
                    try {
                        DB::statement(
                            "UPDATE social_posts SET meta = JSON_SET(meta, CONCAT('$.options[', ?, '].votes_count'), GREATEST(0, CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, CONCAT('$.options[', ?, '].votes_count'))) AS UNSIGNED) - 1)) WHERE id = ?",
                            [$idx, $idx, $post->id]
                        );
                    } catch (\Exception $e) {
                        // SQLite doesn't support MySQL JSON functions — ignored
                    }
                }
                $vote->delete();
            }
        });
    }

    /**
     * Return vote counts from poll_votes table (authoritative) and the user's current vote(s).
     */
    public function counts(Post $post, ?int $userId): array
    {
        $counts = PollVote::where('post_id', $post->id)
            ->selectRaw('option_id, count(*) as cnt')
            ->groupBy('option_id')
            ->pluck('cnt', 'option_id');

        $userVotes = $userId
            ? PollVote::where('post_id', $post->id)->where('user_id', $userId)->pluck('option_id')->all()
            : [];

        $userVote = ! empty($userVotes) ? $userVotes[0] : null;

        return [
            'counts' => $counts,
            'user_vote' => $userVote,
            'user_votes' => $userVotes,
        ];
    }
}

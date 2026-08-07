<?php

namespace Tests\Unit;

use App\Services\Slack\MentionBuilder;
use Tests\TestCase;

class MentionBuilderTest extends TestCase
{
    public function test_it_builds_a_mention_from_the_configured_user_id(): void
    {
        config(['services.slack.mention_user_id' => 'U01ABCDE23']);

        $this->assertSame('<@U01ABCDE23>', (new MentionBuilder())->build());
    }

    public function test_it_trims_surrounding_whitespace(): void
    {
        config(['services.slack.mention_user_id' => '  U01ABCDE23  ']);

        $this->assertSame('<@U01ABCDE23>', (new MentionBuilder())->build());
    }

    public function test_it_returns_empty_string_when_not_configured(): void
    {
        config(['services.slack.mention_user_id' => null]);

        $this->assertSame('', (new MentionBuilder())->build());
    }

    public function test_it_returns_empty_string_when_blank(): void
    {
        config(['services.slack.mention_user_id' => '   ']);

        $this->assertSame('', (new MentionBuilder())->build());
    }

    public function test_it_rejects_values_that_are_not_slack_member_ids(): void
    {
        config(['services.slack.mention_user_id' => '<!channel>']);

        $this->assertSame('', (new MentionBuilder())->build());
    }
}

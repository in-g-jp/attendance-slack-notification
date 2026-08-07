<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Tests\TestCase;

class SlackMentionViewTest extends TestCase
{
    public function test_attendance_view_puts_mention_line_at_the_top(): void
    {
        $members = collect([
            ['name' => '田中', 'role' => 'インターン', 'status' => '出社', 'workTime' => '15:00-19:00'],
            ['name' => '佐藤', 'role' => '社員', 'status' => 'リモート', 'workTime' => null],
        ]);

        $text = view('slack.attendance', [
            'today' => Carbon::parse('2026-08-07'),
            'dateLine' => '2026年8月7日(金)',
            'roles' => ['インターン', '社員'],
            'parsedMembers' => $members,
            'groupedMembers' => $members->groupBy('role'),
            'mentionLine' => '<@U01ABC>',
        ])->render();

        $this->assertStringStartsWith("<@U01ABC>\n\n", $text);
        $this->assertStringContainsString('*2026年8月7日(金)*', $text);
        // The member list stays inside a code block, so mentions must not live there.
        $this->assertStringContainsString('田中 15:00-19:00 出社', $text);
    }

    public function test_attendance_view_omits_mention_line_when_empty(): void
    {
        $members = collect([
            ['name' => '田中', 'role' => 'インターン', 'status' => '出社', 'workTime' => null],
        ]);

        $text = view('slack.attendance', [
            'today' => Carbon::parse('2026-08-07'),
            'dateLine' => '2026年8月7日(金)',
            'roles' => ['インターン', '社員'],
            'parsedMembers' => $members,
            'groupedMembers' => $members->groupBy('role'),
            'mentionLine' => '',
        ])->render();

        $this->assertStringNotContainsString('<@', $text);
        $this->assertStringStartsWith('*2026年8月7日(金)*', ltrim($text));
    }

    public function test_weekly_report_view_puts_mention_line_at_the_top(): void
    {
        $members = [
            [
                'name' => '田中',
                'role' => 'インターン',
                'schedules' => [['start' => Carbon::parse('2026-08-08 15:00'), 'text' => '8/8(土) 15:00-19:00 出社']],
            ],
        ];

        $text = view('slack.weekly_report', [
            'members' => $members,
            'start' => Carbon::parse('2026-08-08'),
            'roles' => ['インターン', '社員'],
            'groupedMembers' => collect($members)->groupBy('role'),
            'mentionLine' => '<@U01ABC>',
        ])->render();

        $this->assertStringStartsWith("<@U01ABC>\n\n", $text);
        $this->assertStringContainsString('来週（8/8〜）の予定一覧', $text);
    }

    public function test_weekly_report_view_omits_mention_line_when_empty(): void
    {
        $members = [
            [
                'name' => '田中',
                'role' => 'インターン',
                'schedules' => [['start' => Carbon::parse('2026-08-08 15:00'), 'text' => '8/8(土) 15:00-19:00 出社']],
            ],
        ];

        $text = view('slack.weekly_report', [
            'members' => $members,
            'start' => Carbon::parse('2026-08-08'),
            'roles' => ['インターン', '社員'],
            'groupedMembers' => collect($members)->groupBy('role'),
            'mentionLine' => '',
        ])->render();

        $this->assertStringNotContainsString('<@', $text);
        $this->assertStringStartsWith('来週（8/8〜）の予定一覧', ltrim($text));
    }
}

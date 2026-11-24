<?php

namespace Tests\Feature;

use App\Enums\TicketChannel;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\CsatRating;
use App\Models\Ticket;
use App\Services\ReportService;
use Carbon\Carbon;
use Tests\TestCase;

class ReportTest extends TestCase
{
    protected function seedReportTickets(): array
    {
        $agent = $this->staff('agent', ['name' => 'Report Agent']);
        $category = $this->category();
        $start = Carbon::parse('2026-01-10 09:00:00', 'UTC');

        $make = function (string $reference, int $firstMinutes, int $resolutionMinutes) use ($agent, $category, $start): Ticket {
            $ticket = new Ticket([
                'reference' => $reference,
                'subject' => 'Report ticket '.$reference,
                'description' => 'A ticket used to verify report calculations.',
                'status' => TicketStatus::Solved,
                'priority' => TicketPriority::Normal,
                'channel' => TicketChannel::Portal,
                'category_id' => $category->id,
                'requester_name' => 'Report Requester',
                'requester_email' => strtolower($reference).'@example.test',
                'requester_locale' => 'ar',
                'assigned_to' => $agent->id,
                'first_response_due_at' => $start->copy()->addMinutes(60),
                'first_responded_at' => $start->copy()->addMinutes($firstMinutes),
                'resolution_due_at' => $start->copy()->addMinutes(600),
                'resolved_at' => $start->copy()->addMinutes($resolutionMinutes),
                'last_reply_at' => $start,
            ]);
            $ticket->created_at = $start->copy();
            $ticket->updated_at = $start->copy();
            $ticket->save();

            return $ticket;
        };

        $first = $make('TKT-2026-9001', 30, 120);
        $second = $make('TKT-2026-9002', 30, 240);

        CsatRating::create([
            'ticket_id' => $first->id,
            'score' => 5,
            'comment' => 'Excellent',
            'submitted_at' => $start->copy()->addDay(),
        ]);
        CsatRating::create([
            'ticket_id' => $second->id,
            'score' => 4,
            'comment' => 'Good',
            'submitted_at' => $start->copy()->addDay(),
        ]);

        return [$agent, $start];
    }

    public function test_report_metrics_are_calculated_from_real_tickets(): void
    {
        [$agent, $start] = $this->seedReportTickets();
        $admin = $this->staff('admin');

        $report = app(ReportService::class)->build(
            $start->copy()->startOfDay(),
            $start->copy()->addDays(2)->endOfDay(),
            [],
            $admin,
        );

        $this->assertSame(2, $report['total']);
        $this->assertSame(2, $report['solved']);
        $this->assertSame(30, $report['avg_first_response']);
        $this->assertSame(180, $report['avg_resolution']);
        $this->assertSame(100, $report['first_response_compliance']);
        $this->assertSame(100, $report['resolution_compliance']);
        $this->assertSame(4.5, $report['csat_average']);
        $this->assertSame(2, $report['csat_count']);

        $row = collect($report['per_agent'])->firstWhere('id', $agent->id);
        $this->assertNotNull($row);
        $this->assertSame(2, $row['assigned']);
        $this->assertSame(2, $row['solved']);
        $this->assertSame(4.5, $row['csat']);
    }

    public function test_report_page_and_csv_export_render(): void
    {
        [$agent, $start] = $this->seedReportTickets();
        $admin = $this->staff('admin');

        $this->actingAs($admin)->get('/app/reports?from=2026-01-10&to=2026-01-12')
            ->assertOk()
            ->assertSee('Report Agent')
            ->assertSee('4.5');

        $response = $this->actingAs($admin)->get('/app/reports/export?from=2026-01-10&to=2026-01-12');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('Report Agent', $response->streamedContent());
        $this->assertStringContainsString('csat_average', $response->streamedContent());
    }

    public function test_reports_are_filtered_by_agent(): void
    {
        [$agent, $start] = $this->seedReportTickets();
        $other = $this->staff('agent', ['name' => 'Other Agent']);
        $admin = $this->staff('admin');

        $report = app(ReportService::class)->build($start->copy(), $start->copy()->addDay(), ['agent' => $agent->id], $admin);

        $this->assertSame(2, $report['total']);
        $this->assertCount(1, $report['per_agent']);
        $this->assertSame($agent->id, $report['per_agent'][0]['id']);
        $this->assertNotSame($other->id, $report['per_agent'][0]['id']);
    }

    public function test_report_rejects_ranges_longer_than_a_year(): void
    {
        $admin = $this->staff('admin');

        $this->actingAs($admin)->get('/app/reports?from=2020-01-01&to=2026-01-01')->assertStatus(422);
    }
}

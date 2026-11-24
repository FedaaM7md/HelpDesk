<?php

namespace Database\Factories;

use App\Enums\TicketChannel;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'TKT-'.now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'subject' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::Normal,
            'channel' => TicketChannel::Portal,
            'category_id' => TicketCategory::factory(),
            'requester_name' => fake()->name(),
            'requester_email' => fake()->safeEmail(),
            'requester_locale' => 'ar',
            'assigned_to' => null,
            'created_by' => null,
            'last_reply_at' => now(),
        ];
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn () => ['assigned_to' => $user->id]);
    }

    public function solved(): static
    {
        return $this->state(fn () => [
            'status' => TicketStatus::Solved,
            'resolved_at' => now(),
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn () => ['priority' => TicketPriority::Urgent]);
    }
}

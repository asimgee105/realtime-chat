<?php

namespace Tests\Feature;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private function createConversation(
        User $userA,
        User $userB
    ): Conversation {
        $ids = [
            $userA->id,
            $userB->id,
        ];

        sort($ids);

        $conversation = Conversation::create([
            'type' => 'direct',
            'direct_key' => implode(':', $ids),
        ]);

        $conversation
            ->users()
            ->attach([
                $userA->id,
                $userB->id,
            ]);

        return $conversation;
    }

    public function test_guest_cannot_access_chat_dashboard(): void
    {
        $this
            ->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_user_can_start_direct_conversation(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $response = $this
            ->actingAs($userA)
            ->post(
                route('chat.start', $userB)
            );

        $response->assertRedirect();

        $this->assertDatabaseCount(
            'conversations',
            1
        );

        $this->assertDatabaseCount(
            'conversation_user',
            2
        );
    }

    public function test_same_two_users_do_not_create_duplicate_conversation(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this
            ->actingAs($userA)
            ->post(
                route('chat.start', $userB)
            );

        $this
            ->actingAs($userA)
            ->post(
                route('chat.start', $userB)
            );

        $this->assertDatabaseCount(
            'conversations',
            1
        );
    }

    public function test_participant_can_send_message(): void
    {
        Event::fake([
            MessageSent::class,
        ]);

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $conversation = $this
            ->createConversation(
                $userA,
                $userB
            );

        $response = $this
            ->actingAs($userA)
            ->postJson(
                route(
                    'chat.send',
                    $conversation
                ),
                [
                    'body' => 'Salam, test message',
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'success',
                true
            );

        $this->assertDatabaseHas(
            'messages',
            [
                'conversation_id' => $conversation->id,

                'sender_id' => $userA->id,

                'body' => 'Salam, test message',
            ]
        );

        Event::assertDispatched(
            MessageSent::class
        );
    }

    public function test_outsider_cannot_send_message(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $outsider = User::factory()->create();

        $conversation = $this
            ->createConversation(
                $userA,
                $userB
            );

        $this
            ->actingAs($outsider)
            ->postJson(
                route(
                    'chat.send',
                    $conversation
                ),
                [
                    'body' => 'Unauthorized message',
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseCount(
            'messages',
            0
        );
    }

    public function test_user_cannot_open_someone_elses_conversation(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $outsider = User::factory()->create();

        $conversation = $this
            ->createConversation(
                $userA,
                $userB
            );

        $this
            ->actingAs($outsider)
            ->get(
                route(
                    'chat.show',
                    $conversation
                )
            )
            ->assertForbidden();
    }

    public function test_received_message_can_be_marked_as_read(): void
    {
        Event::fake([
            MessageRead::class,
        ]);

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $conversation = $this
            ->createConversation(
                $userA,
                $userB
            );

        $message = Message::create([
            'conversation_id' => $conversation->id,

            'sender_id' => $userA->id,

            'body' => 'Please read this',
        ]);

        $this
            ->actingAs($userB)
            ->postJson(
                route(
                    'chat.read',
                    $conversation
                )
            )
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            );

        $this->assertNotNull(
            $message
                ->fresh()
                ->read_at
        );

        Event::assertDispatched(
            MessageRead::class
        );
    }

    public function test_sender_cannot_mark_own_message_as_read(): void
    {
        Event::fake([
            MessageRead::class,
        ]);

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $conversation = $this
            ->createConversation(
                $userA,
                $userB
            );

        $message = Message::create([
            'conversation_id' => $conversation->id,

            'sender_id' => $userA->id,

            'body' => 'My own message',
        ]);

        $this
            ->actingAs($userA)
            ->postJson(
                route(
                    'chat.read',
                    $conversation
                )
            )
            ->assertOk();

        $this->assertNull(
            $message
                ->fresh()
                ->read_at
        );
    }

    public function test_empty_message_is_rejected(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $conversation = $this
            ->createConversation(
                $userA,
                $userB
            );

        $this
            ->actingAs($userA)
            ->postJson(
                route(
                    'chat.send',
                    $conversation
                ),
                [
                    'body' => '',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'body'
            );
    }
}

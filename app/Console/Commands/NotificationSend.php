<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Console\Command;

class NotificationSend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send notification to user devices';

    /**
     * Summary of firebase
     * @var
     */
    protected $firebase;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $notifications = Notification::where('scheduled_at', '<=', now())
            ->whereNull('sent_at')
            ->whereNotNull('user_id')
            ->get();

        foreach ($notifications as $notification) {
            $user = User::find($notification->user_id);

            if ($user && $user->device_token) {
                try {
                    $this->firebase->sendNotification(
                        $user->device_token,
                        $notification->title,
                        $notification->body,
                        $notification->data
                    );

                    // Mark the notification as sent
                    $notification->update(['sent_at' => now()]);
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }
        }

        return true;
    }
}

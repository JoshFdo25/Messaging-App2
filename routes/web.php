<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Message;
use App\Events\MessageReceived;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('chat/users', function () {
    return User::where('id', '!=', auth()->id())->select('id', 'name', 'avatar')->get();
})->middleware('auth');

Route::get('chat/{user}', function (User $user) {
    $messages = Message::where(function ($query) use ($user) {
        $query->where('sender_id', auth()->id())->where('receiver_id', $user->id);
    })->orWhere(function ($query) use ($user) {
        $query->where('sender_id', $user->id)->where('receiver_id', auth()->id());
    })->orderBy('created_at', 'asc')->get()->map(function ($message) {
        return [
            'id' => $message->id,
            'message' => $message->message,
            'who' => $message->sender_id === auth()->id() ? 'me' : $message->sender->name,
        ];
    });

    return Inertia::render('Chat', [
        'user' => $user,
        'messages' => $messages,
    ]);
})->middleware('auth')->name('chat');

Route::post('/messages', function () {
    $payload = request()->validate([
        'message' => 'required|string',
        'id' => 'required|exists:users,id',

    ]);

    $message = Message::create([
        'sender_id' => auth()->id(),
        'receiver_id' => $payload['id'],
        'message' => $payload['message'],
    ]);

    broadcast(new MessageReceived($message->message, $payload['id'], auth()->user()->name));

    return response()->json(['message' => 'Message sent successfully']);
})->middleware('auth');

require __DIR__.'/auth.php';
require __DIR__.'/settings.php';

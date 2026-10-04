# Laravel Real-Time Chat System

A real-time one-to-one chat application built with Laravel, Reverb, Laravel Echo, MySQL and Vite.

## Features

- User Registration and Login
- One-to-One Conversations
- Real-Time Messaging
- Laravel Reverb WebSockets
- Laravel Echo
- Private Presence Channels
- Online / Offline Status
- Typing Indicator
- Sent / Seen Status
- Message History
- Load Older Messages
- Conversation Authorization
- Database Queue
- Automated Feature Tests
- GitHub Actions CI

## Architecture

Browser
→ Laravel
→ MySQL

Browser
↔ WebSocket / Reverb
↔ Laravel Broadcasting

Message Flow:

User sends message
→ Validation
→ Authorization
→ Database
→ Broadcast Event
→ Reverb
→ Receiver Browser

## Requirements

- PHP 8.4+
- Composer
- MySQL
- Node.js
- NPM

## Installation

```bash
git clone YOUR_REPOSITORY_URL
cd realtime-laravel-chat

composer install
npm install

cp .env.example .env
php artisan key:generate

php artisan migrate
npm run build
```

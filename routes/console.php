<?php

use Illuminate\Support\Facades\Schedule;

// Run Autonomous AI Marketing Job every morning at 07:00 AM
Schedule::command('ai:daily-marketing')->dailyAt('07:00');

// Auto-publish scheduled social media posts every 5 minutes via Repliz
Schedule::command('posts:publish-scheduled')->everyFiveMinutes();

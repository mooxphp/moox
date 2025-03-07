<?php

use Illuminate\Support\Facades\Route;
use Moox\Devops\Webhooks\EnvoyerWebhook;
use Moox\Devops\Webhooks\ForgeWebhook;
use Moox\Devops\Webhooks\GithubWebhook;

Route::post('/webhooks/forge', [ForgeWebhook::class, 'handleForge']);
Route::post('/webhooks/github', [GithubWebhook::class, 'handleGitHub']);
Route::post('/webhooks/envoyer', [EnvoyerWebhook::class, 'handleGitHub']);

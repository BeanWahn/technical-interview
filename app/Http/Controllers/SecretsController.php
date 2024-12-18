<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Contracts\Support\Responsable;

class SecretsController extends Controller
{
    public function index(): Responsable
    {
        return Inertia::render('Secrets');
    }
}

<?php
namespace App\Controllers;

use App\Controller;

class GraficoController extends Controller
{
    public function index(): void
    {
        $user = $this->currentUser();
        $this->render('pages.graficos', compact('user'));
    }
}

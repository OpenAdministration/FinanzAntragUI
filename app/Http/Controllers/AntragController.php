<?php

namespace App\Http\Controllers;

class AntragController extends Controller
{
    public function index(int $site = 1)
    {
        return view("antrag.$site");
    }
}

<?php

use App\Livewire\DocumentsPanel;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/documentos', DocumentsPanel::class)->name('documents.index');

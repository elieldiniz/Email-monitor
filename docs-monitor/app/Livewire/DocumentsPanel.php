<?php

namespace App\Livewire;

use App\Models\Document;
use Livewire\Component;

class DocumentsPanel extends Component
{
    public $documents;

    public function mount()
    {
        $this->loadDocuments();
    }

    public function loadDocuments()
    {
        $this->documents = Document::latest('received_at')->get();
    }

    public function render()
    {
        return view('livewire.documents-panel')
            ->layout('components.layouts.app')
            ->title('Docs Monitor');
    }
}

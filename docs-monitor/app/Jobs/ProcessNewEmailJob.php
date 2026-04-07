<?php

namespace App\Jobs;

use DirectoryTree\ImapEngine\Mailbox;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessNewEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $uid) {}

    public function handle(): void
    {
        try {
            $mailbox = new Mailbox(config('imap.mailboxes.default'));
            $inbox = $mailbox->inbox();

            $message = $inbox->messages()
                ->withHeaders()
                ->withBody()
                ->findOrFail($this->uid);

            $subject   = $message->subject() ?? 'Sem assunto';
            $this->log("📧 Processando UID: {$this->uid} | Assunto: {$subject}");

            $html = $message->html() ?? $message->text() ?? '';
            
            if (empty(trim($html))) {
                $this->log("⚠️ Corpo vazio");
                $message->markSeen();
                return;
            }

            $this->log("📏 Tamanho HTML: " . strlen($html) . " caracteres");

            // Extrai link do Google Drive
            $url = $this->extractDriveLink($html);

            if (!$url) {
                $this->log("❌ Nenhum link do Google Drive encontrado.");
                $message->markSeen();
                return;
            }

            $this->log("🔗 Link Drive encontrado: " . $url);

            // Converte para link direto de download
            $directUrl = $this->convertToDirectDownload($url);

            $this->log("⬇️  Link direto de download: " . $directUrl);

            // Baixa o arquivo
            $response = Http::timeout(60)
                ->withoutVerifying()
                ->get($directUrl);

            if (!$response->successful()) {
                $this->log("❌ Download falhou (HTTP " . $response->status() . ")");
                $message->markSeen();
                return;
            }

            $filename = 'Digitalizado_' . now()->format('Ymd_His') . '.pdf';
            $path = 'documents/' . now()->format('Y/m/') . $filename;

            Storage::disk('public')->put($path, $response->body());

            $this->log("✅ PDF baixado com sucesso!");
            $this->log("📁 Salvo como: {$path}");

            $message->markSeen();

        } catch (\Exception $e) {
            \Log::error('ProcessNewEmailJob Error', ['uid' => $this->uid, 'error' => $e->getMessage()]);
            $this->log("❌ Erro: " . $e->getMessage());
        }
    }

    private function extractDriveLink(string $html): ?string
    {
        // Procura links do Google Drive
        if (preg_match('/https?:\/\/(?:www\.)?drive\.google\.com[^\s<>"\']+/i', $html, $match)) {
            return $match[0];
        }
        return null;
    }

    private function convertToDirectDownload(string $url): string
    {
        // Converte link de visualização para download direto
        if (preg_match('/file\/d\/([a-zA-Z0-9_-]+)/i', $url, $match)) {
            $fileId = $match[1];
            return "https://drive.google.com/uc?export=download&id=" . $fileId;
        }

        return $url; // fallback
    }

    private function log(string $message): void
    {
        if (app()->runningInConsole()) {
            echo $message . PHP_EOL;
        }
    }
}
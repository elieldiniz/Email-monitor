<?php

namespace App\Console\Commands;

use App\Jobs\ProcessNewEmailJob;
use DirectoryTree\ImapEngine\Mailbox;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class EmailPollChecker extends Command
{
    protected $signature = 'email:poll';

    protected $description = 'Verifica Inbox, envia job para PDFs e marca todos como lidos';

    public function handle(): int
    {
        $this->info('🔄 Iniciando verificação na Caixa de Entrada...');

        try {
            $mailbox = new Mailbox(config('imap.mailboxes.default'));
            $inbox = $mailbox->inbox();

            $authorizedSender = strtolower(trim(env('DOCUMENT_SENDER', 'elieldiniz1@outlook.com')));

            $messages = $inbox->messages()
                ->withHeaders()
                ->unseen()
                ->since(Carbon::now()->subDays(2))
                ->get();

            $count = $messages->count();

            if ($count === 0) {
                $this->info('📭 Nenhum e-mail não lido encontrado na Inbox.');
                return 0;
            }

            $this->info("📬 Encontrados {$count} e-mail(s) não lido(s) na Caixa de Entrada");

            $processed = 0;

            foreach ($messages as $message) {
                $subject   = $message->subject() ?? '[SEM ASSUNTO]';
                $fromObj   = $message->from();
                $fromEmail = $fromObj ? ($fromObj->email() ?? '') : '';
                $uid       = $message->uid();

                $displayFrom = $fromObj 
                    ? ($fromObj->name() ? $fromObj->name() . ' <' . $fromEmail . '>' : $fromEmail) 
                    : '[SEM REMETENTE]';

                $this->info("------------");
                $this->info("📧 Assunto: {$subject}");
                $this->info("👤 De: {$displayFrom}");
                $this->info("🆔 UID: {$uid}");

                if ($fromEmail && str_contains(strtolower($fromEmail), $authorizedSender)) {
                    ProcessNewEmailJob::dispatch($uid);
                    $this->info("✅ Job enviado para UID: {$uid}");
                    $processed++;
                } else {
                    $this->info("⏭️  Ignorado (remetente não autorizado)");
                }

                // Marca como lido SEMPRE
                $message->markSeen();
                $this->info("👁️  Marcado como lido");
            }

            $this->info("✅ Concluído! {$count} e-mail(s) marcado(s) como lido(s). {$processed} job(s) enviado(s).");

            return 0;

        } catch (\Throwable $e) {
            $this->error("❌ Erro: {$e->getMessage()}");
            \Log::error('email.poll.error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
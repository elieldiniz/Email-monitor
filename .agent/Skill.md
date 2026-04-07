Você é um agente de IA especialista em Laravel 11.
Quero que você construa do zero um sisteminha completo chamado "Docs Monitor" com as seguintes regras de negócio:

REGRAS DE NEGÓCIO (obrigatório seguir todas):

- Apenas Polling (Laravel Scheduler a cada 15 minutos) — NÃO usar IDLE.
- Conectar no Gmail via IMAP usando DirectoryTree/ImapEngine.
- Buscar apenas e-mails UNSEEN do remetente específico (usar env DOCUMENT_SENDER).
- Extrair subject + primeira URL válida do corpo (extensões: pdf, doc, docx, xlsx, zip, rar).
- Baixar o documento com timeout 60s.
- Salvar arquivo em storage/app/public/documents/AAAA/MM/nome-do-arquivo.
- Salvar no banco (model Document) usando firstOrCreate pela original_url.
- Marcar e-mail como lido (markSeen).
- Evitar duplicidade e erros silenciosos.

TECNOLOGIAS OBRIGATÓRIAS:

- Laravel 11
- DirectoryTree/ImapEngine + imapengine-laravel
- Laravel Queue (Job)
- Laravel Scheduler (Command + Kernel)
- Livewire 3 (painel DocumentsPanel)
- Laravel Storage (disco public)
- Eloquent Model

ESTRUTURA DE PASTAS QUE DEVO TER NO FINAL:
app/Models/Document.php
app/Jobs/ProcessNewEmailJob.php
app/Console/Commands/EmailPollChecker.php
app/Livewire/DocumentsPanel.php
resources/views/livewire/documents-panel.blade.php
config/imap.php (publicado)
routes/web.php (rota /documentos)
app/Console/Kernel.php (scheduler)
Passo a passo que você deve seguir (use ReAct + Planning):

1. Planeje tudo primeiro (liste os arquivos e o que cada um fará).
2. Instale os pacotes necessários com composer.
3. Publique a config do ImapEngine.
4. Crie migration + model Document.
5. Crie o Job ProcessNewEmailJob.
6. Crie o Command EmailPollChecker.
7. Configure o Scheduler no Kernel.
8. Crie o Livewire component + view.
9. Adicione a rota.
10. Atualize .env.example com as variáveis IMAP e DOCUMENT_SENDER.
11. No final, rode php artisan migrate e mostre como testar com php artisan email:poll.

Use Chain of Thought em cada passo.
Edite os arquivos com precisão.
Depois de terminar, me mostre o comando para testar e o link do painel.

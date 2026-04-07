# Docs Monitor - Visão Geral do Projeto

## Descrição

Sistema Laravel 11 para monitoramento automatizado de e-mails via IMAP (Gmail), extraindo documentos de URLs presentes no corpo de e-mails de remetentes específicos.

## Funcionamento Resumido

1. **Polling**: A cada 15 minutos, o sistema verifica a caixa de e-mail via IMAP
2. **Filtragem**: Busca apenas e-mails não lidos (`UNSEEN`) de um remetente configurado
3. **Extração**: Captura o subject e a primeira URL válida do corpo do e-mail
4. **Download**: Baixa o documento da URL (timeout de 60s)
5. **Armazenamento**: Salva o arquivo no storage e registra no banco de dados
6. **Visualização**: Painel Livewire 3 exibe todos os documentos processados

## Arquitetura

- **Framework**: Laravel 11
- **IMAP**: DirectoryTree/ImapEngine
- **Processamento**: Laravel Queue (Jobs) + Scheduler (Commands)
- **Interface**: Livewire 3 + Blade + Tailwind CSS
- **Storage**: Laravel Storage (disco public)

## Fluxo do Sistema

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  Gmail (IMAP)   │────▶│  EmailPollChecker │────▶│ ProcessNewEmail │
│   UNSEEN msgs   │     │  (Command)       │     │    (Job)        │
└─────────────────┘     └──────────────────┘     └────────┬────────┘
                                                        │
                              ┌─────────────────────────┼─────────────────────────┐
                              │                         │                         │
                              ▼                         ▼                         ▼
                    ┌─────────────────┐      ┌─────────────────┐      ┌─────────────────┐
                    │  Download File  │─────▶│ Storage/public  │─────▶│     Database    │
                    │  (HTTP Client)  │      │ documents/YYYY/MM│      │   (Document)    │
                    └─────────────────┘      └─────────────────┘      └─────────────────┘
                                                                               │
                                                                               ▼
                                                                      ┌─────────────────┐
                                                                      │  DocumentsPanel │
                                                                      │   (Livewire 3)  │
                                                                      └─────────────────┘
```

## Estrutura de Pastas

```
app/
├── Console/
│   └── Commands/
│       └── EmailPollChecker.php          # Comando de polling IMAP
├── Jobs/
│   └── ProcessNewEmailJob.php          # Job de processamento assíncrono
├── Livewire/
│   └── DocumentsPanel.php                # Componente do painel
├── Models/
│   └── Document.php                      # Model Eloquent
config/
├── imap.php                              # Configuração ImapEngine
database/
├── migrations/
│   └── create_documents_table.php        # Migration da tabela documents
docs/                                     # 📁 Documentação completa
resources/
└── views/
    └── livewire/
        └── documents-panel.blade.php     # View do painel
routes/
└── web.php                               # Rota /documentos
storage/
└── app/
    └── public/
        └── documents/                    # Arquivos baixados (YYYY/MM/)
```

## Tecnologias Utilizadas

| Componente | Tecnologia | Versão/Motivo |
|------------|------------|---------------|
| Framework | Laravel | 11 (versão atual em 2026) |
| IMAP | DirectoryTree/ImapEngine | Moderna, leve, excelente suporte a buscas |
| Agendamento | Laravel Scheduler | Polling a cada 15 minutos |
| Processamento | Laravel Queue + Jobs | Assíncrono, não bloqueia scheduler |
| Banco de Dados | Eloquent + Migration | Padrão Laravel |
| Storage | Laravel Storage (public) | Fácil, pode migrar para S3 depois |
| Interface | Livewire 3 | Componentes reativos sem Vue/React |
| Frontend | Blade + Tailwind CSS | Padrão Laravel |

## Regras Principais

- **Apenas Polling**: Laravel Scheduler a cada 15 minutos (nunca IDLE)
- **Remetente Específico**: Configurável via `.env` (DOCUMENT_SENDER)
- **Apenas UNSEEN**: Só processa e-mails não lidos
- **Janela de 25 minutos**: Busca e-mails dos últimos 25 minutos (margem de segurança)
- **Extensões Permitidas**: pdf, doc, docx, xlsx, zip, rar
- **Anti-duplicidade**: Usa `firstOrCreate` pela URL original
- **Marcação**: Sempre marca e-mail como lido após processamento

## Próximos Passos

Consulte o arquivo `05-CHECKLIST-IMPLEMENTACAO.md` para iniciar a implementação fase por fase.

---

**Projeto iniciado em:** 30 de Março de 2026  
**Arquitetura:** ReAct + Chain of Thought + Planning + Tool Use

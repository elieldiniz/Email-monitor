# Regras de Negócio - Docs Monitor

## 1. Objetivo Geral

O sistema deve monitorar automaticamente uma caixa de e-mail específica (Gmail) e, sempre que chegar um e-mail de um remetente autorizado contendo uma URL de documento, baixar esse documento e registrá-lo no banco de dados para visualização no painel.

## 2. Regras Principais

### 2.1 Remetente

- **Apenas e-mails enviados pelo remetente específico configurado serão processados**
- Qualquer e-mail de outro remetente será **ignorado** e imediatamente marcado como lido
- O remetente será definido via `.env` na variável `DOCUMENT_SENDER`
- Exemplo: `documentos@seusistema.com`

### 2.2 Frequência de Verificação

- O sistema verificará novos e-mails **a cada 15 minutos** através do Laravel Scheduler
- **Não haverá escuta em tempo real (IDLE)** - apenas polling

### 2.3 Filtro de E-mails

| Critério | Descrição |
|----------|-----------|
| Status | Apenas e-mails **não lidos** (`UNSEEN`) |
| Tempo | E-mails recebidos nos **últimos 25 minutos** (margem de segurança) |
| Remetente | Apenas e-mails do remetente configurado em `DOCUMENT_SENDER` |

### 2.4 Extração de Informações

#### Subject
- Deve ser capturado **integralmente**
- Salvo no campo `subject` do banco

#### URL
- Deve ser extraída do corpo do e-mail (texto ou HTML)
- **Critérios da URL válida:**
  - Deve começar com `http://` ou `https://`
  - Deve terminar com uma das extensões permitidas:
    - `.pdf`
    - `.doc`
    - `.docx`
    - `.xlsx`
    - `.xls`
    - `.zip`
    - `.rar`
- Será considerada apenas a **primeira URL válida** encontrada no corpo

### 2.5 Download do Documento

| Regra | Descrição |
|-------|-----------|
| Timeout | Máximo de **60 segundos** |
| Status HTTP | Apenas processa se retornar **200 OK** |
| Falha | Se falhar, marca e-mail como lido e ignora (sem erro fatal) |
| Retry | **Não há retry automático** no MVP |

### 2.6 Armazenamento

#### Estrutura de Diretórios
```
storage/app/public/documents/
├── 2026/                          # Ano
│   ├── 03/                        # Mês (03 = Março)
│   │   ├── documento_recebido.pdf
│   │   ├── planilha_dados.xlsx
│   │   └── arquivo_compactado.zip
│   └── 04/                        # Próximo mês
│       └── ...
```

#### Nome do Arquivo
- Usar nome original da URL quando disponível
- Se não houver nome original: `doc_{timestamp}.{extensão}`
- Exemplo: `doc_1711823400.pdf`

#### Caminho no Banco
- Salvar caminho relativo: `documents/YYYY/MM/nome-arquivo.ext`
- Exemplo: `documents/2026/03/documento.pdf`

### 2.7 Persistência no Banco

#### Tabela: `documents`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | bigint unsigned | PK auto-increment |
| `subject` | string | Assunto completo do e-mail |
| `original_url` | string | URL de onde o arquivo foi baixado |
| `file_path` | string | Caminho relativo no storage |
| `filename` | string | Nome do arquivo |
| `from_email` | string | E-mail do remetente |
| `received_at` | timestamp | Data/hora do e-mail |
| `created_at` | timestamp | Data/hora do registro |
| `updated_at` | timestamp | Data/hora da última atualização |

#### Regras de Persistência

- **Anti-duplicidade**: Usar `firstOrCreate` pela `original_url`
- Se a mesma URL já existir: **não criar novo registro**
- Campos obrigatórios: `subject`, `file_path`, `filename`, `from_email`, `received_at`
- Campo opcional: `original_url` (pode ser null se não houver URL)

### 2.8 Marcação de E-mail

- **Após o processamento (com sucesso ou não)**, o e-mail **deve ser marcado como lido** (`markSeen()`)
- Isso evita que o mesmo e-mail seja processado repetidamente
- Mesmo em caso de erro no download, o e-mail deve ser marcado como lido

### 2.9 Visualização no Painel

#### Listagem
- Ordenação: **mais recente primeiro** (por `received_at` DESC)
- Campos exibidos:
  - Data e hora do recebimento (formato: `d/m/Y H:i`)
  - Subject
  - Nome do arquivo (com link para download)
  - URL original (texto, não link clicável - para referência)

#### Download
- Link deve usar `Storage::url()` para gerar URL pública
- Download direto do arquivo salvo

## 3. Regras de Exceção e Segurança

| Situação | Ação |
|----------|------|
| Sem URL válida no corpo | Marcar e-mail como lido e ignorar |
| Remetente não é o configurado | Marcar como lido e ignorar |
| Erro no download (timeout, 404, etc.) | Marcar como lido e ignorar (log de warning) |
| Erro ao salvar no storage | Marcar como lido e ignorar (log de error) |
| Erro ao salvar no banco | Marcar como lido e ignorar (log de error) |
| Múltiplas URLs no e-mail | Processar apenas a primeira válida |
| URL com extensão não permitida | Ignorar e marcar como lido |

## 4. Regras de Performance e Simplicidade

### 4.1 Leveza do Polling
- O comando de polling deve ser **rápido** (apenas busca no IMAP)
- Todo processamento pesado (download + salvamento) deve ser feito via **Queue (Job)**
- Isso evita que o scheduler fique bloqueado

### 4.2 Idempotência
- O comando de polling deve ser **idempotente**
- Rodar múltiplas vezes não deve causar problemas
- Duplicatas são prevenidas pelo `firstOrCreate`

### 4.3 Fail-Safe
- Erros em um e-mail **não devem parar** o processamento dos próximos
- Cada job é independente
- Logs em todas as etapas críticas para debug

## 5. Regras de Configuração

### 5.1 Variáveis de Ambiente (.env)

```env
# IMAP Configuration
IMAP_DEFAULT_HOST=imap.gmail.com
IMAP_DEFAULT_PORT=993
IMAP_DEFAULT_USERNAME=seuemaildocs@gmail.com
IMAP_DEFAULT_PASSWORD=sua_senha_de_app_de_16_caracteres

# Document Sender
DOCUMENT_SENDER=documentos@seusistema.com

# Queue
QUEUE_CONNECTION=database
```

### 5.2 Regras das Configurações

- **Nunca** hardcode credenciais ou remetente no código
- Todas as configurações devem vir do `.env`
- Valores padrão devem ser `null` ou vazios (não expor dados)

## 6. Checklist de Regras de Negócio

- [ ] Só processa e-mail do remetente configurado
- [ ] Só processa e-mails não lidos (UNSEEN)
- [ ] Busca apenas e-mails dos últimos 25 minutos
- [ ] Extrai subject completo
- [ ] Extrai primeira URL válida do corpo
- [ ] Valida extensão da URL (pdf, doc, docx, xlsx, xls, zip, rar)
- [ ] Baixa documento com timeout de 60s
- [ ] Verifica sucesso do download (HTTP 200)
- [ ] Salva arquivo em `documents/YYYY/MM/`
- [ ] Gera nome de arquivo se necessário
- [ ] Salva registro no banco
- [ ] Usa `firstOrCreate` para evitar duplicidade
- [ ] Marca e-mail como lido após processamento
- [ ] Exibe documentos no painel ordenados por data (mais recente primeiro)
- [ ] Link de download funciona via `Storage::url()`
- [ ] Nunca usa IDLE - apenas polling
- [ ] Scheduler roda a cada 15 minutos
- [ ] Processamento em Job (Queue) para não bloquear
- [ ] Tratamento de erros sem quebrar o fluxo
- [ ] Logs em todas as etapas críticas

## Documentação Relacionada

- [00-README.md](00-README.md) - Visão geral
- [01-ESTRATEGIA.md](01-ESTRATEGIA.md) - Estratégia completa
- [02-MVP.md](02-MVP.md) - Escopo do MVP
- [04-TECNOLOGIAS.md](04-TECNOLOGIAS.md) - Stack tecnológica
- [05-CHECKLIST-IMPLEMENTACAO.md](05-CHECKLIST-IMPLEMENTACAO.md) - Checklist de implementação

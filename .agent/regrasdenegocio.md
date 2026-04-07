### Regras de Negócio – Sisteminha de Monitoramento de Documentos via E-mail (Polling)

#### 1. Objetivo Geral

O sistema deve monitorar automaticamente uma caixa de e-mail específica (Gmail “docs”) e, sempre que chegar um e-mail de um remetente autorizado contendo uma URL de documento, baixar esse documento e registrá-lo no banco de dados para visualização no painel.

#### 2. Regras Principais

**2.1 Remetente**

- Apenas e-mails enviados pelo **remetente específico** configurado serão processados.
- Qualquer e-mail de outro remetente será **ignorado** e imediatamente marcado como lido.
- O remetente será definido via `.env` ou constante no código (ex: `documentos@seusistema.com`).

**2.2 Frequência de Verificação**

- O sistema verificará novos e-mails **a cada 15 minutos** através do Laravel Scheduler.
- Não haverá escuta em tempo real (IDLE).

**2.3 Filtro de E-mails**

- Apenas e-mails **não lidos** (`unseen`) serão considerados.
- Serão buscados apenas e-mails recebidos nos últimos 25 minutos (margem de segurança).
- Serão filtrados pelo remetente específico.

**2.4 Extração de Informações**

- **Subject**: Deve ser capturado integralmente e salvo.
- **URL**: Deve ser extraída do corpo do e-mail (texto ou HTML).
  - Critério: A URL deve começar com `http://` ou `https://` e terminar com uma das extensões permitidas: `.pdf`, `.doc`, `.docx`, `.xlsx`, `.zip`, `.rar`.
  - Será considerada apenas a **primeira URL válida** encontrada no corpo.

**2.5 Download do Documento**

- O arquivo deve ser baixado da URL extraída.
- Timeout máximo de download: 60 segundos.
- Se o download falhar (erro de conexão, status diferente de 200, etc.), o e-mail será marcado como lido e o processamento será ignorado (sem falhar o job inteiro).

**2.6 Armazenamento**

- O arquivo baixado será salvo no disco `public` no seguinte padrão de caminho:
  - `documents/AAAA/MM/nome-do-arquivo.ext`
- O nome do arquivo será baseado no nome original da URL ou gerado automaticamente (`doc_ timestamp.ext`).

**2.7 Persistência no Banco**

- Cada documento processado com sucesso será salvo na tabela `documents`.
- Campos obrigatórios:
  - `subject` (string)
  - `original_url` (string) – usado como chave para evitar duplicidade
  - `file_path` (string) – caminho relativo no storage
  - `filename` (string)
  - `from_email` (string)
  - `received_at` (timestamp)
- Se o mesmo `original_url` já existir no banco, o registro **não será duplicado** (`firstOrCreate`).

**2.8 Marcação de E-mail**

- Após o processamento (com sucesso ou não), o e-mail **deve ser marcado como lido** (`markSeen()`).
- Isso evita que o mesmo e-mail seja processado repetidamente nas próximas verificações.

**2.9 Visualização no Painel**

- O painel mostrará todos os documentos salvos, ordenados por data de recebimento (mais recente primeiro).
- Cada linha deve exibir:
  - Data e hora do recebimento
  - Subject
  - Nome do arquivo (com link para download)
  - URL original (para referência)
- O link de download deve usar `Storage::url()`.

#### 3. Regras de Exceção e Segurança

- Se não houver URL válida no corpo → marcar e-mail como lido e ignorar.
- Se o remetente não for o correto → marcar como lido e ignorar.
- Erros durante o download não devem parar o processamento dos próximos e-mails.
- Todas as credenciais (usuário e senha de app do Gmail) devem vir do arquivo `.env`.
- O sistema não processará anexos (apenas URLs presentes no corpo).

#### 4. Regras de Performance e Simplicidade

- O polling deve ser leve e não sobrecarregar o servidor.
- Todo processamento pesado (download + salvamento) deve ser feito via **Queue (Job)**.
- O comando de polling deve ser idempotente e seguro para rodar múltiplas vezes.

---

### Resumo das Regras em forma de Checklist

- [ ] Só processa e-mail do remetente configurado
- [ ] Só processa e-mails não lidos
- [ ] Busca apenas e-mails dos últimos 25 minutos
- [ ] Extrai subject completo
- [ ] Extrai primeira URL válida do corpo
- [ ] Baixa o documento com timeout de 60s
- [ ] Salva arquivo em `documents/AAAA/MM/`
- [ ] Salva registro no banco (sem duplicidade)
- [ ] Marca e-mail como lido após processamento
- [ ] Exibe documentos no painel com link de download

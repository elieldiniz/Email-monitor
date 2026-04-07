# MVP - Docs Monitor (Versão 1.0)

## Definição do MVP

O MVP (Minimum Viable Product) do Docs Monitor é uma versão funcional e operacional que entrega o core value proposition: **automatizar o download de documentos recebidos via e-mail de um remetente específico**.

## Escopo do MVP

### Incluído no MVP ✅

1. **Conexão IMAP**
   - Conectar ao Gmail via IMAP usando DirectoryTree/ImapEngine
   - Configuração via arquivo `.env`

2. **Polling Automatizado**
   - Scheduler Laravel rodando a cada 15 minutos
   - Command `email:poll` funcional

3. **Filtragem de E-mails**
   - Buscar apenas e-mails UNSEEN
   - Filtrar por remetente específico
   - Janela de busca: últimos 25 minutos

4. **Extração de Dados**
   - Capturar subject completo
   - Extrair primeira URL válida do corpo (pdf, doc, docx, xlsx, zip, rar)

5. **Download e Armazenamento**
   - Baixar arquivo com timeout de 60s
   - Salvar em `storage/app/public/documents/YYYY/MM/`
   - Nome do arquivo preservado ou gerado automaticamente

6. **Persistência**
   - Model Document com migration
   - Campos: subject, original_url, file_path, filename, from_email, received_at
   - Prevenção de duplicidade via `firstOrCreate`

7. **Interface de Visualização**
   - Painel Livewire 3 em `/documentos`
   - Lista de documentos ordenados por data (mais recente primeiro)
   - Links para download via `Storage::url()`

8. **Marcação de E-mails**
   - Sempre marcar como lido após processamento
   - Evita reprocessamento

### Fora do Escopo do MVP ❌

| Feature | Motivo | Futura Versão |
|---------|--------|---------------|
| Suporte a anexos | Foco em URLs no corpo | v2.0 |
| Processamento de múltiplas URLs | MVP = primeira URL apenas | v2.0 |
| Fila com Redis | Database queue é suficiente | v2.0 |
| Múltiplos remetentes | Configuração única no MVP | v2.0 |
| Notificações real-time | Painel Livewire é suficiente | v2.0 |
| Dashboard com estatísticas | Lista simples no MVP | v2.0 |
| Autenticação no painel | Acesso direto no MVP | v2.0 |
| Testes automatizados | Testes manuais no MVP | v1.1 |
| API REST | Painel web é suficiente | v2.0 |
| Suporte a outros provedores | Gmail apenas no MVP | v2.0 |
| OCR em documentos | Fora do escopo inicial | v3.0 |
| Categorização automática | Fora do escopo inicial | v3.0 |

## Histórias de Usuário (MVP)

### HU-01: Configurar Sistema
**Como** administrador  
**Quero** configurar credenciais IMAP e remetente  
**Para** que o sistema possa acessar a caixa de e-mail correta  

**Critérios:**
- Configurar IMAP_HOST, IMAP_PORT, IMAP_USERNAME, IMAP_PASSWORD no .env
- Configurar DOCUMENT_SENDER no .env
- Rodar migrations sem erros

### HU-02: Polling Automático
**Como** sistema  
**Quero** verificar novos e-mails a cada 15 minutos  
**Para** processar documentos sem intervenção manual  

**Critérios:**
- Scheduler configurado no Kernel
- Command email:poll funcional
- Processamento via queue

### HU-03: Receber Documento
**Como** sistema  
**Quero** processar e-mails do remetente configurado  
**Para** extrair e baixar documentos automaticamente  

**Critérios:**
- Apenas e-mails UNSEEN
- Extrair subject e URL
- Baixar arquivo em até 60s
- Salvar no storage organizado por data
- Marcar e-mail como lido

### HU-04: Visualizar Documentos
**Como** usuário  
**Quero** ver lista de documentos baixados  
**Para** acessar os arquivos quando necessário  

**Critérios:**
- Acessar `/documentos`
- Ver lista ordenada por data (mais recente primeiro)
- Cada item mostra: data, subject, nome do arquivo, URL original
- Link funcional para download

### HU-05: Evitar Duplicatas
**Como** sistema  
**Quero** não processar o mesmo documento duas vezes  **Para** manter integridade dos dados  

**Critérios:**
- Usar firstOrCreate pela URL original
- Mesmo e-mail reprocessado não cria duplicata

## Critérios de Aceite do MVP

### Funcionais

- [ ] Sistema conecta ao Gmail IMAP com sucesso
- [ ] Scheduler executa a cada 15 minutos
- [ ] Processa apenas e-mails do remetente configurado
- [ ] Extrai subject corretamente
- [ ] Extrai primeira URL válida (extensões permitidas)
- [ ] Baixa arquivo em até 60 segundos
- [ ] Salva arquivo em estrutura YYYY/MM/
- [ ] Salva registro no banco com todos os campos
- [ ] Não cria duplicatas (mesma URL)
- [ ] Sempre marca e-mail como lido
- [ ] Painel exibe documentos ordenados por data
- [ ] Download via painel funciona corretamente

### Não-Funcionais

- [ ] Código segue padrões PSR-12
- [ ] Logs em todas as etapas críticas
- [ ] Tratamento de erros sem quebrar o fluxo
- [ ] Configurações via .env (sem hardcode)
- [ ] Documentação clara nos arquivos

## Testes de Validação do MVP

### Cenário 1: E-mail Válido
**Dado** que o sistema está configurado  
**E** um e-mail é enviado pelo remetente configurado com URL de PDF  
**Quando** o polling executa  
**Então** o arquivo é baixado e salvo  
**E** aparece no painel  
**E** o e-mail é marcado como lido  

### Cenário 2: E-mail sem URL
**Dado** que o sistema está configurado  
**E** um e-mail é enviado sem URL válida  
**Quando** o polling executa  
**Então** o e-mail é marcado como lido  
**E** nenhum documento é criado  

### Cenário 3: Remetente Errado
**Dado** que o sistema está configurado  
**E** um e-mail é enviado por outro remetente  
**Quando** o polling executa  
**Então** o e-mail é marcado como lido  
**E** ignorado (sem processamento)  

### Cenário 4: Duplicata
**Dado** que um documento já foi processado  
**E** o mesmo e-mail é reprocessado  
**Quando** o polling executa  
**Então** nenhuma duplicata é criada  
**E** o e-mail é marcado como lido  

## Pós-MVP (Roadmap)

### v1.1 - Estabilidade
- [ ] Testes automatizados (PHPUnit)
- [ ] Tratamento avançado de erros
- [ ] Logs estruturados
- [ ] Configurações de retry para jobs

### v2.0 - Features Avançadas
- [ ] Suporte a anexos de e-mail
- [ ] Processamento de múltiplas URLs por e-mail
- [ ] Redis para queues
- [ ] Múltiplos remetentes configuráveis
- [ ] Dashboard com estatísticas
- [ ] Filtros no painel (data, tipo, etc.)
- [ ] API REST

### v3.0 - Inteligência
- [ ] OCR em PDFs
- [ ] Categorização automática
- [ ] Busca por conteúdo
- [ ] Integração com outros serviços

## Documentação Relacionada

- [00-README.md](00-README.md) - Visão geral
- [01-ESTRATEGIA.md](01-ESTRATEGIA.md) - Estratégia completa
- [03-REGRAS-DE-NEGOCIO.md](03-REGRAS-DE-NEGOCIO.md) - Regras detalhadas
- [04-TECNOLOGIAS.md](04-TECNOLOGIAS.md) - Stack tecnológica
- [05-CHECKLIST-IMPLEMENTACAO.md](05-CHECKLIST-IMPLEMENTACAO.md) - Checklist de implementação

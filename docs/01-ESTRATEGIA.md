# Estratégia do Sistema - Docs Monitor

## Visão Estratégica

O Docs Monitor é um sistema leve e eficiente para automação de download de documentos recebidos via e-mail, utilizando arquitetura de **Polling** ao invés de **IDLE** para máxima confiabilidade e simplicidade.

## Por Que Polling (e Não IDLE)?

| Aspecto | Polling (Escolhido) | IDLE (Rejeitado) |
|---------|---------------------|------------------|
| **Complexidade** | Baixa | Alta |
| **Consumo de Recursos** | Mínimo (roda a cada 15min) | Mantém conexão persistente |
| **Confiabilidade** | Alta (sem conexões longas) | Média (quedas de conexão) |
| **Debug** | Fácil (logs claros) | Difícil (problemas de socket) |
| **Escalabilidade** | Simples (adicionar workers) | Complexa (gerenciar sockets) |
| **Custo de Infra** | Baixo | Médio/Alto |

## Decisões Estratégicas

### 1. Arquitetura de Camadas

```
┌─────────────────────────────────────────────┐
│           CAMADA DE APRESENTAÇÃO            │
│         (Livewire 3 + Blade + Tailwind)      │
├─────────────────────────────────────────────┤
│           CAMADA DE APLICAÇÃO               │
│    (Commands + Jobs + Service Providers)   │
├─────────────────────────────────────────────┤
│           CAMADA DE DOMÍNIO                  │
│         (Models + Regras de Negócio)         │
├─────────────────────────────────────────────┤
│           CAMADA DE INFRAESTRUTURA          │
│   (IMAP, HTTP Client, Storage, Database)     │
└─────────────────────────────────────────────┘
```

### 2. Padrões de Design

| Padrão | Aplicação | Benefício |
|--------|-----------|-----------|
| **Single Responsibility** | Cada classe tem uma única responsabilidade | Código limpo e testável |
| **Separation of Concerns** | IMAP em Command, processamento em Job, view em Livewire | Facilita manutenção |
| **Queue / Async** | Download e salvamento em Job | Scheduler não bloqueia |
| **Idempotência** | `firstOrCreate` pela URL | Evita duplicatas |
| **Fail-Safe** | Erros de download não quebram o fluxo | Sistema robusto |
| **Convention over Configuration** | Padrões Laravel | Menos código boilerplate |

### 3. Fluxo de Dados

```
E-mail no Gmail
       │
       ▼
[EmailPollChecker] ──▶ Busca UNSEEN do remetente
       │
       ▼
[ProcessNewEmailJob] ──▶ Extrai subject + URL
       │
       ├──▶ Download via HTTP (timeout 60s)
       │
       ├──▶ Salva em storage/app/public/documents/YYYY/MM/
       │
       └──▶ Persiste no banco (firstOrCreate)
       │
       ▼
[DocumentsPanel] ──▶ Exibe lista com download links
```

### 4. Estratégia de Processamento

**Polling Cycle (a cada 15 minutos):**

1. **Verificação**: Scheduler dispara `email:poll`
2. **Busca**: Command conecta ao IMAP e busca mensagens UNSEEN
3. **Filtro**: Apenas remetente configurado, últimos 25 minutos
4. **Distribuição**: Para cada e-mail, despacha um Job
5. **Processamento**: Job extrai dados, baixa arquivo, salva no storage
6. **Persistência**: Salva no banco com proteção contra duplicidade
7. **Finalização**: Marca e-mail como lido no Gmail
8. **Visualização**: Painel Livewire exibe documentos atualizados

### 5. Estratégia de Armazenamento

**Estrutura de Diretórios:**
```
storage/app/public/documents/
├── 2026/
│   ├── 03/                    # Março
│   │   ├── documento1.pdf
│   │   ├── planilha.xlsx
│   │   └── arquivo.zip
│   └── 04/                    # Abril
│       └── ...
```

**Vantagens:**
- Organização cronológica clara
- Fácil backup e arquivamento
- Separado do código da aplicação
- Acessível via Storage::url()

### 6. Estratégia de Segurança

- **Credenciais**: Todas no `.env` (nunca hardcoded)
- **Filtro de Remetente**: Apenas e-mails de origem confiável
- **Validação de URL**: Só processa URLs com extensões permitidas
- **Timeout**: Download limitado a 60s (evita bloqueios)
- **Marcação**: Sempre marca como lido (evita reprocessamento)

### 7. Estratégia de Escalabilidade

**Fase 1 (Atual):**
- Polling a cada 15 minutos
- Queue driver: database
- Single worker

**Fase 2 (Futura):**
- Redis para queue
- Múltiplos queue workers
- Supervisor para gerenciamento

**Fase 3 (Futura):**
- Horizontal scaling com múltiplos servidores
- S3 para storage de arquivos
- Database read replicas

## Cronograma de Implementação

| Fase | Descrição | Tempo Estimado |
|------|-----------|----------------|
| 1 | Configuração inicial | 15 min |
| 2 | Banco de dados e Model | 20 min |
| 3 | Job de Processamento | 30 min |
| 4 | Command de Polling | 25 min |
| 5 | Scheduler | 10 min |
| 6 | Livewire Panel | 30 min |
| 7 | Rotas e Testes | 20 min |

**Total Estimado:** ~2.5 horas

## Métricas de Sucesso

- ✅ Processa e-mails em até 15 minutos após recebimento
- ✅ Zero duplicatas de documentos
- ✅ 100% dos e-mails válidos processados
- ✅ Nenhum e-mail deixado como não lido
- ✅ Painel atualizado em tempo real

## Documentação Relacionada

- [00-README.md](00-README.md) - Visão geral
- [02-MVP.md](02-MVP.md) - Escopo do MVP
- [03-REGRAS-DE-NEGOCIO.md](03-REGRAS-DE-NEGOCIO.md) - Regras detalhadas
- [04-TECNOLOGIAS.md](04-TECNOLOGIAS.md) - Stack tecnológica
- [05-CHECKLIST-IMPLEMENTACAO.md](05-CHECKLIST-IMPLEMENTACAO.md) - Checklist de implementação

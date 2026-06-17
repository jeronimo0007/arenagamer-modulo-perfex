# ArenaGamer - Módulo Perfex CRM

Módulo de integração entre o ArenaGamer e o Perfex CRM. Permite gerenciar torneios, planos, presets, usuários e auditoria diretamente pelo painel administrativo do Perfex.

## Instalação

1. Copie a pasta `arenagamer` para `modules/` no seu Perfex CRM:
```bash
cp -r arenagamer /path/to/perfex/modules/
```

2. Acesse o painel admin do Perfex → **Setup** → **Modules**

3. Ative o módulo **ArenaGamer**

4. Configure a conexão com a API em **ArenaGamer** → **Configurações**

## Configuração

Após ativar o módulo, acesse **ArenaGamer → Configurações** e preencha:

| Campo | Descrição |
|-------|-----------|
| URL da API | URL base da ArenaGamer API (ex: `http://localhost:8080/api/v1`) |
| Email do Admin | Email do usuário ADMIN na API |
| Senha do Admin | Senha do usuário ADMIN na API |
| Sincronização Automática | Habilitar polling periódico |
| Intervalo | Frequência de sincronização (1min - 1h) |

## Funcionalidades

### Dashboard
- Visão geral com totais de torneios e usuários
- Torneios recentes com ações rápidas
- Status da conexão com a API

### Torneios
- Listagem paginada de todos os torneios
- Detalhes completos (info, partidas, datas)
- Ações: Abrir/fechar inscrições, gerar chaves, agendar partidas, cancelar

### Planos
- Visualização dos planos disponíveis (Free, Pro, Enterprise)
- Limites e configurações de cada plano

### Presets / Jogos
- Lista de presets de jogos configurados (CS2, LoL, Valorant, etc.)
- Informações de tamanho de time e plataforma

### Tiers de Créditos
- Tabela de custos por faixa de participantes

### Usuários
- Listagem paginada de todos os usuários
- Filtro por role (ADMIN, MANAGER, PLAYER)

### Auditoria
- Logs de auditoria da API
- Histórico de ações e operações

### Webhooks
- Documentação dos eventos disponíveis
- Instruções para configuração via API

## Permissões

O módulo registra as seguintes permissões no Perfex:
- **View ArenaGamer** - Visualizar dados
- **Create ArenaGamer** - Criar recursos
- **Edit ArenaGamer** - Editar recursos e configurações
- **Delete ArenaGamer** - Excluir recursos

## Estrutura

```
arenagamer/
├── arenagamer.php          # Ponto de entrada do módulo
├── install.php             # Script de instalação
├── controllers/
│   └── Arenagamer.php      # Controller principal
├── models/
│   └── Arenagamer_model.php # Model para sync logs
├── libraries/
│   └── ArenaGamer_api.php  # Client HTTP para a API
├── helpers/
│   └── arenagamer_helper.php # Funções auxiliares
├── views/admin/            # Views do painel admin
├── assets/                 # CSS e JS
└── language/               # Traduções
```

## Requisitos

- Perfex CRM 3.0+
- PHP 7.4+ com extensão cURL
- ArenaGamer API rodando e acessível

# reserva-salas — Instruções Claude

## Memória de Conversas (OBRIGATÓRIO)

**Ao final de TODA conversa**, salvar resumo em:
`_claude-memory/conversas/YYYY-MM-DD.md`

Formato do arquivo:

```markdown
# Conversa — YYYY-MM-DD

## O que foi feito
- item 1
- item 2

## Decisões tomadas
- decisão e motivo

## Problemas encontrados
- problema → solução

## Próximos passos sugeridos
- item
```

Se já existe arquivo do mesmo dia, **adicionar** nova seção ao final (não sobrescrever).

Atualizar também `_claude-memory/MEMORY.md` com ponteiro para o arquivo se for novo.

## Contexto do Projeto

- PHP + MySQL + Bootstrap 5.3.3, sem Composer/npm
- Banco: `reserva_salas` (auto-provisionado)
- 2 perfis: `admin` (acesso total), `lider` (só próprias reservas)
- Conflito de horário centralizado em `includes/conflitos.php` → `checkFullConflict()`
- CSRF obrigatório em toda ação de estado
- Resumo completo do projeto: `_claude-memory/projeto-resumo.md`

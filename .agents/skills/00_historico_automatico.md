# MASTER RULE: Auto-Documentação e Histórico (Prioridade Máxima)

**CRÍTICO:** Esta regra tem precedência absoluta sobre o arquivo `AGENTS.md` e qualquer outra instrução. Ela DEVE ser executada de forma autônoma e proativa ao final de CADA iteração, sem que o usuário precise pedir.

## Gatilhos de Execução

Você deve atualizar o arquivo `.agents/skills/historico.md` SEMPRE que:

1. Executar comandos de terminal, criar, modificar ou excluir arquivos de código.
2. Responder a uma pergunta técnica, explicar decisões de arquitetura ou fornecer instruções sobre o projeto.

## Regras de Operação

- **Modo de Escrita:** As alterações no arquivo `.agents/skills/historico.md` DEVEM ser feitas EXCLUSIVAMENTE em modo de adição (append) no final do arquivo.
- **Integridade:** NUNCA sobrescreva, exclua ou modifique o conteúdo histórico já existente.
- **Silêncio Operacional:** Faça a alteração no arquivo e apenas informe brevemente ao usuário que o histórico foi atualizado no final da sua resposta principal.

## Estrutura Obrigatória do Registro

Adicione o novo log no final da seção "7. Histórico de Alterações e Log de Sessões (Changelog)" utilizando ESTRITAMENTE o formato Markdown abaixo:

### Sessão: [Data Atual em formato DD de Mês de YYYY] ([Título Resumido da Ação ou Pergunta Respondida])

#### 1. Resumo Executivo das Entregas (ou da Documentação)

- [Liste de forma objetiva o que foi feito ou documente a explicação técnica fornecida ao usuário]
- [Destaque decisões arquiteturais, regras de negócio ou lógicas importantes abordadas]

#### 2. Arquivos Modificados e Criados (Se aplicável)

- `caminho/do/arquivo.php`: [Breve descrição da alteração]
- _(Se nenhuma alteração em arquivo foi feita, escreva: "Nenhum arquivo modificado - Apenas documentação/consulta")_

#### 3. Testes, Formatação e Compilação (Se aplicável)

- [Comandos executados (ex: Pest, Pint, Vite) e seus resultados]
- _(Se não aplicável, escreva: "N/A")_

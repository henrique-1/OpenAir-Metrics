# Dumps e Backups do Banco de Dados (OpenAir-Metrics)

Este diretório é destinado ao armazenamento de dumps e backups estruturais e de dados do banco MariaDB da aplicação.

## Nome Padrão do Dump
- **`openair_backup.sql`**

## Como Exportar Manualmente (via PHPMyAdmin)
1. Acesse o **PHPMyAdmin** local (`http://localhost:8080`).
2. Selecione a base de dados **`OpenAir`**.
3. Clique na aba **Exportar (Export)**.
4. Escolha o método de exportação **Rápido (Quick)** ou **Personalizado (Custom)** com formato **SQL**.
5. Salve o arquivo baixado neste diretório com o nome: `database/dumps/openair_backup.sql`.

## Como Importar o Dump

### Via PHPMyAdmin:
1. Abra o PHPMyAdmin e selecione o banco de dados `OpenAir`.
2. Acesse a aba **Importar (Import)**.
3. Selecione o arquivo `database/dumps/openair_backup.sql`.
4. Clique em **Executar (Import)**.

### Via Container Podman / MariaDB CLI:
```bash
# Executando diretamente no container Podman:
podman exec -i <nome_do_container> mariadb -u tcc -p"&1+=p5:y06ERFZm9^<DS7o" OpenAir < database/dumps/openair_backup.sql

# Ou via host se tiver o cliente mariadb instalado:
mariadb -h 127.0.0.1 -P 3306 -u tcc -p"&1+=p5:y06ERFZm9^<DS7o" OpenAir < database/dumps/openair_backup.sql
```

# 💇‍♀️ Hairbook - Sistema de Agendamento Online para Cabeleireiros 📅

## Sobre o Projeto

O Hairbook é um sistema de agendamento online desenvolvido para cabeleireiros e salões de beleza, permitindo que clientes agendem seus serviços de forma rápida e conveniente. O objetivo é simplificar o processo de agendamento, otimizando o tempo tanto dos clientes quanto dos profissionais.

## Funcionalidades

* **Agendamento de Serviços:** Clientes podem selecionar o serviço desejado, data e horário disponível.
* **Listagem Dinâmica de Serviços:** Os serviços oferecidos são carregados diretamente do banco de dados.
* **Verificação de Disponibilidade:** O sistema verifica horários livres com base na duração do serviço, agendamentos existentes e horários bloqueados.
* **Feedback Visual:** Mensagens de sucesso ou erro são exibidas ao cliente após a tentativa de agendamento.
* **Design Responsivo:** Interface adaptável a diferentes tamanhos de tela (desktop, tablet, celular) via Bootstrap.
* **Segurança Básica:** Sanitização de entradas e uso de Prepared Statements para prevenir Injeção SQL e XSS.

## Tecnologias Utilizadas

O projeto Hairbook foi construído utilizando as seguintes tecnologias:

* **Frontend:**
    * **HTML5:** Estrutura da página web.
    * **CSS3:** Estilização e design.
    * **JavaScript:** Interatividade no lado do cliente e comunicação com a API.
    * **Bootstrap 5.3:** Framework CSS para um design responsivo e componentes pré-estilizados.
* **Backend:**
    * **PHP:** Linguagem de programação para a lógica de negócio da API.
    * **MySQL:** Sistema de gerenciamento de banco de dados relacional para armazenamento de informações (serviços, agendamentos, etc.).
* **Ferramentas:**
    * **Git:** Sistema de controle de versão.
    * **XAMPP/WAMP/MAMP (ou similar):** Ambiente de desenvolvimento local (servidor Apache e MySQL).

## Estrutura de Pastas

```
hairbook/
├── public/                 # Contém os arquivos acessíveis publicamente pelo navegador (HTML, CSS, JS)
│   ├── index.html          # Página principal do formulário de agendamento
│   ├── css/                # Folhas de estilo CSS
│   ├── js/                 # Scripts JavaScript
│   └── ...
├── app/                    # Contém a lógica do Back-end em PHP
│   ├── api/                # Endpoints da API (api.php)
│   │   └── api.php         # Lida com as requisições AJAX do frontend
│   ├── config/             # Arquivos de configuração
│   │   └── database.php    # Configuração de conexão com o banco de dados (lê do .env)
│   └── ...
├── database/               # Scripts SQL do banco de dados
│   └── schema.sql          # Script para criar as tabelas e popular dados iniciais
├── .env                    # Variáveis de ambiente (credenciais sensíveis - IGNORE PELO GIT)
├── .gitignore              # Lista de arquivos e pastas a serem ignorados pelo Git
├── README.md               # Este arquivo de documentação
```

## Como Configurar e Rodar o Projeto Localmente

Siga os passos abaixo para colocar o Hairbook em funcionamento no seu ambiente de desenvolvimento.

1.  **Clone o Repositório:**
    ```bash
    git clone [https://github.com/klebermkardel/hairbook.git](https://github.com/klebermkardel/hairbook.git)
    cd hairbook
    ```

2.  **Configurar o Servidor Web (XAMPP/WAMP/MAMP):**
    * Certifique-se de ter um ambiente de servidor local (como XAMPP, WAMP ou MAMP) instalado e configurado.
    * Mova a pasta `hairbook` clonada para o diretório de documentos do seu servidor web (ex: `htdocs` para XAMPP/Apache). Ex: `C:\xampp\htdocs\hairbook`.

3.  **Configurar o Banco de Dados MySQL:**
    * Acesse sua ferramenta de gerenciamento MySQL (phpMyAdmin, MySQL Workbench, etc.).
    * **Crie um novo banco de dados** chamado `hairbook`.
    * Execute o script SQL localizado em `database/schema.sql`. Este script criará as tabelas `servicos`, `horarios_bloqueados` e `agendamentos`, e populará a tabela `servicos` com dados de exemplo.

4.  **Configurar Variáveis de Ambiente:**
    * Na **raiz do seu projeto** (`hairbook/`), crie um arquivo chamado `.env`.
    * Adicione suas credenciais do banco de dados neste arquivo. **Substitua pelos seus dados reais:**
        ```
        DB_SERVER=localhost
        DB_USERNAME=root
        DB_PASSWORD=sua_senha_do_banco_de_dados
        DB_NAME=hairbook
        DB_CHARSET=utf8mb4
        ```
    * Certifique-se de que o `.gitignore` (já incluído no repositório) está configurado para ignorar o arquivo `.env`.

5.  **Acessar a Aplicação:**
    * Inicie o Apache e o MySQL no seu painel de controle do XAMPP/WAMP/MAMP.
    * Abra seu navegador e acesse: `http://localhost/hairbook/public/`
    * O formulário de agendamento deve ser carregado e os serviços devem aparecer no dropdown.

## Contribuição

Contribuições são bem-vindas! Se você tiver sugestões ou quiser melhorar o projeto, sinta-se à vontade para:

1.  Abrir uma issue para relatar bugs ou sugerir funcionalidades.
2.  Criar um fork do repositório.
3.  Implementar suas alterações em uma nova branch.
4.  Abrir um Pull Request.

## Licença

Este projeto está licenciado sob a Licença MIT.

---

```bash
git add README.md
git commit -m "docs: Adiciona README.md do projeto"
git push
```
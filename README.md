<div align="center">

<img src="public/img/Nutri_original.png" alt="Mascote nútria da NutrIA" width="180">

# NutrIA

### Sistema Inteligente de Gestão e Acompanhamento Nutricional

Descreva o que você comeu, e a Inteligência Artificial estima calorias e nutrientes para você acompanhar suas metas.

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![XAMPP](https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white)
![NVIDIA](https://img.shields.io/badge/NVIDIA_API-76B900?style=for-the-badge&logo=nvidia&logoColor=white)
![Status](https://img.shields.io/badge/status-TCC_em_desenvolvimento-1d633d?style=for-the-badge)

</div>

---

## 📑 Sumário

- [Sobre o projeto](#-sobre-o-projeto)
- [Funcionalidades](#-funcionalidades)
- [Tecnologias utilizadas](#-tecnologias-utilizadas)
- [Demonstração do sistema](#-demonstração-do-sistema)
- [Arquitetura e organização](#-arquitetura-e-organização-do-projeto)
- [Pré-requisitos](#-pré-requisitos)
- [Instalação e configuração](#-instalação-e-configuração)
- [Configuração das APIs](#-configuração-das-apis)
- [Banco de dados](#-banco-de-dados)
- [Segurança e observações](#-segurança-e-observações)
- [Trabalhos futuros](#-trabalhos-futuros)
- [Autor e créditos](#-autor-e-créditos)

---

## 🥗 Sobre o projeto

O **NutrIA** é uma aplicação web para gerenciamento e acompanhamento nutricional. Ela reúne em um só lugar o registro das refeições, o acompanhamento de metas diárias e a evolução do peso.

**Problema.** Controlar a alimentação costuma exigir tabelas nutricionais, cálculos manuais e vários aplicativos. Isso torna o hábito cansativo e leva muitas pessoas a desistir do acompanhamento.

**Objetivo.** Reduzir esse esforço. O usuário descreve a refeição em texto, e a Inteligência Artificial estima calorias e nutrientes. O sistema compara o resultado com as metas pessoais e mostra o progresso do dia.

**Público-alvo.** Pessoas que desejam organizar a alimentação, seja para perder peso, manter a saúde ou ganhar massa. Esses são os três objetivos disponíveis no cadastro.

---

## ✨ Funcionalidades

### Diferenciais

| Destaque | Descrição |
| --- | --- |
| 🤖 **Análise nutricional por IA** | A partir da descrição da refeição, estima calorias, proteínas, carboidratos, gorduras, açúcares, fibras e sódio. |
| 💡 **Sugestão inteligente** | Na refeição registrada, a IA sugere uma alternativa da mesma categoria, levando em conta o objetivo do usuário. A sugestão não altera o registro original. |
| ⭐ **Refeições favoritas** | Refeições salvas podem ser reutilizadas sem nova análise da IA. |
| 📷 **Código de barras** | Consulta de produtos na base Open Food Facts para registrar seus dados nutricionais. |

### Funcionalidades implementadas

- **Cadastro e autenticação**
  - Criação de conta com dados biométricos.
  - Confirmação de e-mail por código de 6 dígitos, com validade de 15 minutos e opção de reenvio.
  - Login e logout.
- **Recuperação de senha**
  - Solicitação por e-mail.
  - Link com token, válido por 1 hora, para redefinir a senha.
- **Perfil e informações antropométricas**
  - Consulta e atualização de dados pessoais e biométricos: peso, altura, idade, gênero, nível de atividade e objetivo.
  - Visualização do IMC.
- **Registro de refeições**
  - Cadastro por descrição em texto, com tipo de refeição e data.
  - Consulta, edição e exclusão de registros.
  - O sistema impede o registro em datas futuras.
- **Calendário alimentar**
  - Refeições organizadas por data, com FullCalendar em português.
- **Dashboard**
  - Consumo diário de calorias e macronutrientes em relação às metas.
  - Gráfico de distribuição de macronutrientes.
  - Lista das refeições do dia.
- **Home**
  - Resumo dos macros do dia e modal para registrar refeições rapidamente.
- **Metas personalizadas**
  - Cálculo sugerido a partir do perfil e do objetivo.
  - Opção de definir os valores manualmente.
- **Evolução do peso**
  - Histórico de peso com gráfico.
- **Guia de Nutrientes**
  - Página educativa sobre calorias, proteínas, carboidratos, gorduras, sódio, açúcar e fibras.
- **Painel administrativo**
  - Consulta de usuários e de refeições, restrita a contas com perfil `admin`.
- **Experiência de uso**
  - Tema claro e escuro, layout responsivo e notificações visuais (toasts).

> As informações nutricionais geradas pela IA são **estimativas** e não substituem a orientação de profissionais de saúde.

---

## 🛠 Tecnologias utilizadas

| Tecnologia | Aplicação no projeto |
| --- | --- |
| **PHP** | Back-end, controllers, sessões e acesso ao banco via PDO. |
| **MySQL / MariaDB** | Armazenamento de usuários, refeições, nutrientes e metas. |
| **HTML5 e CSS3** | Páginas e estilos, com uma folha de CSS por página. |
| **JavaScript** | Interações e requisições assíncronas (`fetch`). |
| **XAMPP** | Ambiente local com Apache, PHP e MySQL. |
| **[PHPMailer](https://github.com/PHPMailer/PHPMailer)** | Envio de e-mails (verificação de conta e recuperação de senha) via SMTP. Instalado com o Composer. |
| **[FullCalendar](https://fullcalendar.io/) 6.1.11** | Calendário alimentar (carregado por CDN). |
| **[Chart.js](https://www.chartjs.org/)** | Gráficos de macronutrientes e de evolução do peso (carregado por CDN). |
| **API da NVIDIA** | Análise nutricional e sugestão inteligente por IA. |
| **[Open Food Facts](https://world.openfoodfacts.org/)** | Consulta de produtos por código de barras. |
| **Google Fonts e Material Symbols** | Tipografia e ícones. |

---

## 🖼 Demonstração do sistema

O repositório ainda **não contém capturas de tela** da aplicação. Os recursos visuais disponíveis são o logotipo e o mascote do projeto.

<div align="center">

| | | |
| :---: | :---: | :---: |
| <img src="public/img/Nutri_chef.png" alt="Nutri chef" width="160"> | <img src="public/img/Nutri_musc.png" alt="Nutri musculoso" width="160"> | <img src="public/img/Nutri_carteiro.png" alt="Nutri carteiro" width="160"> |
| Nutri chef | Nutri musculoso | Nutri carteiro (usado nos e-mails) |

</div>

<!--
Quando as capturas de tela forem adicionadas ao repositório, inclua-as aqui com os caminhos reais.
Telas sugeridas: página inicial (index.php), login e cadastro, home, dashboard,
gerenciamento de refeições, metas, perfil, Guia de Nutrientes e painel administrativo.
Exemplo: ![Dashboard](caminho/real/da/imagem.png)
-->

---

## 🗂 Arquitetura e organização do projeto

O projeto usa uma separação simples em **controllers** e **views**, sem framework. Não há uma pasta de *models*: o acesso ao banco é feito diretamente nos controllers, com PDO e *prepared statements*.

```text
TCC_NutrIA/
├── index.php                  # Página inicial (landing page)
├── composer.json              # Dependências PHP (PHPMailer)
├── public/
│   ├── banco/
│   │   ├── schema.sql         # Criação do banco "nutria", das tabelas e do administrador inicial
│   │   └── seeds.sql          # Dados de demonstração (opcional)
│   ├── css/                   # Uma folha de estilo por página
│   └── img/                   # Logotipos e mascote
└── src/
    ├── config/
    │   ├── conexao.php        # Conexão PDO com o MySQL
    │   └── funcoes.php        # Envio de e-mails (PHPMailer) e geração de tokens e códigos
    ├── controllers/           # Regras de negócio, ações e integrações externas
    └── views/                 # Páginas da aplicação
        └── adm/               # Páginas do painel administrativo
```

| Pasta | Responsabilidade |
| --- | --- |
| `src/config/` | Conexão com o banco e funções compartilhadas. |
| `src/controllers/` | Processam formulários e requisições assíncronas e integram as APIs externas. |
| `src/views/` | Páginas exibidas ao usuário, com o JavaScript de cada tela. |
| `public/css/` | Estilos de cada página. |
| `public/img/` | Logotipos, mascote e imagens. |
| `public/banco/` | Scripts SQL. |

### Principais controllers

| Controller | Função |
| --- | --- |
| `auth_controller.php` | Cadastro e login. |
| `verificar_codigo_controller.php`, `reenviar_codigo_controller.php` | Confirmação de e-mail. |
| `esqueci_senha_controller.php`, `redefinir_senha_controller.php` | Recuperação de senha. |
| `ia_controller.php` | Análise nutricional por IA (NVIDIA). |
| `sugestao_controller.php` | Sugestão inteligente de refeição alternativa (NVIDIA). |
| `alimento_controller.php` | Consulta de produtos por código de barras (Open Food Facts). |
| `refeicao_controller.php` | Registro, edição e exclusão de refeições. |
| `favorita_controller.php` | Refeições favoritas. |
| `metas_controller.php`, `get_macros_controller.php` | Metas e macros do dia. |
| `perfil_controller.php` | Perfil e biometria. |
| `logout_controller.php` | Encerramento da sessão. |

---

## 📋 Pré-requisitos

- **XAMPP** (Apache, PHP e MySQL/MariaDB) ou ambiente equivalente.
- **Composer**, para instalar o PHPMailer.
- Extensões PHP **PDO MySQL** e **cURL** habilitadas.
- Navegador atualizado.
- Conexão com a internet, necessária para a API da NVIDIA, o Open Food Facts, o envio de e-mails e as bibliotecas carregadas por CDN.
- **Conta Gmail com senha de aplicativo** para o envio de e-mails, já que o projeto usa `smtp.gmail.com`.
- **Chave de API da NVIDIA**, obtida em [build.nvidia.com](https://build.nvidia.com/).

---

## ⚙ Instalação e configuração

### 1. Instale e inicie o XAMPP

Inicie os módulos **Apache** e **MySQL** no painel do XAMPP.

### 2. Coloque o projeto no `htdocs`

```bash
cd C:\xampp\htdocs
git clone <url-do-repositorio> TCC_NutrIA
```

Também é possível extrair o `.zip` do projeto em `C:\xampp\htdocs\TCC_NutrIA`.

### 3. Crie e importe o banco de dados

Pelo phpMyAdmin (`http://localhost/phpmyadmin`), importe o arquivo `public/banco/schema.sql`. Ele cria o banco **`nutria`**, as tabelas e uma conta administrativa inicial.

Se preferir usar a linha de comando:

```bash
mysql -u root -p < public/banco/schema.sql
```

Opcionalmente, importe `public/banco/seeds.sql` para carregar dados de demonstração (usuários, biometria, metas, refeições e nutrientes).

### 4. Configure a conexão com o banco

Confira `src/config/conexao.php`. Os valores padrão do XAMPP já vêm configurados:

```php
$servidor = "localhost";
$usuario  = "root";
$senha    = "";
$banco    = "nutria";
```

Ajuste esses valores se o seu MySQL usar outras credenciais.

### 5. Instale as dependências

Na raiz do projeto:

```bash
composer install
```

### 6. Configure as chaves de API e o e-mail

Veja a seção [Configuração das APIs](#-configuração-das-apis).

### 7. Acesse a aplicação

Abra no navegador:

```text
http://localhost/TCC_NutrIA/
```

Crie uma conta, confirme o e-mail com o código recebido e faça login.

> **Conta administrativa.** O `schema.sql` cria um usuário com perfil `admin`, que acessa o painel administrativo ao fazer login. Defina uma senha própria para essa conta, ou troque o e-mail dela, antes de publicar o sistema. O campo `senha` armazena um hash gerado com `password_hash()` do PHP.

---

## 🔌 Configuração das APIs

### NVIDIA (análise nutricional e sugestão inteligente)

O NutrIA usa o endpoint de chat completions da NVIDIA (`https://integrate.api.nvidia.com/v1/chat/completions`) em dois pontos:

| Arquivo | Uso |
| --- | --- |
| `src/controllers/ia_controller.php` | Estimativa de nutrientes das refeições. |
| `src/controllers/sugestao_controller.php` | Sugestão de refeição alternativa. |

**Como configurar**

1. Crie uma conta e gere uma chave em [build.nvidia.com](https://build.nvidia.com/).
2. Informe a **sua chave** nos dois arquivos acima: na variável `$apiKey` em `ia_controller.php` e na constante `NVIDIA_API_KEY` em `sugestao_controller.php`.
3. O modelo utilizado fica definido no próprio código (`model` em `ia_controller.php` e `NVIDIA_MODELO_SUGESTAO` em `sugestao_controller.php`) e pode ser alterado conforme os modelos disponíveis na sua conta.

### Envio de e-mails (SMTP)

A verificação de conta e a recuperação de senha enviam e-mails pelo Gmail com PHPMailer. Em `src/config/funcoes.php`, informe o e-mail remetente (`Username`) e a **senha de aplicativo** do Google (`Password`). Para gerar a senha de aplicativo, a conta Google precisa ter a verificação em duas etapas ativada.

### Open Food Facts (código de barras)

Nenhuma chave é necessária. O `alimento_controller.php` consulta a API pública `https://world.openfoodfacts.org/api/v3/product/`, que retorna dados como nome, marca, quantidade e nutrientes. A disponibilidade e a completude dos dados dependem da base colaborativa.

---

## 🗄 Banco de dados

O banco **`nutria`** (utf8mb4) é composto por oito tabelas, todas ligadas à tabela `usuarios`.

| Tabela | Descrição |
| --- | --- |
| `usuarios` | Dados de acesso, verificação de e-mail e tipo de usuário (`usuario` ou `admin`). |
| `biometria` | Peso, altura, idade, gênero, nível de atividade e objetivo. |
| `historico_peso` | Registro de peso por data, com um registro por usuário por dia. |
| `metas_diarias` | Metas de calorias, proteínas, carboidratos e gorduras. A origem é `Sistema` ou `Manual`. |
| `refeicao` | Descrição, tipo e data de consumo da refeição. |
| `nutriente` | Calorias, proteínas, carboidratos, gorduras, açúcares, fibras e sódio de cada refeição. |
| `refeicoes_favoritas` | Refeições salvas com seus nutrientes. |
| `reset_senha` | Tokens de recuperação de senha, com validade e indicação de uso. |

**Relacionamentos**

```text
usuarios 1 ── N biometria
usuarios 1 ── N historico_peso
usuarios 1 ── 1 metas_diarias
usuarios 1 ── N refeicao ── 1 nutriente
usuarios 1 ── N refeicoes_favoritas
usuarios 1 ── N reset_senha
```

Todas as chaves estrangeiras usam `ON DELETE CASCADE`: ao excluir um usuário, seus dados vinculados também são removidos.

---

## 🔒 Segurança e observações

- **Proteja suas credenciais.** Não publique chaves de API, senhas de aplicativo ou senhas de banco em repositórios públicos. As configurações do NutrIA ficam definidas diretamente nos arquivos PHP listados na seção de APIs. Use credenciais próprias e revogue qualquer chave que tenha sido exposta.
- **Senhas.** São armazenadas como hash com `password_hash()` e verificadas com `password_verify()`.
- **Banco de dados.** As consultas usam PDO com *prepared statements*.
- **Verificação e recuperação.** O código de confirmação de e-mail expira em 15 minutos e o token de recuperação de senha expira em 1 hora.
- **Painel administrativo.** O acesso exige sessão com `tipo_usuario` igual a `admin`.
- **Ambiente local.** A configuração padrão do banco (`root` sem senha) serve apenas para desenvolvimento. Em produção, use um usuário próprio com senha forte.
- **Estimativas da IA.** Os valores nutricionais gerados pela Inteligência Artificial são **estimativas** e podem variar conforme a descrição, a quantidade e o preparo dos alimentos. Eles não substituem o acompanhamento de nutricionistas ou médicos.
- **Serviços externos.** A análise por IA, a busca por código de barras e o envio de e-mails dependem de serviços de terceiros e podem ficar indisponíveis.

---

## 🚀 Trabalhos futuros

O repositório não traz um roteiro documentado de melhorias planejadas. Por isso, esta seção não lista funcionalidades futuras. Quando houver planejamento formal, ele deve ser registrado aqui, sempre indicando que ainda **não faz parte** das funcionalidades implementadas.

---

## 👨‍💻 Autor e créditos

**Desenvolvedor:** Braian Santos Izidoro.

**Projeto:** NutrIA — Sistema Inteligente de Gestão e Acompanhamento Nutricional.

**Licença:** distribuído sob a licença MIT. Veja o arquivo [LICENSE](LICENSE).

**Créditos de tecnologias:** [PHPMailer](https://github.com/PHPMailer/PHPMailer), [FullCalendar](https://fullcalendar.io/), [Chart.js](https://www.chartjs.org/), [Open Food Facts](https://world.openfoodfacts.org/) e [NVIDIA](https://build.nvidia.com/).

---

<div align="center">

Feito com 💚 para uma alimentação mais consciente.

</div>

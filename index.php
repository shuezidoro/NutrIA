<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#F5F7F1">
  <meta name="description" content="Acompanhe refeições, consulte nutrientes e organize suas metas com a NutrIA.">
  <title>NutrIA — Nutrição inteligente para sua rotina</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0"rel="stylesheet">
  <link rel="stylesheet" href="public/css/index_style.css">
  <link rel="icon" type="image/svg+xml" href="public/img/logo.svg">
</head>

<body>
  <a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
  <header class="site-nav">
    <div class="nav-inner">
      <a class="brand" href="#inicio" aria-label="NutrIA — início"><img src="public/img/NutrIA_claro.svg" alt=""
          class="brand-logo"></a>
      <button class="menu-toggle" type="button" aria-label="Abrir menu" aria-expanded="false"
        aria-controls="nav-links"><span class="material-symbols-rounded" aria-hidden="true">menu</span></button>
      <nav class="nav-links" id="nav-links" aria-label="Navegação principal">
        <a class="nav-link active" href="#inicio">Início</a><a class="nav-link" href="#recursos">Recursos</a><a
          class="nav-link" href="#como-funciona">Como funciona</a>
      </nav>
    </div>
  </header>
  <main id="conteudo">
    <section class="hero section-anchor" id="inicio">
      <div class="hero-copy">
        <h1>Coma melhor.<br>Viva melhor com a <span>NutrIA.</span></h1>
        <p class="hero-description">Entenda o que está no seu prato, acompanhe suas metas e transforme pequenas escolhas
          em hábitos mais conscientes.</p>
        <div class="hero-actions">
          <a class="button" href="src/views/cadastro.php">Começar agora <span class="material-symbols-rounded"
              aria-hidden="true">arrow_forward</span></a>
          <a class="button button-outline" href="src/views/login.php">já possuo uma conta</a>
        </div>
        <div class="hero-benefits">
          <span><i class="material-symbols-rounded" aria-hidden="true">psychology</i>Análise com IA</span>
          <span><i class="material-symbols-rounded" aria-hidden="true">flag</i>Metas pessoais</span>
          <span><i class="material-symbols-rounded" aria-hidden="true">monitoring</i>Acompanhe seu progresso</span>
        </div>
      </div>
      <div class="hero-art" aria-label="Mascote da NutrIA">
        <div class="art-orbit orbit-one"></div>
        <div class="art-orbit orbit-two"></div>
        <div class="mascot-frame">
          <img src="public/img/Nutri_original.png" alt="Mascote nútria da NutrIA" class="mascot-main"
            onerror="this.hidden=true">
        </div>
      </div>
    </section>

    <section class="resource-section section-anchor" id="recursos">
      <div class="section-heading"><span class="section-kicker">Recursos</span>
        <h2>O que você precisa para cuidar melhor da sua alimentação.</h2>
        <p>Ferramentas para acompanhar sua rotina sem complicar o processo.</p>
      </div>
      <div class="feature-grid">
        <article class="feature-card">
          <div class="feature-icon"><i class="material-symbols-rounded" aria-hidden="true">psychology</i></div>
          <h3>Análise nutricional com IA</h3>
          <p>Consulte estimativas nutricionais dos alimentos informados e entenda melhor suas refeições.</p>
        </article>
        <article class="feature-card">
          <div class="feature-icon"><i class="material-symbols-rounded" aria-hidden="true">flag</i></div>
          <h3>Metas personalizadas</h3>
          <p>Organize seus objetivos diários e acompanhe os nutrientes de acordo com suas metas cadastradas.</p>
        </article>
        <article class="feature-card">
          <div class="feature-icon"><i class="material-symbols-rounded" aria-hidden="true">restaurant_menu</i></div>
          <h3>Registro de refeições</h3>
          <p>Tenha suas refeições organizadas para consultar o que registrou ao longo da rotina.</p>
        </article>
        <article class="feature-card">
          <div class="feature-icon"><i class="material-symbols-rounded" aria-hidden="true">monitoring</i></div>
          <h3>Evolução acompanhada</h3>
          <p>Visualize seu consumo e observe sua rotina com mais clareza, sem perder de vista seus objetivos.</p>
        </article>
      </div>
    </section>

    <section class="how-section section-anchor" id="como-funciona">
      <div class="how-copy"><span class="section-kicker">Como funciona</span>
        <h2>Uma rotina mais consciente começa com passos simples.</h2>
        <p>A NutrIA ajuda você a organizar informações sobre sua alimentação para tomar decisões mais informadas no dia
          a dia.</p>
      </div>
      <div class="steps-list">
        <article class="step-item"><span class="step-number">01</span>
          <div class="step-icon"><i class="material-symbols-rounded" aria-hidden="true">edit_note</i></div>
          <div>
            <h3>Registre sua refeição</h3>
            <p>Informe os alimentos que consumiu e organize seus registros.</p>
          </div>
        </article>
        <article class="step-item"><span class="step-number">02</span>
          <div class="step-icon"><i class="material-symbols-rounded" aria-hidden="true">query_stats</i></div>
          <div>
            <h3>Consulte os nutrientes</h3>
            <p>Veja as informações nutricionais estimadas e acompanhe suas metas.</p>
          </div>
        </article>
        <article class="step-item"><span class="step-number">03</span>
          <div class="step-icon"><i class="material-symbols-rounded" aria-hidden="true">trending_up</i></div>
          <div>
            <h3>Continue no seu ritmo</h3>
            <p>Use o acompanhamento como apoio para construir hábitos consistentes.</p>
          </div>
        </article>
      </div>
    </section>

    <section class="final-cta">
      <div class="cta-content"><span class="section-kicker">Comece hoje</span>
        <h2>Seu próximo passo pode ser simples.</h2>
        <p>Crie sua conta e comece a organizar sua alimentação com a NutrIA.</p><a class="button button-light"
          href="src/views/cadastro.php">Criar minha conta <i class="material-symbols-rounded"
            aria-hidden="true">arrow_forward</i></a>
      </div>

    </section>
  </main>

  <footer class="site-footer">
    <a class="brand footer-brand" href="#inicio" aria-label="NutrIA — voltar ao início"><img
        src="public/img/NutrIA_claro.svg" alt="" class="brand-logo"></a>
    <p>Acompanhe refeições, consulte nutrientes e organize suas metas com a NutrIA.</p>
    <nav aria-label="Links do rodapé">
      <a href="#inicio">Início</a>
      <a href="#recursos">Recursos</a>
      <a href="#como-funciona">Como funciona</a>
      <a href="src/views/cadastro.php">Criar conta</a>
      <a href="src/views/login.php">Fazer login</a>
    </nav>
    <small>© <?= date('Y') ?> NutrIA. As informações nutricionais são estimativas e não substituem a orientação de um
      profissional de saúde.</small>
  </footer>

  <script>
    (() => {
      const menuButton = document.querySelector('.menu-toggle');
      const nav = document.querySelector('#nav-links');
      const links = document.querySelectorAll('.nav-link');
      const sections = document.querySelectorAll('.section-anchor');
      menuButton?.addEventListener('click', () => {
        const open = menuButton.getAttribute('aria-expanded') === 'true';
        menuButton.setAttribute('aria-expanded', String(!open));
        menuButton.setAttribute('aria-label', open ? 'Abrir menu' : 'Fechar menu');
        nav.classList.toggle('is-open', !open);
      });
      const closeMenu = () => {
        nav.classList.remove('is-open');
        menuButton?.setAttribute('aria-expanded', 'false');
        menuButton?.setAttribute('aria-label', 'Abrir menu');
      };
      links.forEach(link => link.addEventListener('click', closeMenu));
      document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMenu(); });
      window.matchMedia('(min-width: 901px)').addEventListener('change', e => { if (e.matches) closeMenu(); });
      if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => entries.forEach(entry => {
          if (!entry.isIntersecting) return;
          links.forEach(link => link.classList.toggle('active', link.hash === '#' + entry.target.id));
        }), {rootMargin: '-30% 0px -60% 0px'});
        sections.forEach(section => observer.observe(section));
      }
    })();
  </script>
</body>

</html>
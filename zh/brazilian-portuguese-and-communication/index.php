<?php
/* Live & Learn Brazil — /zh/brazilian-portuguese-and-communication/ (versão chinesa).
   Texto original da página em inglês:
   Página do serviço de português e comunicação. Os dois formulários (abertura e
   fecho) são o formulário de contato padrão do site: postam em /contact/send.php
   com about=portuguese e voltam para cá com ?sent=1 quando não há JavaScript.
   A ilustração a lápis entra em PNG sem papel, já com o esvanecimento embutido. */
declare(strict_types=1);
header('Cache-Control: no-store');

$raiz = '../../';
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

/* O convite: um botão para a página de contato padrão, que traz o WhatsApp
   e o WeChat logo abaixo do formulário. */
function bp_convite(string $raiz, string $rotulo, string $texto, string $botao): void
{ ?>
      <div class="bp-convite">
        <?php if ($rotulo !== ''): ?><p class="dd-rotulo bp-rotulo"><?= e($rotulo) ?></p><?php endif; ?>
        <?php if ($texto !== ''): ?><p class="bp-convite-texto"><?= e($texto) ?></p><?php endif; ?>
        <a class="dd-btn" href="<?= e($raiz) ?>contact/?about=portuguese&amp;lang=zh"><?= e($botao) ?></a>
        <p class="bp-convite-nota">微信与 WhatsApp 也在该页面</p>
      </div>
<?php }
?>
<!doctype html>
<html lang="zh-Hans">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>巴西葡语与沟通 — Live &amp; Learn Brazil</title>
  <meta name="description" content="三位圣保罗大学（USP）教授教授巴西葡语与沟通：生存葡语、商务葡语及定制课程，面向家庭、高管与团队，线上或圣保罗线下授课。">
  <link rel="canonical" href="https://liveandlearnbrazil.com/zh/brazilian-portuguese-and-communication/">
  <link rel="alternate" hreflang="en" href="https://liveandlearnbrazil.com/brazilian-portuguese-and-communication/">
  <link rel="alternate" hreflang="zh-Hans" href="https://liveandlearnbrazil.com/zh/brazilian-portuguese-and-communication/">
  <link rel="alternate" hreflang="x-default" href="https://liveandlearnbrazil.com/brazilian-portuguese-and-communication/">
  <link rel="icon" type="image/svg+xml" href="../../img/live-and-learn-brazil-favicon.svg">
  <link rel="stylesheet" href="../../css/styles.css?v=20260921a">
  <link rel="stylesheet" href="../../css/due-diligence.css?v=20260923c">
  <link rel="stylesheet" href="../../css/full-advisory.css?v=20260921b">
  <link rel="stylesheet" href="../../css/portuguese.css?v=20260924b">
  <link rel="stylesheet" href="../../css/zh.css?v=20260927e">
</head>
<body>
<div class="page dd fa bp">

  <!-- ===== MENU ===== -->
  <header class="header">
    <a class="header-logo" href="../"><img src="<?= e($raiz) ?>img/live-and-learn-brazil-horizontal-light.svg" alt="Live &amp; Learn Brazil 标志"></a>
    <nav class="menu" aria-label="服务">
      <a href="../intercultural-due-diligence/">跨文化管理诊断与咨询</a>
      <a href="../innovation-ties/">创新纽带</a>
      <a href="./" aria-current="page">巴西葡语与沟通</a>
      <a href="../destination-services/">落地服务</a>
    </nav>
  </header>

  <main>

  <!-- ===== ABERTURA: título e formulário lado a lado ===== -->
  <section class="bp-abertura" aria-labelledby="bp-h1">
    <div class="bp-abertura-texto">
      <nav class="dd-rotulo bp-migalhas" aria-label="导航路径">
        <a href="../">首页</a> <span aria-hidden="true">/</span>
        <span>专业服务</span> <span aria-hidden="true">/</span>
        <span aria-current="page">巴西葡语与沟通</span>
      </nav>
      <p class="dd-rotulo dd-rotulo--claro">巴西葡语与沟通</p>
      <h1 class="bp-h1" id="bp-h1"><span class="zh-frase">会议听不懂潜台词，</span><span class="zh-frase">谈判把握不住分寸，</span><span class="zh-frase">日常办事处处碰壁。</span></h1>
      <p class="bp-lead">在巴西，措辞、语调、表情和发言节奏都会改变一次对话的结果。只学语法，远远不够。</p>
      <p class="bp-lead">我们倾听您的目标，了解您最常遇到的场景，研究最有效的表达方式，为您量身设计课程。</p>
      <div class="bp-numeros">
        <div class="bp-numero"><b>3</b><span>位 USP 教授</span></div>
        <div class="bp-numero"><b>10 年以上</b><span>对外葡语教学</span></div>
        <div class="bp-numero"><b>3</b><span>种课程匹配不同场景</span></div>
      </div>
      <p class="bp-lead">三位教授均毕业于圣保罗大学（USP），每位都有十年以上对外葡语教学经验和多年海外生活、工作经历。我们逐堂课打磨教学法，目标只有一个：您的成果。</p>
    </div>

    <div class="bp-coluna-convite">
<?php bp_convite($raiz, '与教授沟通', '告诉我们谁来学习、需要什么。两个工作日内回复。', '索取课程方案'); ?>
    </div>
  </section>

  <!-- ===== DOBRA 1: a conversa à esquerda, o foco à direita ===== -->
  <section class="bp-dobra bp-dobra--conversa" aria-labelledby="bp-t-foco">
    <img class="bp-ilustracao" src="../../img/ilustracao-conversa.png" alt="铅笔画：三人在药店招牌旁交谈" width="1240" height="523">
    <div class="bp-dobra-texto">
      <p class="dd-rotulo bp-rotulo" id="bp-t-foco">01 / 我们的重点：沟通与成果</p>
      <p>语法与发音是起点。我们关注您、您的家人和团队通过沟通取得的成果。</p>
      <p class="bp-citacao">课程立足两个基础：掌握核心规则，在对您最重要的场景中应对自如。</p>
    </div>
  </section>

  <!-- ===== MATERIAIS E METODOLOGIA ===== -->
  <section class="bp-secao bp-secao--azul" aria-labelledby="bp-t-materiais">
    <div class="bp-cabeca">
      <p class="dd-rotulo dd-rotulo--secao">02 / 教材与方法</p>
      <h2 id="bp-t-materiais">两堂课之间需要的一切</h2>
    </div>
    <div class="bp-materiais">
      <article class="bp-material">
        <h3>生存葡语 App</h3>
        <p>随身使用，内含面向真实场景的即用句型、词汇与复习资料。由我们的教授亲自录制，发音准确，附沟通文化提示。</p>
      </article>
      <article class="bp-material">
        <h3>数字教材</h3>
        <p>基于多年教学实践打磨的素材，按您的需求与学习场景调整。</p>
      </article>
      <article class="bp-material">
        <h3>线上或线下实时课程</h3>
        <p>按学员特点设计每一堂课，儿童、青少年、家庭或高管各有方法。团队成员毕业于教育学与语言文学专业，专长涵盖公众演讲、戏剧与自我表达、商务沟通。</p>
      </article>
    </div>
  </section>

  <!-- ===== O APLICATIVO: três telas ===== -->
  <section class="bp-app" aria-labelledby="bp-t-app">
    <div class="bp-cabeca bp-cabeca-dupla">
      <div class="bp-cabeca">
        <h2 id="bp-t-app">生存葡语，装进口袋</h2>
      </div>
      <p>超市、药店、银行、您的住所——在您最先到达的地方用得上的短句、练习与视频场景。每句话都由我们的教授录制。</p>
    </div>
    <div class="bp-telas">
      <figure class="bp-tela">
        <img src="../../img/app-frases.jpg" alt="App 界面：超市词汇卡片" width="640" height="1385" loading="lazy">
        <figcaption>短句</figcaption>
      </figure>
      <figure class="bp-tela">
        <img src="../../img/app-videos.jpg" alt="App 界面：肉类柜台的视频场景与注意事项" width="640" height="1385" loading="lazy">
        <figcaption>视频</figcaption>
      </figure>
      <figure class="bp-tela">
        <img src="../../img/app-exercicios.jpg" alt="App 界面：选择在收银台该说什么的练习" width="640" height="1385" loading="lazy">
        <figcaption>练习</figcaption>
      </figure>
    </div>
  </section>

  <!-- ===== OS TRÊS CURSOS ===== -->
  <section class="bp-secao bp-secao--gelo" id="courses" aria-labelledby="bp-t-cursos">
    <div class="bp-cabeca bp-cabeca-dupla">
      <div class="bp-cabeca">
        <p class="dd-rotulo dd-rotulo--secao">03 / 我们的课程</p>
        <h2 id="bp-t-cursos">三种路径，同一套方法</h2>
      </div>
      <p>每门课程均含数字教材与独家 App，内置 AI 资源，可在课间练习发音、语调、词汇与沟通句式。</p>
    </div>

    <div class="bp-cursos">
      <article class="bp-curso">
        <h3>生存葡语</h3>
        <p class="bp-curso-meta">10 小时 · 线上</p>
        <p class="bp-curso-lead">适用对象：刚到巴西、最初几周就要应对日常生活的外派人员与家属。</p>
        <ul class="bp-itens">
          <li><span>超市、餐厅、交通、就医、银行、住所与社区的核心句型和词汇</span></li>
          <li><span>语调与发音练习，让巴西人一次听懂</span></li>
          <li><span>问候、礼貌、寒暄与求助的文化提示</span></li>
          <li><span>生存葡语 App，教授录制短句即学即用</span></li>
        </ul>
      </article>

      <article class="bp-curso bp-curso--destaque">
        <h3>商务葡语</h3>
        <p class="bp-curso-meta">30–40 小时 · 线上或线下 · 个人或小班</p>
        <p class="bp-curso-lead">适用对象：与巴西伙伴、客户和团队共事的高管与专业人士。</p>
        <ul class="bp-itens">
          <li><span>会议、演示、谈判与商务午餐</span></li>
          <li><span>专业语体的邮件、WhatsApp 与电话</span></li>
          <li><span>巴西商务礼仪：建立关系、给予反馈、处理分歧、达成协议</span></li>
          <li><span>适配您所在行业与岗位的词汇和句式</span></li>
        </ul>
      </article>

      <article class="bp-curso">
        <h3>定制语言与沟通技能</h3>
        <p class="bp-curso-meta">线上或线下 · 个人、家庭或小班</p>
        <p class="bp-curso-lead">适用对象：有特定目标与场景的学员、家庭与团队。</p>
        <ul class="bp-itens">
          <li><span><b>家庭与儿童：</b>帮助孩子适应圣保罗的学校，与老师和校方沟通，满足全家日常所需</span></li>
          <li><span><b>跨文化沟通：</b>从社交场合到公共服务，自信应对巴西生活</span></li>
          <li><span><b>公众演讲：</b>演示、致辞与自我表达，依托团队的戏剧与公众沟通背景</span></li>
          <li><span><b>组织内部沟通：</b>与不同部门、层级互动，理解不成文规则，融入巴西企业文化</span></li>
        </ul>
      </article>
    </div>
  </section>

  <!-- ===== DOBRA 2: famílias e escolas, ilustração à direita ===== -->
  <section class="bp-dobra bp-dobra--placas" aria-labelledby="bp-t-familias">
    <div class="bp-dobra-texto">
      <p class="dd-rotulo bp-rotulo" id="bp-t-familias">04 / 家庭与学校</p>
      <p class="bp-grande">我们的两位教授多年协调圣保罗外派家庭与子女学校之间的沟通，帮助国际学生和家庭顺利适应，为在巴西的学业打好基础。</p>
    </div>
    <img class="bp-ilustracao" src="../../img/ilustracao-placas.png" alt="铅笔画：高架桥上方指向桑托斯、库巴唐与大海滩的路牌" width="1240" height="369">
  </section>

  <!-- ===== FECHO: o formulário completo ===== -->
  <section class="bp-fecho" id="contact" aria-label="从这里开始">
    <div class="bp-fecho-texto">
      <p class="dd-rotulo bp-rotulo">从这里开始</p>
      <p>课程方案从一次对话开始：谁在学习、哪些场景最重要、希望取得什么成果。两个工作日内回复，可用中文、葡萄牙语、英语或意大利语。</p>
      <div class="bp-whatsapp">
        <p>习惯用手机？请通过微信或 WhatsApp 留言：</p>
        <a href="https://wa.me/5511954395027">+55 11 95439 5027</a>
      </div>
    </div>

    <div class="bp-coluna-convite">
<?php bp_convite($raiz, '', '', '索取课程方案'); ?>
    </div>
  </section>

  </main>

  <!-- ===== RODAPÉ ===== -->
  <footer class="footer">
    <svg class="footer-curva" aria-hidden="true" viewBox="0 0 1000 1000" preserveAspectRatio="none"><path d="M0 900 C 250 900, 400 640, 520 540 S 800 180, 1000 100" fill="none" stroke="#DCC792" stroke-width="2" stroke-linecap="round" vector-effect="non-scaling-stroke"/></svg>
    <a class="footer-logo" href="../"><img src="<?= e($raiz) ?>img/live-and-learn-brazil-horizontal-dark.svg" alt="Live &amp; Learn Brazil 标志"></a>
    <a class="footer-site" href="https://liveandlearnbrazil.com">liveandlearnbrazil.com</a>
  </footer>

</div>
</body>
</html>

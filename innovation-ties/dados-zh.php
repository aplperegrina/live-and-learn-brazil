<?php
/* Live & Learn Brazil — /zh/innovation-ties/ : a copy chinesa da página (documento
   "Live-Learn-Brazil-copy-chines-revisada", seções 6, 7 e 7b) e os rótulos chineses
   do formulário. Os VALORES enviados continuam os de IT_FORM, em inglês, para que
   enviar.php valide igual e o e-mail da recepção chegue legível; aqui só muda o que
   a pessoa lê. Lido por partes.php quando $GLOBALS['it_lang'] === 'zh'. */

declare(strict_types=1);

const IT_COPY_ZH = [
    'titulo'      => '创新纽带 — Live & Learn Brazil',
    'descricao'   => '为寻求巴西创新合作伙伴的企业与研究机构牵线，从首次对话到签署协议全程居中协调。',
    'menu'        => ['跨文化管理诊断与咨询', '创新纽带', '巴西葡语与沟通', '落地服务'],
    'migalhas'    => ['首页', '专业服务', '创新纽带'],
    'rotulo'      => '创新纽带 · 国际创新合作对接与居中协调',
    'h1'          => '好项目，常常卡在跨国合作的第一步。',
    'sub'         => '找不到合适的巴西伙伴，沟通节奏对不上，预期与工作方式各不相同，项目一拖再拖。',
    'fecho_hero'  => '我们倾听您的项目目标，理解双方机构的运作方式，研究潜在合作方，提出切实可行的对接方案。',
    'slogan'      => '您的构想 · 共同的战略 · 更高的目标',
    'cta_primario' => '提交您的项目',
    'cta_secundario' => '预约初步通话',
    'wechat'      => '微信咨询',

    'intro_rotulo' => '01 / 为什么值得投入',
    'intro'        => '技术让不同国家的团队从第一天起就能共同开发项目，把过去数年的工作压缩到数月。跨文化合作是原创创新的重要来源，同时也对语言、预期与工作方式提出更高要求。有了恰当的支持、共同的沟通基础和清晰的工作机制，两支多元团队就能达成目标。',

    'oque_rotulo'  => '02 / 我们做什么',
    'oque_titulo'  => '对接，以及对接所需的一切',
    'oque_itens'   => [
        '为寻求巴西学术与商业研发伙伴的国际机构提供对接服务',
        '聚焦创新合作：应用研究、产品开发、技术转移、学术与商业合作协议及项目',
    ],
    'exp_rotulo'   => '我们的经验',
    'exp_intro'    => '团队累计数十年经验：',
    'exp_itens'    => [
        '国际学术与科研合作',
        '文化与合作协议',
        '学术与科研场景中的跨文化居中协调',
    ],

    'como_rotulo'  => '03 / 运作方式',
    'como_titulo'  => '六步，从项目描述到签署协议',
    'etapas'       => [
        ['介绍项目', '说明团队的想法、范围、目标与具体要求。'],
        ['初步图谱', '我们回复一份初步调研，列出与您画像匹配的潜在合作方。'],
        ['规划对接', '双方均表达兴趣后，与您共同规划下一步。'],
        ['代表团 / 商务考察（可选）', '如需赴巴，我们规划行程、住宿、日程、合作机构调研，以及合作所需的文化准备；可安排参观圣保罗州工业园区、研究机构与行业协会。'],
        ['现场支持', '会议口译与全程礼宾服务。'],
        ['正式确立合作', '决定推进时，合作律师提供法律指引并起草协议，保护双方的目标与流程。'],
    ],

    'quem_rotulo'  => '04 / 适用对象',
    'quem_titulo'  => '有项目、有条件走向海外的机构',
    'quem_itens'   => [
        ['拥有研发团队、寻求巴西研究或技术伙伴的企业', ''],
        ['计划与巴西机构合作的大学、研究所与科技园区', ''],
        ['推进学术、科学或商业合作计划的政府机构与国际组织', ''],
    ],

    'preco_rotulo' => '05 / 价格',
    'preco_itens'  => [
        ['潜在合作方初步图谱', '申请获批后免费提供。'],
        ['对接规划、代表团来访与居中协调', '按项目报价，视机构数量、会议次数、访问时长与文件而定。初步通话后发送方案。'],
    ],

    'comecar_rotulo' => '开始',
    'comecar_titulo' => '告诉我们您的项目以及您在寻找的合作伙伴。',

    'form_rotulo'  => '',
    'form_titulo'  => '创新纽带 · 合作申请',
    'form_lead'    => '请填写表单，申请一次线上沟通。',
    'form_intro'   => '创新纽带服务于已有明确项目、具备开展国际合作条件的机构，通常包含至少一次代表团来访。以下问题帮助我们评估匹配度，准备有针对性的初步图谱。5 个工作日内回复。',
    'obrigatorio'  => '*',
    'obrigatorio_nota' => '带星号字段为必填项。',
    'enviar'       => '提交您的项目',
    'enviando'     => '发送中…',
    'contador_antes' => '已写 ',
    'contador'     => '字，上限 500 字',
    'contador_aviso' => '项目描述请控制在 500 字以内。',
    'selecione'    => '请选择',
    'erro'         => '提交失败，请重试，或致信 reception@liveandlearnbrazil.com。',

    'fecho_titulo' => '想先聊聊？',
    'fecho_texto'  => '预约初步通话，我们回复具体时间。工作语言：中文、葡萄牙语、英语、意大利语。',
    'fecho_cta'    => '预约初步通话',
];

/* Títulos das cinco seções do formulário, na ordem de IT_FORM */
const IT_FORM_ZH_SECOES = ['第一部分 · 您的机构', '第二部分 · 您的项目', '第三部分 · 当前阶段', '第四部分 · 预算', '第五部分 · 其他'];

/* Por campo: 'r' = rótulo, 'dica' = ajuda, 'opcoes' = valor em inglês => rótulo em chinês
   (a ordem deste mapa é a ordem em que as opções aparecem). */
const IT_FORM_ZH = [
    'org_name'   => ['r' => '机构名称'],
    'org_type'   => ['r' => '机构类型', 'opcoes' => [
        'University or research institute' => '大学或研究所',
        'Company (R&D or innovation area)' => '企业（研发或创新部门）',
        'Technology park or incubator' => '科技园区或孵化器',
        'Government body' => '政府机构',
        'International or nonprofit organization' => '国际或非营利组织']],
    'country'    => ['r' => '国家'],
    'website'    => ['r' => '网址', 'dica' => '请包含 https://'],
    'your_name'  => ['r' => '您的姓名'],
    'position'   => ['r' => '职务'],
    'email'      => ['r' => '邮箱'],
    'phone'      => ['r' => '电话 / 微信 / WhatsApp'],
    'authority'  => ['r' => '您是否获授权代表贵机构主导此合作？', 'opcoes' => [
        'Yes, I am the decision-maker' => '是，我是决策者',
        'I lead the project and report to a decision-maker' => '我主导项目，向决策者汇报',
        'I am gathering information for my team' => '我在为团队收集信息']],
    'area'       => ['r' => '研究或创新领域'],
    'description' => ['r' => '项目描述：想法、范围与目标'],
    'partner_type' => ['r' => '您在巴西寻找哪类合作伙伴？', 'opcoes' => [
        'University or research center' => '大学或研究中心',
        'Company' => '企业',
        'Government or public institution' => '政府或公共机构',
        'Not sure yet' => '尚未确定']],
    'outcome'    => ['r' => '预期成果', 'opcoes' => [
        'Joint research' => '联合研究',
        'Product development' => '产品开发',
        'Technology transfer' => '技术转移',
        'Pilot project' => '试点项目',
        'Academic exchange' => '学术交流',
        'Commercial agreement' => '商业协议']],
    'institution' => ['r' => '是否已有意向巴西机构？', 'opcoes' => [
        'Yes, we have identified one or more' => '是，已确定一家或多家',
        'Yes, and we are already in contact' => '是，且已在接洽',
        'No, we need help finding partners' => '否，需要协助寻找']],
    'institution_names' => ['r' => '请填写机构名称'],
    'previous'   => ['r' => '贵机构此前是否尝试在巴西建立合作？', 'opcoes' => [
        'Yes, and it resulted in an agreement' => '是，并已达成协议',
        'Yes, but it did not move forward' => '是，但未能推进',
        'No, this is our first initiative' => '否，这是首次']],
    'previous_why' => ['r' => '请简述原因'],
    'start'      => ['r' => '计划何时开始？', 'opcoes' => [
        'Within 3 months' => '3 个月内',
        '3 to 6 months' => '3–6 个月',
        '6 to 12 months' => '6–12 个月',
        'No defined timeline' => '无明确时间表']],
    'funding'    => ['r' => '是否已为此合作分配资金？', 'opcoes' => [
        'Yes, approved' => '是，已获批',
        'Under application or approval' => '申请或审批中',
        'Not yet' => '尚未']],
    'budget'     => ['r' => '合作开展预估预算（含代表团来访）', 'opcoes' => [
        'Up to USD 10,000' => '10,000 美元以下',
        'USD 10,000 – 30,000' => '10,000–30,000 美元',
        'USD 30,000 – 80,000' => '30,000–80,000 美元',
        'Above USD 80,000' => '80,000 美元以上']],
    'delegation' => ['r' => '代表团预计出行人数', 'opcoes' => [
        '1–3' => '1–3',
        '4–8' => '4–8',
        'More than 8' => '8 人以上',
        'No visit planned' => '暂无来访计划']],
    'heard'      => ['r' => '您如何了解到我们？'],
    'language'   => ['r' => '联系首选语言', 'opcoes' => [
        '中文' => '中文',
        'English' => 'English',
        'Português' => 'Português',
        'Italiano' => 'Italiano']],
    'consent'    => ['r' => '我同意 Live & Learn Brazil 使用此信息评估我的申请并与我联系。'],
];

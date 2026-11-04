<?php
/* Textos dos e-mails e rótulos de tipos e características em português, inglês e chinês.
   Tom sóbrio, sem exclamações. As traduções para o chinês devem ser revisadas por quem domina o idioma. */

const MM_IDIOMAS = ['pt', 'en', 'zh'];

const MM_TIPOS = [
    'APARTAMENTO_TIPO' => ['Apartamento', 'Apartment', '公寓'],
    'APARTAMENTO_GARDEN' => ['Apartamento garden', 'Garden apartment', '花园公寓'],
    'COBERTURA' => ['Cobertura', 'Penthouse', '顶层公寓'],
    'DUPLEX' => ['Duplex', 'Duplex', '复式公寓'],
    'TRIPLEX' => ['Triplex', 'Triplex', '三层复式'],
    'FLAT' => ['Flat', 'Serviced apartment', '酒店式公寓'],
    'KITNET' => ['Kitnet', 'Studio', '小型公寓'],
    'LOFT' => ['Loft', 'Loft', '阁楼公寓'],
    'STUDIO' => ['Studio', 'Studio', '开间公寓'],
    'CASA_TIPO' => ['Casa', 'House', '独栋住宅'],
    'CASA_DE_VILA' => ['Casa de vila', 'Townhouse', '联排住宅'],
    'CASA_EM_CONDOMINIO' => ['Casa em condomínio', 'House in gated community', '封闭社区住宅'],
    'SOBRADO' => ['Sobrado', 'Two-story house', '两层住宅'],
];

/* tipo do formulário => tipos da Nonstop */
const MM_TIPOS_FORM = [
    'apartamento' => ['APARTAMENTO_TIPO', 'APARTAMENTO_GARDEN', 'DUPLEX', 'TRIPLEX'],
    'casa' => ['CASA_TIPO', 'CASA_DE_VILA', 'SOBRADO'],
    'casa_condominio' => ['CASA_EM_CONDOMINIO'],
    'cobertura' => ['COBERTURA'],
    'studio' => ['STUDIO', 'KITNET', 'LOFT', 'FLAT'],
    'indiferente' => [],
];

const MM_CARAC = [
    'ADEGA_CLIMATIZADA' => ['Adega climatizada', 'Wine cellar', '恒温酒窖'],
    'AGUA_QUENTE' => ['Água quente', 'Hot water system', '热水系统'],
    'AQUECIMENTO_CENTRAL' => ['Aquecimento central', 'Central heating', '中央供暖'],
    'AQUECIMENTO_SOLAR' => ['Aquecimento solar', 'Solar heating', '太阳能供暖'],
    'AR_CONDICIONADO_CENTRAL' => ['Ar-condicionado central', 'Central air conditioning', '中央空调'],
    'AREA_SERVICO' => ['Área de serviço', 'Laundry area', '洗衣区'],
    'BANHEIRO_SERVICO' => ['Banheiro de serviço', 'Service bathroom', '佣人卫生间'],
    'CHURRASQUEIRA' => ['Churrasqueira', 'Barbecue grill', '烧烤炉'],
    'CLOSET' => ['Closet', 'Walk-in closet', '衣帽间'],
    'COPA' => ['Copa', 'Breakfast room', '早餐厅'],
    'COZINHA_INTEGRADA' => ['Cozinha integrada', 'Open kitchen', '开放式厨房'],
    'DEPENDENCIA_DE_FUNCIONARIO' => ['Dependência de funcionário', 'Staff quarters', '佣人房'],
    'DEPOSITO' => ['Depósito', 'Storage room', '储藏室'],
    'DESPENSA' => ['Despensa', 'Pantry', '食品储藏室'],
    'ELEVADOR_PRIVATIVO' => ['Elevador privativo', 'Private elevator', '私人电梯'],
    'ESPACO_GOURMET' => ['Varanda gourmet', 'Gourmet balcony', '美食阳台'],
    'GAS_ENCANADO' => ['Gás encanado', 'Piped gas', '管道燃气'],
    'HALL_PRIVATIVO' => ['Hall privativo', 'Private hall', '私人门厅'],
    'HIDROMASSAGEM' => ['Hidromassagem', 'Whirlpool bath', '按摩浴缸'],
    'HOME_OFFICE' => ['Home office', 'Home office', '家庭办公室'],
    'HOME_THEATER' => ['Home theater', 'Home theater', '家庭影院'],
    'INFRAESTRUTURA_AR_CONDICIONADO' => ['Infraestrutura para ar-condicionado', 'Air conditioning ready', '空调预留'],
    'JANELA_TETO_CHAO' => ['Janelas do piso ao teto', 'Floor-to-ceiling windows', '落地窗'],
    'JARDIM' => ['Jardim', 'Garden', '花园'],
    'LAREIRA' => ['Lareira', 'Fireplace', '壁炉'],
    'LAVABO' => ['Lavabo', 'Powder room', '客用卫生间'],
    'MOBILIADO' => ['Mobiliado', 'Furnished', '带家具'],
    'MOVEIS_PLANEJADOS' => ['Móveis planejados', 'Built-in furniture', '定制家具'],
    'OFURO' => ['Ofurô', 'Soaking tub', '日式浴桶'],
    'PISCINA' => ['Piscina privativa', 'Private pool', '私人泳池'],
    'PISO_MADEIRA' => ['Piso de madeira', 'Wood flooring', '木地板'],
    'PISO_PORCELANATO' => ['Piso de porcelanato', 'Porcelain flooring', '瓷砖地板'],
    'PISO_LAMINADO' => ['Piso laminado', 'Laminate flooring', '复合地板'],
    'PISO_VINILICO' => ['Piso vinílico', 'Vinyl flooring', '塑胶地板'],
    'QUINTAL' => ['Quintal', 'Backyard', '后院'],
    'SACADA' => ['Sacada', 'Balcony', '阳台'],
    'SALA_INTIMA' => ['Sala íntima', 'Family room', '家庭起居室'],
    'SALA_CINEMA' => ['Sala de cinema', 'Cinema room', '影音室'],
    'SAUNA_SECA' => ['Sauna seca', 'Dry sauna', '干蒸房'],
    'SAUNA_UMIDA' => ['Sauna úmida', 'Steam room', '湿蒸房'],
    'VISTA_PARA_CIDADE' => ['Vista para a cidade', 'City view', '城市景观'],
    'VISTA_PANORAMICA' => ['Vista panorâmica', 'Panoramic view', '全景视野'],
    'VISTA_PERMANENTE' => ['Vista permanente', 'Unobstructed view', '永久景观'],
    // condomínio
    'ACADEMIA' => ['Academia', 'Gym', '健身房'],
    'ACEITA_AIRBNB' => ['Aceita locação por temporada', 'Short-term rentals allowed', '允许短租'],
    'ACESSO_PCD' => ['Acessibilidade', 'Accessibility', '无障碍设施'],
    'AREA_DE_LAZER' => ['Área de lazer', 'Leisure area', '休闲区'],
    'BICICLETARIO' => ['Bicicletário', 'Bicycle storage', '自行车存放处'],
    'BRINQUEDOTECA' => ['Brinquedoteca', 'Playroom', '儿童游戏室'],
    'CINEMA' => ['Cinema', 'Cinema', '影院'],
    'CONTROLE_DE_ACESSO' => ['Controle de acesso', 'Access control', '门禁系统'],
    'COWORKING' => ['Coworking', 'Coworking space', '共享办公区'],
    'ELEVADOR' => ['Elevador', 'Elevator', '电梯'],
    'ELEVADOR_SOCIAL' => ['Elevador social', 'Passenger elevator', '客梯'],
    'ELEVADOR_DE_SERVICO' => ['Elevador de serviço', 'Service elevator', '货梯'],
    'ESPACO_KIDS' => ['Espaço infantil', 'Kids area', '儿童区'],
    'ESPACO_PETS' => ['Espaço pet', 'Pet area', '宠物区'],
    'FITNESS' => ['Fitness', 'Fitness room', '健身室'],
    'GARAGEM_TODA_COBERTA' => ['Garagem coberta', 'Covered parking', '有顶车库'],
    'GERADOR' => ['Gerador', 'Backup generator', '备用发电机'],
    'PET_FRIENDLY' => ['Aceita animais', 'Pet friendly', '可养宠物'],
    'PISCINA_ADULTO' => ['Piscina', 'Pool', '泳池'],
    'PISCINA_COBERTA' => ['Piscina coberta', 'Indoor pool', '室内泳池'],
    'PISCINA_AQUECIDA' => ['Piscina aquecida', 'Heated pool', '恒温泳池'],
    'PISCINA_INFANTIL' => ['Piscina infantil', "Children's pool", '儿童泳池'],
    'PLAYGROUND' => ['Playground', 'Playground', '儿童乐园'],
    'PROXIMO_AO_METRO' => ['Próximo ao metrô', 'Near the subway', '近地铁'],
    'PORTARIA_24_HORAS' => ['Portaria 24 horas', '24-hour front desk', '24小时门卫'],
    'PORTARIA_REMOTA' => ['Portaria remota', 'Remote concierge', '远程门卫'],
    'QUADRA_DE_TENIS' => ['Quadra de tênis', 'Tennis court', '网球场'],
    'QUADRA_DE_BEACH_TENNIS' => ['Quadra de beach tennis', 'Beach tennis court', '沙滩网球场'],
    'QUADRA_POLIESPORTIVA' => ['Quadra poliesportiva', 'Sports court', '综合运动场'],
    'SALAO_DE_FESTAS' => ['Salão de festas', 'Party room', '宴会厅'],
    'SALA_DE_JOGOS' => ['Sala de jogos', 'Game room', '游戏室'],
    'SAUNA' => ['Sauna', 'Sauna', '桑拿'],
    'SEGURANCA_24H' => ['Segurança 24 horas', '24-hour security', '24小时安保'],
    'SPA' => ['Spa', 'Spa', '水疗中心'],
    'VAGA_CARRO_ELETRICO' => ['Recarga para carro elétrico', 'EV charging', '电动车充电'],
    'VAGAS_DE_VISITANTE' => ['Vagas de visitante', 'Visitor parking', '访客停车位'],
    'ZELADOR' => ['Zelador', 'Building manager', '物业管理员'],
];

function mm_rotulo(array $tabela, string $chave, string $lang): string {
    $i = array_search($lang, MM_IDIOMAS, true) ?: 0;
    if (isset($tabela[$chave])) return $tabela[$chave][$i];
    $t = mb_strtolower(str_replace('_', ' ', $chave));
    return mb_strtoupper(mb_substr($t, 0, 1)) . mb_substr($t, 1);
}

const MM_T = [
    'assunto' => ['Suas opções no Matchmaking Imobiliário', 'Your options from the Real Estate Matchmaking', '您的房产匹配结果'],
    'assunto_sem' => ['Seu pedido no Matchmaking Imobiliário', 'Your Real Estate Matchmaking request', '您的房产匹配申请'],
    'cabecalho' => ['Matchmaking Imobiliário', 'Real Estate Matchmaking', '房产匹配'],
    'ola' => ['Olá%s.', 'Hello%s.', '您好%s。'],
    'abertura' => [
        'Seguem %d opções selecionadas para o seu pedido: %s, até %d minutos de %s até %s, na faixa de %s.',
        'Here are %d options selected for your request: %s, up to %d minutes by %s to %s, in the range of %s.',
        '以下是根据您的需求挑选的 %1$d 个选项：%2$s，%4$s %3$d 分钟内可达%5$s，价格区间 %6$s。',
    ],
    'comprar' => ['compra', 'purchase', '购买'],
    'alugar' => ['locação', 'rental', '租赁'],
    'modo' => [
        'carro' => ['carro', 'car', '驾车'], 'transporte' => ['transporte público', 'public transport', '公共交通'],
        'pe' => ['caminhada', 'walking', '步行'], 'bicicleta' => ['bicicleta', 'bicycle', '骑行'],
    ],
    'trajeto' => ['%d min de %s até %s, às 8h de um dia útil', '%d min by %s to %s, at 8 a.m. on a weekday', '工作日早上8点%2$s约 %1$d 分钟到达%3$s'],
    'seu_destino' => ['o seu trabalho', 'your workplace', '您的工作地点'],
    'area' => ['m² de área privativa', 'm² private area', '平方米室内面积'],
    'dorm' => ['dormitórios', 'bedrooms', '卧室'], 'dorm1' => ['dormitório', 'bedroom', '卧室'],
    'suite' => ['suítes', 'suites', '套房'], 'suite1' => ['suíte', 'suite', '套房'],
    'vaga' => ['vagas', 'parking spaces', '车位'], 'vaga1' => ['vaga', 'parking space', '车位'],
    'andar' => ['Andar %s', '%s floor', '%s楼层'],
    'andar_baixo' => ['baixo', 'Low', '低'], 'andar_medio' => ['médio', 'Middle', '中'], 'andar_alto' => ['alto', 'High', '高'],
    'ano' => ['Construído em %d', 'Built in %d', '建于 %d 年'],
    'preco' => ['Preço', 'Price', '价格'], 'aluguel' => ['Aluguel', 'Rent', '租金'],
    'preco_consulta' => ['Preço sob consulta', 'Price on request', '价格面议'],
    'condominio' => ['Condomínio', 'Condo fee', '物业费'], 'iptu' => ['IPTU', 'Property tax (IPTU)', '房产税'],
    'mes' => ['/mês', '/month', '/月'], 'ano_txt' => ['/ano', '/year', '/年'],
    'no_imovel' => ['No imóvel', 'In the unit', '房屋特点'], 'no_condominio' => ['No condomínio', 'In the building', '小区设施'],
    'local' => ['Localização aproximada em %s. O círculo marca a região do imóvel, com raio de %d m.',
                'Approximate location in %s. The circle marks the area of the property, with a %d m radius.',
                '大致位置：%s。圆圈标示房屋所在区域，半径 %d 米。'],
    'botao' => ['Quero visitar esta opção', 'I would like to visit this option', '我想参观此房源'],
    'wa_msg' => ['Olá, Andrea. Quero visitar a opção %s do Matchmaking Imobiliário.', 'Hello Andrea. I would like to visit option %s from the Real Estate Matchmaking.', 'Andrea 您好，我想参观房产匹配中的 %s 房源。'],
    'codigo' => ['Opção %s', 'Option %s', '房源 %s'],
    'atendimento' => ['Atendimento presencial em português, inglês e chinês.', 'In-person service in Portuguese, English and Chinese.', '可提供葡萄牙语、英语和中文的线下服务。'],
    'estimativa' => ['Tempos calculados pelo Google Maps para um dia útil às 8h. Valores informados pelo anunciante, sujeitos a confirmação.',
                     'Travel times calculated with Google Maps for a weekday at 8 a.m. Values provided by the listing owner, subject to confirmation.',
                     '通勤时间按工作日早上8点由 Google 地图计算。价格由房源方提供，以最终确认为准。'],
    'descadastrar' => ['Não quer mais receber estas mensagens? Descadastrar', 'No longer want these messages? Unsubscribe', '不想再收到这些邮件？退订'],
    'sem_resultado' => [
        "Recebemos o seu pedido no Matchmaking Imobiliário. No momento não há opções no acervo que atendam ao tempo de trajeto e às condições informadas.\n\nVou procurar opções com parceiros e respondo em até 3 dias úteis.",
        "We received your Real Estate Matchmaking request. At the moment there are no listings that meet the travel time and conditions you provided.\n\nI will look for options with partners and reply within 3 business days.",
        "我们已收到您的房产匹配申请。目前没有符合您通勤时间和条件的房源。\n\n我将通过合作伙伴为您寻找，并在3个工作日内回复。",
    ],
];

function mm_t(string $chave, string $lang, ...$args): string {
    $i = array_search($lang, MM_IDIOMAS, true) ?: 0;
    $s = MM_T[$chave][$i] ?? MM_T[$chave][0];
    return $args ? vsprintf($s, $args) : $s;
}

function mm_t_modo(string $modo, string $lang): string {
    $i = array_search($lang, MM_IDIOMAS, true) ?: 0;
    return MM_T['modo'][$modo][$i] ?? $modo;
}

function mm_reais(?float $v, string $lang = 'pt'): string {
    if ($v === null) return '';
    return 'R$ ' . number_format($v, 0, ',', '.');
}

function mm_faixa_andar(?int $andar): ?string {
    if ($andar === null) return null;
    if ($andar <= 4) return 'andar_baixo';
    if ($andar <= 12) return 'andar_medio';
    return 'andar_alto';
}

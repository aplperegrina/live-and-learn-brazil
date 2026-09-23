<?php
/* Live & Learn Brazil — /innovation-ties/ : toda a copy da página e a definição dos
   campos do formulário, num só lugar, para as versões PT, ZH e IT só traduzirem isto.
   Lido por index.php (os dois modelos) e por enviar.php. Não é servido ao navegador. */

declare(strict_types=1);

const IT_COPY = [
    'titulo'      => 'Innovation Ties — Live & Learn Brazil',
    'descricao'   => 'We connect international companies and research centers looking for innovation partners in Brazil, and we mediate each step from the first conversation to the agreement.',
    'rotulo'      => 'INNOVATION TIES',
    'h1'          => 'Your idea. A shared strategy. Greater goals.',
    'sub'         => 'We connect international companies and research centers looking for innovation partners, and we mediate each step from the first conversation to the agreement.',
    'cta_primario' => 'Submit your project',
    'cta_secundario' => 'Schedule an initial call',

    'intro_rotulo' => '01 / WHY IT IS WORTH THE WORK',
    'intro_titulo' => 'The most original innovation happens between different cultures.',
    'intro'        => 'In the 2020s, technology connects production chains across cultures from the very first idea. Teams in different countries can now develop a project together from day one, compressing into months what used to take years. Cross-cultural partnerships are where some of the most original innovation happens. They are also demanding: different languages, expectations and ways of working can slow a promising project down. With the right support, a shared ground for communication and clear working mechanisms, two diverse teams can reach their goal and build something new together.',

    'oque_rotulo'  => '02 / WHAT WE DO',
    'oque_titulo'  => 'Connection, and everything the connection needs.',
    'oque_itens'   => [
        'Connection services for international institutions looking for academic and business research partners in Brazil.',
        'Focus on innovation cooperation: applied research, product development, technology, and academic and business cooperation agreements and projects.',
    ],
    'exp_rotulo'   => 'OUR EXPERIENCE',
    'exp_intro'    => 'Our team brings decades of combined experience in:',
    'exp_itens'    => [
        'International academic and research partnerships',
        'Cultural and cooperation agreements',
        'Intercultural mediation in academic and research settings',
    ],

    'como_rotulo'  => '03 / HOW IT WORKS',
    'como_titulo'  => 'Six steps, from the first description to the signed agreement.',
    'etapas'       => [
        ['Tell us about your project.', 'Describe your team’s idea, scope, goals and specifications.'],
        ['Receive a first mapping.', 'We reply with initial field research on potential partners that match your profile.'],
        ['Plan the connection.', 'When both sides show interest, we plan the next steps with you.'],
        ['Delegation visit (optional).', 'If the connection involves a visit to Brazil, we plan every step with you: travel, accommodation, schedule, a study of the partner institutions, and the cultural processes that support a successful partnership.'],
        ['On-site support.', 'Interpreting in your meetings and concierge service throughout the visit.'],
        ['Formalize the partnership.', 'When you decide to move forward, our partner lawyers provide full legal guidance and draft agreements that protect the goals and processes of both parties.'],
    ],

    'quem_rotulo'  => '04 / WHO IT IS FOR',
    'quem_titulo'  => 'Institutions with a project and the means to take it abroad.',
    'quem_itens'   => [
        ['Companies with R&D teams', 'looking for research or technology partners in Brazil.'],
        ['Universities, research institutes and technology parks', 'planning cooperation with Brazilian institutions.'],
        ['Government bodies and international organizations', 'developing academic, scientific or business cooperation programs.'],
    ],

    'preco_rotulo' => '05 / PRICING',
    'preco_itens'  => [
        ['Initial mapping of potential partners', 'Included after your request is approved.'],
        ['Connection planning, delegation visits and mediation', 'Priced per project, according to scope: number of institutions, meetings, visit length and documents. A proposal is sent after an initial call.'],
    ],

    'comecar_rotulo' => 'GET STARTED',
    'comecar_titulo' => 'Tell us about your project and the partner you are looking for.',

    'form_rotulo'  => 'INNOVATION TIES REQUEST',
    'form_titulo'  => 'Innovation Ties request',
    'form_intro'   => 'Innovation Ties works with institutions that have a defined project and the resources to develop an international partnership, which usually includes at least one delegation visit. The questions below help us assess fit and prepare a relevant first mapping. We reply within 5 business days.',
    'obrigatorio'  => '*',
    'obrigatorio_nota' => 'Fields marked with an asterisk are required.',
    'enviar'       => 'Submit your project',
    'enviando'     => 'Sending…',
    'contador'     => 'words of 500',
    'erro'         => 'Something went wrong. Please try again or write to us at reception@liveandlearnbrazil.com.',

    'fecho_titulo' => 'Prefer to talk first?',
    'fecho_texto'  => 'Ask for an initial call and we will come back with a time. We work in Portuguese, English, Mandarin and Italian.',
    'fecho_cta'    => 'Schedule an initial call',
];

/* Seções e campos do formulário. Cada campo: chave, rótulo, tipo e se é obrigatório.
   'opcoes' vale para escolha única (radio), múltipla (checkbox) e lista (select).
   'mostra' liga um campo condicional ao valor que o revela. */
const IT_FORM = [
    [
        'titulo' => 'Section 1 — Your organization',
        'campos' => [
            ['k' => 'org_name', 'r' => 'Organization name', 'tipo' => 'text', 'req' => true, 'max' => 200, 'auto' => 'organization'],
            ['k' => 'org_type', 'r' => 'Type of organization', 'tipo' => 'radio', 'req' => true, 'opcoes' => [
                'University or research institute', 'Company (R&D or innovation area)', 'Technology park or incubator', 'Government body', 'International or nonprofit organization']],
            ['k' => 'country', 'r' => 'Country', 'tipo' => 'pais', 'req' => true],
            ['k' => 'website', 'r' => 'Website', 'tipo' => 'url', 'req' => true, 'max' => 300, 'dica' => 'Include https://'],
            ['k' => 'your_name', 'r' => 'Your name', 'tipo' => 'text', 'req' => true, 'max' => 200, 'auto' => 'name'],
            ['k' => 'position', 'r' => 'Your position', 'tipo' => 'text', 'req' => true, 'max' => 200, 'auto' => 'organization-title'],
            ['k' => 'email', 'r' => 'Email', 'tipo' => 'email', 'req' => true, 'max' => 254, 'auto' => 'email'],
            ['k' => 'phone', 'r' => 'Phone / WeChat / WhatsApp', 'tipo' => 'text', 'req' => true, 'max' => 120, 'auto' => 'tel'],
            ['k' => 'authority', 'r' => 'Are you authorized to lead this partnership on behalf of your organization?', 'tipo' => 'radio', 'req' => true, 'opcoes' => [
                'Yes, I am the decision-maker', 'I lead the project and report to a decision-maker', 'I am gathering information for my team']],
        ],
    ],
    [
        'titulo' => 'Section 2 — Your project',
        'campos' => [
            ['k' => 'area', 'r' => 'Area of research or innovation', 'tipo' => 'text', 'req' => true, 'max' => 300],
            ['k' => 'description', 'r' => 'Project description: idea, scope and goals', 'tipo' => 'textarea', 'req' => true, 'max' => 5000, 'palavras' => 500],
            ['k' => 'partner_type', 'r' => 'What type of partner are you looking for in Brazil?', 'tipo' => 'checkbox', 'req' => true, 'opcoes' => [
                'University or research center', 'Company', 'Government or public institution', 'Not sure yet']],
            ['k' => 'outcome', 'r' => 'Expected outcome', 'tipo' => 'checkbox', 'req' => false, 'opcoes' => [
                'Joint research', 'Product development', 'Technology transfer', 'Pilot project', 'Academic exchange', 'Commercial agreement']],
        ],
    ],
    [
        'titulo' => 'Section 3 — Current stage',
        'campos' => [
            ['k' => 'institution', 'r' => 'Do you already have a Brazilian institution in mind?', 'tipo' => 'radio', 'req' => true, 'opcoes' => [
                'Yes, we have identified one or more', 'Yes, and we are already in contact', 'No, we need help finding partners']],
            ['k' => 'institution_names', 'r' => 'Please name the institution(s)', 'tipo' => 'text', 'req' => true, 'max' => 500,
             'mostra' => ['campo' => 'institution', 'valores' => ['Yes, we have identified one or more', 'Yes, and we are already in contact']]],
            ['k' => 'previous', 'r' => 'Has your organization tried to establish partnerships in Brazil before?', 'tipo' => 'radio', 'req' => true, 'opcoes' => [
                'Yes, and it resulted in an agreement', 'Yes, but it did not move forward', 'No, this is our first initiative']],
            ['k' => 'previous_why', 'r' => 'Tell us briefly why', 'tipo' => 'text', 'req' => false, 'max' => 500,
             'mostra' => ['campo' => 'previous', 'valores' => ['Yes, but it did not move forward']]],
            ['k' => 'start', 'r' => 'When do you plan to start?', 'tipo' => 'radio', 'req' => true, 'opcoes' => [
                'Within 3 months', '3 to 6 months', '6 to 12 months', 'No defined timeline']],
        ],
    ],
    [
        'titulo' => 'Section 4 — Budget',
        'campos' => [
            ['k' => 'funding', 'r' => 'Is there funding allocated to this partnership?', 'tipo' => 'radio', 'req' => true, 'opcoes' => [
                'Yes, approved', 'Under application or approval', 'Not yet']],
            ['k' => 'budget', 'r' => 'Estimated budget for the partnership development, including a delegation visit', 'tipo' => 'radio', 'req' => true, 'opcoes' => [
                'Up to USD 10,000', 'USD 10,000 – 30,000', 'USD 30,000 – 80,000', 'Above USD 80,000']],
            ['k' => 'delegation', 'r' => 'How many people would travel in a delegation visit?', 'tipo' => 'radio', 'req' => false, 'opcoes' => [
                '1–3', '4–8', 'More than 8', 'No visit planned']],
        ],
    ],
    [
        'titulo' => 'Section 5 — Final',
        'campos' => [
            ['k' => 'heard', 'r' => 'How did you hear about us?', 'tipo' => 'text', 'req' => false, 'max' => 300],
            ['k' => 'language', 'r' => 'Preferred language for contact', 'tipo' => 'radio', 'req' => true, 'opcoes' => ['English', '中文', 'Português', 'Italiano']],
            ['k' => 'consent', 'r' => 'I agree that Live & Learn Brazil may use this information to evaluate my request and contact me.', 'tipo' => 'consentimento', 'req' => true],
        ],
    ],
];

/* Combinação que marca o pedido como PRIORITY no e-mail interno (nunca mostrada ao visitante) */
const IT_PRIORIDADE = [
    'funding' => ['Yes, approved', 'Under application or approval'],
    'budget'  => ['USD 10,000 – 30,000', 'USD 30,000 – 80,000', 'Above USD 80,000'],
    'start'   => ['Within 3 months', '3 to 6 months'],
];

function it_paises(): array
{
    return ['Afghanistan','Albania','Algeria','Andorra','Angola','Antigua and Barbuda','Argentina','Armenia','Australia','Austria','Azerbaijan','Bahamas','Bahrain','Bangladesh','Barbados','Belarus','Belgium','Belize','Benin','Bhutan','Bolivia','Bosnia and Herzegovina','Botswana','Brazil','Brunei','Bulgaria','Burkina Faso','Burundi','Cabo Verde','Cambodia','Cameroon','Canada','Central African Republic','Chad','Chile','China','Colombia','Comoros','Congo','Congo (Democratic Republic)','Costa Rica','Côte d’Ivoire','Croatia','Cuba','Cyprus','Czechia','Denmark','Djibouti','Dominica','Dominican Republic','Ecuador','Egypt','El Salvador','Equatorial Guinea','Eritrea','Estonia','Eswatini','Ethiopia','Fiji','Finland','France','Gabon','Gambia','Georgia','Germany','Ghana','Greece','Grenada','Guatemala','Guinea','Guinea-Bissau','Guyana','Haiti','Honduras','Hong Kong','Hungary','Iceland','India','Indonesia','Iran','Iraq','Ireland','Israel','Italy','Jamaica','Japan','Jordan','Kazakhstan','Kenya','Kiribati','Korea (North)','Korea (South)','Kuwait','Kyrgyzstan','Laos','Latvia','Lebanon','Lesotho','Liberia','Libya','Liechtenstein','Lithuania','Luxembourg','Macao','Madagascar','Malawi','Malaysia','Maldives','Mali','Malta','Marshall Islands','Mauritania','Mauritius','Mexico','Micronesia','Moldova','Monaco','Mongolia','Montenegro','Morocco','Mozambique','Myanmar','Namibia','Nauru','Nepal','Netherlands','New Zealand','Nicaragua','Niger','Nigeria','North Macedonia','Norway','Oman','Pakistan','Palau','Palestine','Panama','Papua New Guinea','Paraguay','Peru','Philippines','Poland','Portugal','Qatar','Romania','Russia','Rwanda','Saint Kitts and Nevis','Saint Lucia','Saint Vincent and the Grenadines','Samoa','San Marino','São Tomé and Príncipe','Saudi Arabia','Senegal','Serbia','Seychelles','Sierra Leone','Singapore','Slovakia','Slovenia','Solomon Islands','Somalia','South Africa','South Sudan','Spain','Sri Lanka','Sudan','Suriname','Sweden','Switzerland','Syria','Taiwan','Tajikistan','Tanzania','Thailand','Timor-Leste','Togo','Tonga','Trinidad and Tobago','Tunisia','Türkiye','Turkmenistan','Tuvalu','Uganda','Ukraine','United Arab Emirates','United Kingdom','United States of America','Uruguay','Uzbekistan','Vanuatu','Vatican City','Venezuela','Vietnam','Yemen','Zambia','Zimbabwe'];
}

-- ============================================================
-- Plataforma AutoriaSCS - Vitrine da Página Inicial (CECAPE)
-- Script de criação das tabelas (MySQL 5.7+ / MariaDB 10.2+)
-- Executar uma única vez no banco de dados do site.
-- ============================================================

-- Garante que o arquivo (salvo em UTF-8) seja lido corretamente
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS autoriascs_vitrine
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE autoriascs_vitrine;

-- ------------------------------------------------------------
-- Usuários do painel administrativo
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_usuarios (
  id            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  nome          VARCHAR(120)     NOT NULL,
  email         VARCHAR(190)     NOT NULL,
  senha_hash    VARCHAR(255)     NOT NULL,
  ativo         TINYINT(1)       NOT NULL DEFAULT 1,
  ultimo_login  DATETIME         NULL,
  criado_em     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuário inicial: prof.flavio.spina@gmail.com / senha: Cecape@2026
-- IMPORTANTE: troque a senha no primeiro acesso (menu "Meu perfil").
INSERT INTO admin_usuarios (nome, email, senha_hash) VALUES
  ('Flávio Spina', 'prof.flavio.spina@gmail.com',
   '$2y$12$n0IfUHGhK1rshx9SJzQy0eb1QI8mSXzSM2YQrKr6UDQSFityWp/OO');

-- ------------------------------------------------------------
-- Parceiros (1ª seção da vitrine)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS parceiros (
  id             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  nome           VARCHAR(160)    NOT NULL,
  titulo         VARCHAR(200)    NULL COMMENT 'Título de destaque exibido no card',
  descricao      TEXT            NULL,
  imagem_url     VARCHAR(500)    NULL COMMENT 'URL da imagem/banner do parceiro',
  link_url       VARCHAR(500)    NULL COMMENT 'Link do botão (site do parceiro)',
  texto_botao    VARCHAR(60)     NOT NULL DEFAULT 'Acessar plataforma',
  destaques      TEXT            NULL COMMENT 'Itens de destaque, um por linha (ex.: Acesso gratuito - Cursos sem custo...)',
  observacao     VARCHAR(500)    NULL COMMENT 'Texto pequeno exibido no rodapé do card',
  ordem          INT             NOT NULL DEFAULT 0,
  ativo          TINYINT(1)      NOT NULL DEFAULT 1,
  criado_em      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_parceiros_ordem (ativo, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Cursos (2ª seção da vitrine - "Novos cursos no ar!")
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cursos (
  id             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  titulo         VARCHAR(220)    NOT NULL,
  descricao      TEXT            NULL,
  imagem_url     VARCHAR(500)    NULL COMMENT 'URL da capa do curso',
  link_url       VARCHAR(500)    NOT NULL COMMENT 'Link de inscrição (curso no Moodle)',
  carga_horaria  VARCHAR(40)     NULL COMMENT 'Ex.: 30 horas',
  texto_botao    VARCHAR(60)     NOT NULL DEFAULT '👉 Inscreva-se agora!',
  novo           TINYINT(1)      NOT NULL DEFAULT 1 COMMENT 'Exibe selo NOVO no card',
  ordem          INT             NOT NULL DEFAULT 0,
  ativo          TINYINT(1)      NOT NULL DEFAULT 1,
  criado_em      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cursos_ordem (ativo, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Configurações gerais da vitrine (títulos das seções etc.)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS configuracoes (
  chave          VARCHAR(80)     NOT NULL,
  valor          TEXT            NULL,
  atualizado_em  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO configuracoes (chave, valor) VALUES
  ('titulo_parceiros',    'Nossos parceiros'),
  ('subtitulo_parceiros', 'Instituições que caminham conosco por uma educação mais inclusiva e transformadora.'),
  ('titulo_cursos',       '🎓 Novos cursos no ar!'),
  ('subtitulo_cursos',    'Formações gratuitas e certificadas para os profissionais da educação de São Caetano do Sul.'),
  ('exibir_parceiros',    '1'),
  ('exibir_cursos',       '1')
ON DUPLICATE KEY UPDATE chave = chave;

-- ------------------------------------------------------------
-- Logs de atividade (auditoria do painel)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS logs_atividade (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id       INT UNSIGNED    NULL,
  usuario_nome     VARCHAR(120)    NULL,
  acao             VARCHAR(30)     NOT NULL COMMENT 'inserir | atualizar | excluir | login | login_falha | logout',
  entidade         VARCHAR(30)     NOT NULL COMMENT 'parceiro | curso | configuracao | usuario | sessao',
  entidade_id      INT UNSIGNED    NULL,
  descricao        VARCHAR(500)    NOT NULL,
  dados_anteriores JSON            NULL,
  dados_novos      JSON            NULL,
  ip               VARCHAR(45)     NULL,
  criado_em        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_logs_data (criado_em),
  KEY idx_logs_entidade (entidade, entidade_id),
  CONSTRAINT fk_logs_usuario FOREIGN KEY (usuario_id)
    REFERENCES admin_usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Controle de tentativas de login (proteção contra força bruta)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tentativas_login (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ip         VARCHAR(45)     NOT NULL,
  email      VARCHAR(190)    NOT NULL,
  sucesso    TINYINT(1)      NOT NULL DEFAULT 0,
  criado_em  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_tentativas (ip, email, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Dados de exemplo (baseados no conteúdo atual da página)
-- Podem ser editados/excluídos pelo painel administrativo.
-- ------------------------------------------------------------
INSERT INTO parceiros (nome, titulo, descricao, imagem_url, link_url, texto_botao, destaques, observacao, ordem) VALUES
  ('Plataforma de Educação Paralímpica',
   'Formação com impacto real',
   'A Plataforma de Educação Paralímpica foi criada para tornar a atividade física e esportiva cada vez mais acessível às pessoas com deficiência, promovendo conhecimento, sensibilização e qualificação profissional. Os cursos são gratuitos, com carga horária variada, abertos a todos os interessados e com certificação emitida pela Universidade Federal de Uberlândia.',
   'https://cecapescs.com.br/img_ext/Educacao-Paralimpica.png',
   'https://www.educacaoparalimpica.org.br',
   'Acessar plataforma',
   'Acesso gratuito - Cursos sem custo, abertos ao público e com conteúdo voltado à inclusão.\nDe 20 a 46 horas de formação - Carga horária consistente para aprofundamento e aplicação prática.\nCertificação pela UFU - Reconhecimento acadêmico que valoriza a trajetória formativa do participante.\nPontuação para evolução - Autorizados pela Secretaria Municipal de Educação no biênio 2025/2026, com pontuação no Eixo III de 0,1 ponto por hora.',
   'Informamos que, no processo de Promoção por Títulos para o biênio 2025/2026, os cursos são autorizados pela Secretaria Municipal de Educação, com pontuação válida por meio do Eixo III.',
   1);

INSERT INTO cursos (titulo, descricao, imagem_url, link_url, carga_horaria, ordem) VALUES
  ('A Linha como Linguagem: Expressões Gráficas e Documentação com Crianças de 3 a 5 Anos',
   'O curso explora a linha como linguagem na Educação Infantil, integrando corpo, gesto, materiais, documentação e criação. Em seis módulos, reúne experiências práticas e registros formativos sobre movimento, bordado, tridimensionalidade, argila, tintas naturais e exposição pedagógica com crianças pequenas.',
   'https://cecapescs.com.br/img_ext/Capa A linha como Linguagem 30horas.png',
   'https://eadcecape.com.br/ava/course/view.php?id=159', '30 horas', 1),
  ('A Capoeira na Dança: Movimento, Arte e Criação',
   'O curso explora a integração entre Capoeira e Dança, valorizando consciência corporal, criação coreográfica e diversidade cultural. Com conteúdos teóricos e práticos, apresenta vivências, fundamentos históricos e propostas pedagógicas alinhadas ao currículo de Dança de São Caetano do Sul.',
   'https://cecapescs.com.br/img_ext/Capa A capoeira na dança10h.png',
   'https://eadcecape.com.br/ava/course/view.php?id=158', '10 horas', 2),
  ('Práticas Democráticas na Escola',
   'O curso aborda fundamentos e práticas da gestão democrática na escola. Apresenta bases teóricas, marcos legais e instrumentos participativos como Conselho Escolar, grêmio estudantil e PPP, incentivando a construção coletiva de uma educação mais inclusiva, participativa e de qualidade.',
   'https://cecapescs.com.br/img_ext/Capa Práticas Democráticas na escola30h.png',
   'https://eadcecape.com.br/ava/course/view.php?id=157', '30 horas', 3),
  ('Construção e Construtividade – de pequenos engenheiros às grandes aprendizagens',
   'O curso explora a construção como linguagem de aprendizagem na Educação Infantil. Com base em referenciais teóricos e experiências formativas, aborda materiais, espaços, processos cognitivos, trabalho em pequenos grupos e documentação pedagógica.',
   'https://cecapescs.com.br/img_ext/Capa Construçconstrut10h.png',
   'https://eadcecape.com.br/ava/course/view.php?id=156', '10 horas', 4),
  ('O que esperar da Comunicação Aumentativa e Alternativa (CAA) na educação inclusiva?',
   'O curso apresenta fundamentos e práticas da Comunicação Aumentativa e Alternativa na educação inclusiva. Aborda acessibilidade, barreiras comunicacionais, diferenças entre fala, linguagem e comunicação, recursos, tecnologias e estratégias pedagógicas.',
   'https://cecapescs.com.br/img_ext/Capa A capoeira na dança10h.png',
   'https://eadcecape.com.br/ava/course/view.php?id=158', '20 horas', 5);

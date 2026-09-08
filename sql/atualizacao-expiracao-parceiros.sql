-- ============================================================
-- Atualização: prazo de exibição dos parceiros na página inicial
-- Executar UMA vez em instalações que já usam o schema.sql antigo.
-- (Instalações novas já recebem as colunas pelo schema.sql atualizado.)
-- ============================================================

SET NAMES utf8mb4;
USE autoriascs_vitrine;

ALTER TABLE parceiros
  ADD COLUMN exibir_valor INT NULL
    COMMENT 'Quantidade do tempo de exibição na página inicial (ex.: 30)'
    AFTER ativo,
  ADD COLUMN exibir_unidade ENUM('dias','meses','anos') NULL
    COMMENT 'Unidade do tempo de exibição (dias, meses ou anos)'
    AFTER exibir_valor,
  ADD COLUMN expira_em DATETIME NULL
    COMMENT 'Após esta data o parceiro sai da página inicial (continua na página de parceiros)'
    AFTER exibir_unidade;

CREATE INDEX idx_parceiros_expira ON parceiros (ativo, expira_em);

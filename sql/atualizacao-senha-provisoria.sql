-- ============================================================
-- Atualização: troca de senha obrigatória no primeiro acesso
-- Executar UMA vez em instalações que já usam o schema.sql antigo.
-- (Instalações novas já recebem a coluna pelo schema.sql atualizado.)
-- ============================================================

SET NAMES utf8mb4;
USE autoriascs_vitrine;

ALTER TABLE admin_usuarios
  ADD COLUMN senha_provisoria TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Se 1, o usuário é obrigado a trocar a senha no próximo login'
  AFTER ativo;

-- Versionado no repositório com valores padrão (2 / 3).
-- Para alinhar ao TicketConstants.php do seu deploy, rode na raiz do projeto:
--   php scripts/generate_pgm_sit_config.php
-- (sobrescreve este arquivo) e faça commit se quiser fixar os IDs reais.
--
-- C_TicketSituacaoResolvido = 2, C_TicketSituacaoFechado = 3 (placeholder)
DO $cfg$
BEGIN
  PERFORM set_config('pgm.sit_resolvido', '2', false);
  PERFORM set_config('pgm.sit_fechado', '3', false);
END $cfg$;

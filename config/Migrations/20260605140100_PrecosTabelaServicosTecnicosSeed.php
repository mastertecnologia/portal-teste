<?php
use App\Utility\PrecosTabelaServicosTecnicosCatalog;
use Migrations\AbstractMigration;

/**
 * Cria a tabela de preços "Serviços Técnicos" e os 26 itens em precos_tabela_itens + produtos (serviço).
 */
class PrecosTabelaServicosTecnicosSeed extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('precos_tabelas') || !$this->hasTable('precos_tabela_itens') || !$this->hasTable('produtos')) {
			return;
		}

		$empresas = $this->fetchAll('SELECT id FROM empresas ORDER BY id');
		if ($empresas === []) {
			return;
		}

		$items = PrecosTabelaServicosTecnicosCatalog::items();
		$ano = (int)date('Y');
		$vigIni = $ano . '-01-01';
		$vigFim = $ano . '-12-31';
		$now = date('Y-m-d H:i:s');
		$codigoTabela = PrecosTabelaServicosTecnicosCatalog::TABELA_CODIGO;
		$nomeTabela = PrecosTabelaServicosTecnicosCatalog::TABELA_NOME;
		$descTabela = 'Tabela oficial de serviços técnicos PGM (formatação, remoto, impressoras, etc.)';

		foreach ($empresas as $empRow) {
			$idempresa = (int)$empRow['id'];
			$tabelaId = $this->ensureTabela($idempresa, $codigoTabela, $nomeTabela, $descTabela, $vigIni, $vigFim, $now);
			if ($tabelaId <= 0) {
				continue;
			}
			$ordem = 0;
			foreach ($items as $item) {
				$ordem++;
				$produtoId = $this->ensureProduto($idempresa, $item);
				$this->ensureItem($tabelaId, $produtoId, $item, $ordem, $now);
			}
		}
	}

	public function down() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('precos_tabelas')) {
			return;
		}
		$codigo = $this->quote(PrecosTabelaServicosTecnicosCatalog::TABELA_CODIGO);
		$this->execute(
			'DELETE FROM precos_tabela_itens WHERE precos_tabela_id IN (SELECT id FROM precos_tabelas WHERE codigo = ' . $codigo . ')'
		);
		$this->execute('DELETE FROM precos_tabelas WHERE codigo = ' . $codigo);
	}

	private function quote(string $value): string {
		return $this->getAdapter()->getConnection()->quote($value);
	}

	private function ensureTabela(int $idempresa, string $codigo, string $nome, string $desc, string $vigIni, string $vigFim, string $now): int {
		$row = $this->fetchRow(
			'SELECT id FROM precos_tabelas WHERE idempresa = ' . $idempresa . ' AND codigo = ' . $this->quote($codigo)
		);
		if (!empty($row['id'])) {
			$id = (int)$row['id'];
			$this->execute(
				'UPDATE precos_tabelas SET nome = ' . $this->quote($nome)
				. ', descricao = ' . $this->quote($desc)
				. ', vigencia_inicio = ' . $this->quote($vigIni)
				. ', vigencia_fim = ' . $this->quote($vigFim)
				. ', vigente = TRUE, ativo = TRUE, modified = ' . $this->quote($now)
				. ' WHERE id = ' . $id
			);

			return $id;
		}
		$this->execute(
			'INSERT INTO precos_tabelas (idempresa, codigo, nome, descricao, moeda, vigencia_inicio, vigencia_fim, vigente, ativo, created, modified) VALUES ('
			. $idempresa . ', '
			. $this->quote($codigo) . ', '
			. $this->quote($nome) . ', '
			. $this->quote($desc) . ', '
			. "'BRL', "
			. $this->quote($vigIni) . ', '
			. $this->quote($vigFim) . ', '
			. 'TRUE, TRUE, '
			. $this->quote($now) . ', '
			. $this->quote($now)
			. ')'
		);
		$ins = $this->fetchRow(
			'SELECT id FROM precos_tabelas WHERE idempresa = ' . $idempresa . ' AND codigo = ' . $this->quote($codigo)
		);

		return (int)($ins['id'] ?? 0);
	}

	/**
	 * @param array{categoria:string,codigo:string,descricao:string,preco:float,unidade:string} $item
	 */
	private function ensureProduto(int $idempresa, array $item): int {
		$codigo = $item['codigo'];
		$row = $this->fetchRow(
			'SELECT id FROM produtos WHERE idempresa = ' . $idempresa . ' AND codigo = ' . $this->quote($codigo)
		);
		$preco = number_format((float)$item['preco'], 2, '.', '');
		$desc = $item['descricao'];
		$un = $item['unidade'];
		if (!empty($row['id'])) {
			$id = (int)$row['id'];
			$this->execute(
				'UPDATE produtos SET descricao = ' . $this->quote($desc)
				. ', unidade = ' . $this->quote($un)
				. ', vlunitario = ' . $preco
				. ', tipo = 2, ativo = 1'
				. ' WHERE id = ' . $id
			);

			return $id;
		}
		$this->execute(
			'INSERT INTO produtos (idempresa, codigo, descricao, unidade, vlunitario, tipo, ativo, vllocdiario, vllocsemanal, vllocquinzenal, vllocmensal) VALUES ('
			. $idempresa . ', '
			. $this->quote($codigo) . ', '
			. $this->quote($desc) . ', '
			. $this->quote($un) . ', '
			. $preco . ', 2, 1, 0, 0, 0, 0)'
		);
		$ins = $this->fetchRow(
			'SELECT id FROM produtos WHERE idempresa = ' . $idempresa . ' AND codigo = ' . $this->quote($codigo)
		);

		return (int)($ins['id'] ?? 0);
	}

	/**
	 * @param array{categoria:string,codigo:string,descricao:string,preco:float,unidade:string} $item
	 */
	private function ensureItem(int $tabelaId, int $produtoId, array $item, int $ordem, string $now): void {
		$row = $this->fetchRow(
			'SELECT id FROM precos_tabela_itens WHERE precos_tabela_id = ' . $tabelaId
			. ' AND codigo_item = ' . $this->quote($item['codigo'])
		);
		$preco = number_format((float)$item['preco'], 2, '.', '');
		$prodSql = $produtoId > 0 ? (string)$produtoId : 'NULL';
		if (!empty($row['id'])) {
			$this->execute(
				'UPDATE precos_tabela_itens SET produto_id = ' . $prodSql
				. ', categoria = ' . $this->quote($item['categoria'])
				. ', descricao = ' . $this->quote($item['descricao'])
				. ', vlunitario = ' . $preco
				. ', ordem = ' . $ordem
				. ', ativo = TRUE, modified = ' . $this->quote($now)
				. ' WHERE id = ' . (int)$row['id']
			);

			return;
		}
		$this->execute(
			'INSERT INTO precos_tabela_itens (precos_tabela_id, produto_id, categoria, codigo_item, descricao, vlunitario, ordem, ativo, created, modified) VALUES ('
			. $tabelaId . ', '
			. $prodSql . ', '
			. $this->quote($item['categoria']) . ', '
			. $this->quote($item['codigo']) . ', '
			. $this->quote($item['descricao']) . ', '
			. $preco . ', '
			. $ordem . ', TRUE, '
			. $this->quote($now) . ', '
			. $this->quote($now)
			. ')'
		);
	}
}

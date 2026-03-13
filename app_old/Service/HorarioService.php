<?php
namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * Classifica intervalos de tempo em minutos comerciais vs especiais (feriados, fim de semana, fora do horário).
 */
class HorarioService
{
    /** @var string Horário comercial início (HH:MM) */
    protected $horaInicio = '08:00';
    /** @var string Horário comercial fim (HH:MM) */
    protected $horaFim = '18:00';
    /** @var array Datas (Y-m-d) que são feriados */
    protected $feriados = [];

    /**
     * @param int|null $idempresa Para filtrar feriados por empresa
     * @param string|null $horaInicio Ex.: 08:00
     * @param string|null $horaFim Ex.: 18:00
     */
    public function __construct($idempresa = null, $horaInicio = null, $horaFim = null)
    {
        if ($horaInicio !== null) {
            $this->horaInicio = $horaInicio;
        }
        if ($horaFim !== null) {
            $this->horaFim = $horaFim;
        }
        try {
            $config = TableRegistry::getTableLocator()->get('Config')->get(1);
            if (!empty($config->horario_comercial_inicio)) {
                $this->horaInicio = $config->horario_comercial_inicio;
            }
            if (!empty($config->horario_comercial_fim)) {
                $this->horaFim = $config->horario_comercial_fim;
            }
        } catch (\Exception $e) {
            // usa defaults
        }
        $this->carregarFeriados($idempresa);
    }

    protected function carregarFeriados($idempresa)
    {
        try {
            $feriadosTable = TableRegistry::getTableLocator()->get('Feriados');
            $hoje = date('Y-m-d');
            $ano = (int)date('Y');
            $this->feriados = $feriadosTable->listarFeriadosNoPeriodo($ano . '-01-01', ($ano + 1) . '-12-31', $idempresa);
        } catch (\Exception $e) {
            $this->feriados = [];
        }
    }

    /**
     * Classifica um intervalo em minutos comerciais e especiais.
     * @param \DateTimeInterface $inicio
     * @param \DateTimeInterface $fim
     * @param \DateTimeInterface|null $pausa Se preenchido, considera dois segmentos: inicio->pausa e (ignorado para duração) pausa->fim; ou seja, duração = (pausa - inicio) + (fim - pausa) = fim - inicio. Na verdade a spec diz "descontando pausas": duração efetiva = (fim - inicio) - (pausa - inicio) = fim - pausa. Ou seja: segmento 1 de inicio a pausa, segmento 2 de pausa a fim. Vamos calcular por segmentos.
     * @return array ['minutos_comercial' => int, 'minutos_especial' => int, 'tipo_horario' => 'comercial'|'especial'|'misto']
     */
    public function classificarIntervalo($inicio, $fim, $pausa = null)
    {
        if ($pausa !== null && $pausa > $inicio && $pausa < $fim) {
            $min1 = $this->classificarSegmento($inicio, $pausa);
            $min2 = $this->classificarSegmento($pausa, $fim);
            $minutos_comercial = $min1['minutos_comercial'] + $min2['minutos_comercial'];
            $minutos_especial = $min1['minutos_especial'] + $min2['minutos_especial'];
        } else {
            $r = $this->classificarSegmento($inicio, $fim);
            $minutos_comercial = $r['minutos_comercial'];
            $minutos_especial = $r['minutos_especial'];
        }
        $tipo = 'comercial';
        if ($minutos_especial > 0 && $minutos_comercial > 0) {
            $tipo = 'misto';
        } elseif ($minutos_especial > 0) {
            $tipo = 'especial';
        }
        return [
            'minutos_comercial' => $minutos_comercial,
            'minutos_especial' => $minutos_especial,
            'tipo_horario' => $tipo,
        ];
    }

    /**
     * Classifica um único segmento contínuo inicio->fim.
     */
    protected function classificarSegmento($inicio, $fim)
    {
        $minutos_comercial = 0;
        $minutos_especial = 0;
        $inicio = clone $inicio;
        $fim = clone $fim;
        if ($inicio >= $fim) {
            return ['minutos_comercial' => 0, 'minutos_especial' => 0];
        }
        $cursor = clone $inicio;
        $cursor->setTime((int)$cursor->format('H'), (int)$cursor->format('i'), 0);
        $fimMinuto = $fim->getTimestamp();
        $hIni = $this->parseTime($this->horaInicio);
        $hFim = $this->parseTime($this->horaFim);

        while ($cursor->getTimestamp() < $fimMinuto) {
            $fimDoMinuto = clone $cursor;
            $fimDoMinuto->modify('+1 minute');
            $segIni = $cursor->getTimestamp();
            $segFim = min($fimDoMinuto->getTimestamp(), $fimMinuto);
            $minutoContado = ($segFim - $segIni) / 60.0;

            $diaSemana = (int)$cursor->format('N'); // 1=Seg .. 7=Dom
            $dataStr = $cursor->format('Y-m-d');
            $ehFimDeSemana = ($diaSemana >= 6);
            $ehFeriado = isset($this->feriados[$dataStr]);

            if ($ehFeriado || $ehFimDeSemana) {
                $minutos_especial += $minutoContado;
            } else {
                $h = (int)$cursor->format('H');
                $m = (int)$cursor->format('i');
                $minutosDoDia = $h * 60 + $m;
                if ($minutosDoDia >= $hIni && $minutosDoDia < $hFim) {
                    $minutos_comercial += $minutoContado;
                } else {
                    $minutos_especial += $minutoContado;
                }
            }
            $cursor->modify('+1 minute');
        }

        return [
            'minutos_comercial' => (int)round($minutos_comercial),
            'minutos_especial' => (int)round($minutos_especial),
        ];
    }

    /** Converte "08:00" em minutos desde 00:00 (480). */
    protected function parseTime($str)
    {
        $p = explode(':', $str);
        $h = isset($p[0]) ? (int)$p[0] : 0;
        $m = isset($p[1]) ? (int)$p[1] : 0;
        return $h * 60 + $m;
    }
}

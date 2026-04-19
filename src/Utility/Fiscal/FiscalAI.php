<?php
namespace App\Utility\Fiscal;

use App\Service\Common\HttpClientService;
use Cake\Log\Log;

/**
 * Inteligência Artificial (Integração Google Gemini) para Tributação e Suporte
 */
class FiscalAI {
    
    /**
     * @param string $prompt 
     * @param bool $jsonFormat Exige que a IA devolva estritamente JSON na response.
     * @throws \Exception
     */
    public static function askGemini($prompt, $jsonFormat = true) {
        // A chave deverá estar no .env do servidor real (GEMINI_API_KEY=AIzaSy...)
        $apiKey = env('GEMINI_API_KEY');
        if (empty($apiKey)) {
            throw new \Exception('O motor de Inteligência Artificial requer uma GEMINI_API_KEY configurada.');
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

        $payload = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ]
        ];

        if ($jsonFormat) {
            $payload['generationConfig'] = [
                'responseMimeType' => 'application/json'
            ];
        }

        // Use secure HttpClientService instead of curl_exec
        $result = HttpClientService::post($url, $payload, [
            'timeout' => 20,
            'headers' => ['Content-Type: application/json'],
            'type' => 'json'
        ]);

        if (!$result['success']) {
            throw new \Exception('Falha de conexão com a IA: ' . ($result['error'] ?? 'Unknown error'));
        }

        $response = $result['data'];

        $decoded = json_decode($response, true);
        if (!empty($decoded['error'])) {
            throw new \Exception('A IA recusou a resposta (' . ($decoded['error']['message'] ?? '') . ')');
        }

        $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!$text) {
            throw new \Exception('Resposta vazia da IA.');
        }
        
        return $text;
    }

    /**
     * Mágica de preenchimento automático.
     */
    public static function sugerirNcm($descricao) {
        $prompt = "Você é um auditor e tabelião fiscal especialista na legislação do Brasil. "
            . "Com base apenas na seguinte descrição de produto/serviço comercial, advinhe o código NCM mais preciso "
            . "e o melhor CFOP considerando que farei uma mera e simples OP de venda estadual se estiver na dúvida. "
            . "Lembre-se que serviço municipal geralmente não usa CFOP tradicional de venda. "
            . "Descrição Informada: '{$descricao}'. "
            . "Responda EXATAMENTE E RIGOROSAMENTE APENAS neste modelo JSON: {\"ncm\": \"apenas os 8 num\", \"cfop\": \"4 numeros\", \"detalhe\": \"pequena frase do seu pensamento\"}";

        $res = self::askGemini($prompt, true);
        $json = json_decode($res, true);
        if (!$json) {
            throw new \Exception('Formato ininteligível ao inferir com a inteligência artificial.');
        }
        return $json; // Array
    }

    /**
     * O famoso Auditor Preventivo Anti-Rejeição SEFAZ.
     */
    public static function auditarNota($notaJson, $regime) {
        $prompt = "Você é um auditor fiscal eletrônico do governo federal experiente na malha da SEFAZ NFe e NFCe. "
            . "O regime tributário atual da empresa emissora desta nota é: '{$regime}'. "
            . "O objeto JSON que eu coletei representa parte do esqueleto XML da NFe no meu banco de dados. "
            . "Cruze as informações do remetente com o destinatário (estadual/interestadual), NCMs, cfop, e cst. "
            . "Procure por vícios clássicos de preenchimento que causarão 'Código de Rejeição' garantido pela Sefaz - por exemplo, "
            . "venda para fora do estado usando CFOP 5xxx, ou CST que não deve ser usado no Simples Nacional, ou campo vazio gritante. "
            . "Seja brando e flexível só aponte falhas GRITANTES. "
            . "Sua resposta precisa SER UM JSON ESTRITO contendo o status ('ok' ou 'alerta') e uma lista das falhas em 'mensagens'. "
            . "Formato JSON ESTRITO obrigatório: {\"status\": \"alerta\", \"mensagens\": [\"CST x incompativel para SN\", \"CFOP 5102 invalido para UF diferente...\"]}\n"
            . "JSON DA NF-e:\n{$notaJson}";

        $res = self::askGemini($prompt, true);
        $json = json_decode($res, true);
        if (!$json) {
            throw new \Exception('Auditor IA retornou uma resposta desconfigurada.');
        }
        return $json; // Array
    }
}

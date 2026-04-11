<?php
namespace App\Utility\Fiscal;

use Cake\Log\Log;

/**
 * Assinatura digital XML com certificado A1 (PFX/P12).
 *
 * Implementa xmldsig conforme exigido pela SEFAZ para NFe/NFCe.
 */
class FiscalSigner {

    /** @var string Conteúdo do PFX (binário) */
    private $pfxContent;

    /** @var string Senha do certificado */
    private $senha;

    /** @var resource|null Chave privada */
    private $privateKey;

    /** @var string|null Certificado X509 (PEM) */
    private $publicKey;

    public function __construct($pfxContent, $senha) {
        $this->pfxContent = $pfxContent;
        $this->senha = $senha;
        $this->loadCertificate();
    }

    /**
     * Carrega o certificado PFX e extrai chave privada e pública.
     */
    private function loadCertificate() {
        $certs = [];
        if (!openssl_pkcs12_read($this->pfxContent, $certs, $this->senha)) {
            throw new \RuntimeException('Não foi possível ler o certificado PFX. Verifique a senha.');
        }
        $this->privateKey = openssl_pkey_get_private($certs['pkey']);
        $this->publicKey = $certs['cert'];

        if (!$this->privateKey) {
            throw new \RuntimeException('Chave privada inválida no certificado.');
        }
    }

    /**
     * Assina um XML NFe/NFCe.
     *
     * @param string $xml XML não assinado
     * @param string $tagToSign Tag a assinar (infNFe, infInut, infEvento, etc.)
     * @return string XML assinado
     */
    public function sign($xml, $tagToSign = 'infNFe') {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;
        $doc->loadXML($xml);

        $node = $doc->getElementsByTagName($tagToSign)->item(0);
        if (!$node) {
            throw new \RuntimeException("Tag '{$tagToSign}' não encontrada no XML.");
        }

        $id = $node->getAttribute('Id');
        if (!$id) {
            throw new \RuntimeException("Atributo 'Id' não encontrado na tag '{$tagToSign}'.");
        }

        // Canonicalizar o nó
        $canonical = $node->C14N(false, false, null, null);

        // DigestValue
        $digestValue = base64_encode(hash('sha1', $canonical, true));

        // Construir SignedInfo
        $signedInfo = '<SignedInfo xmlns="http://www.w3.org/2000/09/xmldsig#">'
            . '<CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>'
            . '<SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>'
            . '<Reference URI="#' . $id . '">'
            . '<Transforms>'
            . '<Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>'
            . '<Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>'
            . '</Transforms>'
            . '<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>'
            . '<DigestValue>' . $digestValue . '</DigestValue>'
            . '</Reference>'
            . '</SignedInfo>';

        // Canonicalizar SignedInfo
        $signedInfoDoc = new \DOMDocument('1.0', 'UTF-8');
        $signedInfoDoc->loadXML($signedInfo);
        $canonicalSignedInfo = $signedInfoDoc->documentElement->C14N(false, false, null, null);

        // Assinar
        $signature = '';
        if (!openssl_sign($canonicalSignedInfo, $signature, $this->privateKey, OPENSSL_ALGO_SHA1)) {
            $sslErr = openssl_error_string();
            throw new \RuntimeException('Falha ao assinar o XML: ' . ($sslErr !== false ? $sslErr : '(sem detalhe OpenSSL)'));
        }
        $signatureValue = base64_encode($signature);

        // Extrair certificado X509 (sem headers PEM)
        $x509Data = $this->getX509Clean();

        // Montar bloco Signature
        $signatureXml = '<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">'
            . $signedInfo
            . '<SignatureValue>' . $signatureValue . '</SignatureValue>'
            . '<KeyInfo>'
            . '<X509Data>'
            . '<X509Certificate>' . $x509Data . '</X509Certificate>'
            . '</X509Data>'
            . '</KeyInfo>'
            . '</Signature>';

        // Inserir Signature após a tag assinada
        $signatureDoc = new \DOMDocument('1.0', 'UTF-8');
        $signatureDoc->loadXML($signatureXml);
        $signatureNode = $doc->importNode($signatureDoc->documentElement, true);
        $node->parentNode->appendChild($signatureNode);

        return $doc->saveXML();
    }

    /**
     * Certificado + chave privada em PEM (concatenados) para SoapClient stream_context ssl.local_cert.
     */
    public function exportSslPemBundle(): string {
        $certPem = trim((string)$this->publicKey);
        $keyPem = '';
        if (!openssl_pkey_export($this->privateKey, $keyPem)) {
            throw new \RuntimeException('Falha ao exportar chave privada para PEM (SOAP/SEFAZ).');
        }

        return $certPem . "\n" . trim($keyPem) . "\n";
    }

    /**
     * Extrai informações do certificado.
     */
    public function getCertificateInfo() {
        $certData = openssl_x509_parse($this->publicKey);
        if (!$certData) {
            return [];
        }
        return [
            'subject' => $certData['subject']['CN'] ?? '',
            'issuer' => $certData['issuer']['CN'] ?? '',
            'serial_number' => $certData['serialNumberHex'] ?? $certData['serialNumber'] ?? '',
            'valid_from' => date('Y-m-d H:i:s', $certData['validFrom_time_t'] ?? 0),
            'valid_to' => date('Y-m-d H:i:s', $certData['validTo_time_t'] ?? 0),
            'cnpj' => $this->extractCnpjFromCert($certData),
        ];
    }

    /**
     * Verifica se o certificado está válido (dentro do prazo).
     */
    public function isValid() {
        $certData = openssl_x509_parse($this->publicKey);
        if (!$certData) {
            return false;
        }
        $now = time();
        return $now >= ($certData['validFrom_time_t'] ?? 0)
            && $now <= ($certData['validTo_time_t'] ?? 0);
    }

    /**
     * Dias restantes de validade.
     */
    public function diasRestantes() {
        $certData = openssl_x509_parse($this->publicKey);
        if (!$certData || empty($certData['validTo_time_t'])) {
            return 0;
        }
        $diff = $certData['validTo_time_t'] - time();
        return max(0, (int)floor($diff / 86400));
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function getX509Clean() {
        $cert = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n"], '', $this->publicKey);
        return trim($cert);
    }

    private function extractCnpjFromCert(array $certData) {
        // CNPJ geralmente está no campo OID 2.16.76.1.3.3 ou no subject
        $subject = $certData['subject'] ?? [];
        $cn = $subject['CN'] ?? '';
        if (preg_match('/(\d{14})/', $cn, $matches)) {
            return $matches[1];
        }
        // Tenta extrair do campo serialNumber (OID)
        if (!empty($subject['serialNumber'])) {
            $serial = $subject['serialNumber'];
            if (preg_match('/(\d{14})/', $serial, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}

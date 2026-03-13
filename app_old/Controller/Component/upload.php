<?php

// --- Configuraчѕes Iniciais ---
// Define o diretѓrio onde as imagens serуo armazenadas
define('UPLOAD_DIR', 'images/');
// Define o prefixo do caminho web para a URL retornada ao cliente
// Ex: Se UPLOAD_DIR щ 'images/' e WEB_PATH_PREFIX щ 'uploads/',
// uma imagem salva como 'uniqueid.jpg' seria acessэvel via 'uploads/uniqueid.jpg'
// No seu caso original, parece ser 'teste/' + 'images/filename.jpg', entуo ajustei para 'teste/' se for o desejado
define('WEB_PATH_PREFIX', 'teste/');
// Define o tamanho mсximo permitido para o arquivo (ex: 5 MB)
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB em bytes

// Origens permitidas para requisiчѕes CORS
$accepted_origins = [
    "http://localhost",
    "http://10.0.2.7:81"
];

// Extensѕes de arquivo permitidas
$allowed_extensions = ["gif", "jpg", "jpeg", "png", "webp"];

// Funчуo auxiliar para enviar respostas JSON de erro
function sendErrorResponse(int $httpStatus, string $message, string $logMessage = null) {
    header("HTTP/1.1 " . $httpStatus);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $message]);
    if ($logMessage) {
        error_log($logMessage);
    }
    exit;
}

// --- Validaчуo CORS (Cross-Origin Resource Sharing) ---
if (isset($_SERVER['HTTP_ORIGIN'])) {
    if (in_array($_SERVER['HTTP_ORIGIN'], $accepted_origins)) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    } else {
        sendErrorResponse(403, 'Acesso negado. Origem nуo permitida.', 'Tentativa de upload de origem nуo permitida: ' . $_SERVER['HTTP_ORIGIN']);
    }
}

// --- Lidar com requisiчѕes OPTIONS (CORS Preflight) ---
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit; // Apenas envia os cabeчalhos CORS e termina
}

// --- Verificaчуo e Criaчуo do Diretѓrio de Upload ---
if (!is_dir(UPLOAD_DIR)) {
    // Tenta criar o diretѓrio com permissѕes 0755
    if (!mkdir(UPLOAD_DIR, 0755, true)) {
        sendErrorResponse(500, 'Erro interno do servidor: O diretѓrio de upload nуo pєde ser criado.', 'Diretѓrio de upload ' . UPLOAD_DIR . ' nуo encontrado e nуo pєde ser criado.');
    }
} elseif (!is_writable(UPLOAD_DIR)) {
    // Verifica se o diretѓrio existe mas nуo щ gravсvel
    sendErrorResponse(500, 'Erro interno do servidor: O diretѓrio de upload nуo possui permissѕes de escrita.', 'Diretѓrio de upload ' . UPLOAD_DIR . ' nуo щ gravсvel.');
}

// --- Obter o Arquivo Enviado ---
$uploaded_file_info = null;
// Procura pelo primeiro arquivo vсlido em $_FILES
foreach ($_FILES as $input_name => $file_data) {
    // Garante que щ uma entrada de upload de arquivo њnico
    if (isset($file_data['tmp_name']) && is_uploaded_file($file_data['tmp_name'])) {
        $uploaded_file_info = $file_data;
        break; // Processa apenas o primeiro arquivo vсlido encontrado
    }
}

if ($uploaded_file_info === null) {
    sendErrorResponse(400, 'Nenhum arquivo enviado ou upload invсlido.', 'Tentativa de upload sem arquivo ou com arquivo invсlido.');
}

// --- Tratamento de Erros de Upload PHP ---
if ($uploaded_file_info['error'] !== UPLOAD_ERR_OK) {
    $errorMessage = 'Ocorreu um erro desconhecido no upload.';
    $logMessage = 'Erro no upload para o arquivo: ' . $uploaded_file_info['name'] . ', Cѓdigo de erro: ' . $uploaded_file_info['error'];

    switch ($uploaded_file_info['error']) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $errorMessage = 'O arquivo excede o tamanho mсximo permitido no servidor ou formulсrio.';
            break;
        case UPLOAD_ERR_PARTIAL:
            $errorMessage = 'O arquivo foi apenas parcialmente enviado.';
            break;
        case UPLOAD_ERR_NO_FILE:
            $errorMessage = 'Nenhum arquivo foi enviado.';
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $errorMessage = 'Pasta temporсria ausente no servidor.';
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $errorMessage = 'Falha ao escrever o arquivo em disco.';
            break;
        case UPLOAD_ERR_EXTENSION:
            $errorMessage = 'Uma extensуo PHP interrompeu o upload do arquivo.';
            break;
    }
    sendErrorResponse(500, 'Erro no upload: ' . $errorMessage, $logMessage);
}

// --- Verificaчуo de Tamanho do Arquivo (Aplicaчуo) ---
if ($uploaded_file_info['size'] > MAX_FILE_SIZE) {
    sendErrorResponse(413, 'O tamanho do arquivo (' . round($uploaded_file_info['size'] / (1024 * 1024), 2) . ' MB) excede o limite permitido de ' . (MAX_FILE_SIZE / (1024 * 1024)) . ' MB.', 'Arquivo muito grande: ' . $uploaded_file_info['name'] . ', tamanho: ' . $uploaded_file_info['size']);
}

// --- Validaчуo da Extensуo do Arquivo ---
$original_filename = $uploaded_file_info['name'];
$file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));

if (!in_array($file_extension, $allowed_extensions)) {
    sendErrorResponse(400, 'Extensуo de arquivo invсlida. Apenas ' . implode(', ', $allowed_extensions) . ' sуo permitidas.', 'Extensуo invсlida para arquivo: ' . $original_filename);
}

// --- Validaчуo do Tipo MIME Real do Arquivo (Mais Seguro) ---
// Requer a extensуo Fileinfo do PHP (geralmente habilitada por padrуo)
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $uploaded_file_info['tmp_name']);
    finfo_close($finfo);

    // Mime types permitidos para imagens
    $allowed_mime_types = ['image/gif', 'image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($mime_type, $allowed_mime_types)) {
        sendErrorResponse(400, 'Tipo de arquivo invсlido detectado. Por favor, envie uma imagem real.', 'MIME type invсlido detectado: ' . $mime_type . ' para arquivo: ' . $original_filename);
    }
} else {
    // Se a extensуo Fileinfo nуo estiver disponэvel, loga um aviso e confia apenas na extensуo
    error_log("A extensуo PHP Fileinfo nуo estс habilitada. A verificaчуo do tipo MIME real foi ignorada para maior seguranчa.");
}

// --- Geraчуo de Nome de Arquivo кnico e Seguro ---
// Isso evita colisѕes e problemas de path traversal
$new_filename = uniqid('upload_', true) . '.' . $file_extension;
$file_path = UPLOAD_DIR . $new_filename;

// --- Mover o Arquivo para o Destino Final ---
if (move_uploaded_file($uploaded_file_info['tmp_name'], $file_path)) {
    // Sucesso: Retorna o caminho pњblico do arquivo em JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'location' => WEB_PATH_PREFIX . $new_filename,
        'original_name' => $original_filename, // Pode ser њtil manter o nome original
        'size' => $uploaded_file_info['size']
    ]);
    exit;
} else {
    // Falha ao mover o arquivo por outras razѕes
    sendErrorResponse(500, 'Falha interna do servidor ao salvar o arquivo enviado.', 'Falha ao mover o arquivo de ' . $uploaded_file_info['tmp_name'] . ' para ' . $file_path);
}

?>
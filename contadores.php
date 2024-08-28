<?php

function BuscarContadores($ip) {
    // Valida o IP
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return json_encode(['error' => 'Endereço IP inválido']);
    }

    // URL do JSON
    $url = "http://{$ip}/sws/app/information/counters/counters.json";

    // Inicializa o cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Define um tempo limite de 10 segundos para a requisição

    $response = curl_exec($ch);

    // Verifica se houve erro na requisição
    if (curl_errno($ch)) {
        $error_message = 'Erro ao carregar o JSON: ' . curl_error($ch);
        curl_close($ch);
        return json_encode(['error' => $error_message]);
    }

    // Verifica o código de status HTTP da resposta
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_status !== 200) {
        curl_close($ch);
        return json_encode(['error' => 'Falha ao acessar o recurso. Status HTTP: ' . $http_status]);
    }

    curl_close($ch);

    // Processa a resposta JSON
    $start_index = strpos($response, 'GXI_SYS_SERIAL_NUM');
    if ($start_index === false) {
        return json_encode(['error' => 'O dado "GXI_SYS_SERIAL_NUM" não foi encontrado na resposta.']);
    }

    $result = substr($response, 0, $start_index) . substr($response, $start_index + 42);
    $result = str_replace(": ", ":", $result);
    $result = str_replace(" ", "", $result);
    $result = preg_replace('/(\w+):(\d+)/', '"$1":$2', $result);

    // Verifica se a string resultante é um JSON válido
    json_decode($result);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return json_encode(['error' => 'Erro ao processar a resposta JSON.']);
    }

    // Retorna o JSON pronto
    return $result;
}

// Captura o IP enviado via POST
$ip = $_POST['ip'] ?? null;

// Chama a função e retorna o resultado
header('Content-Type: application/json');
echo BuscarContadores($ip);

?>

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * URL da API TelcoSMS
     */
    protected string $apiUrl = 'https://telcosms.co.ao/send_message';

    /**
     * Chave API de Produção (SIGEF)
     */
    protected string $apiKey = 'prd09933ffaa3022ca9d71dc39719';

    public function __construct()
    {
        // Permitir sobrescrever via config se necessário
        $this->apiKey = config('services.telcosms.api_key', $this->apiKey);
        $this->apiUrl = config('services.telcosms.api_url', $this->apiUrl);
    }

    /**
     * Enviar SMS usando o formato da Uamicare
     *
     * @param string $phone Número de telefone (formato: 9XXXXXXXX)
     * @param string $message Mensagem a enviar
     * @return array
     */
    public function send(string $phone, string $message): array
    {
        try {
            // Formatar o número de telefone
            $phone = $this->formatPhoneNumber($phone);
            
            if (empty($phone)) {
                return [
                    'success' => false,
                    'message' => 'Número de telefone inválido',
                ];
            }

            // Remover acentos para evitar problemas de encoding
            $message = $this->removeAccents($message);

            // Formato JSON conforme API Uamicare
            $payload = [
                'message' => [
                    'api_key_app' => $this->apiKey,
                    'phone_number' => $phone,
                    'message_body' => $message,
                ]
            ];

            Log::info('Enviando SMS', [
                'url' => $this->apiUrl,
                'phone' => $phone,
            ]);

            // Fazer requisição POST com JSON
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->withOptions([
                    'verify' => false, // Desabilitar verificação SSL para ambiente local
                ])
                ->post($this->apiUrl, $payload);

            $responseBody = $response->json() ?? [];
            $statusCode = $response->status();

            // Verificar resposta - aceitar status que começa com "200"
            if ($response->successful() && isset($responseBody['status']) && str_starts_with((string)$responseBody['status'], '200')) {
                Log::info('SMS enviado com sucesso', [
                    'phone' => $phone,
                    'response' => $responseBody,
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS enviado com sucesso',
                    'response' => $responseBody,
                ];
            }

            // Erro na API
            $errorMessage = $responseBody['error_message'] ?? $responseBody['error'] ?? $response->body();
            
            Log::error('Erro ao enviar SMS', [
                'phone' => $phone,
                'status' => $statusCode,
                'response' => $responseBody,
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao enviar SMS: ' . $errorMessage,
                'error' => $response->body(),
            ];

        } catch (\Exception $e) {
            Log::error('Exceção ao enviar SMS', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao enviar SMS: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Remover acentos e caracteres especiais
     */
    protected function removeAccents(string $string): string
    {
        $accents = [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
            'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C', 'Ñ' => 'N',
        ];
        
        return strtr($string, $accents);
    }

    /**
     * Formatar número de telefone para o formato correto
     * Retorna formato: 244XXXXXXXXX
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remover espaços, hifens e caracteres especiais
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Se começar com 244, já está no formato correto
        if (str_starts_with($phone, '244')) {
            return $phone;
        }
        
        // Se começar com 9, adicionar prefixo de Angola
        if (str_starts_with($phone, '9') && strlen($phone) === 9) {
            return '244' . $phone;
        }
        
        // Se tiver menos de 9 dígitos, é inválido
        if (strlen($phone) < 9) {
            return '';
        }
        
        return '244' . $phone;
    }

    /**
     * Enviar SMS de notificação de inscrição para agente
     */
    public function sendAgentRegistrationNotification(string $phone, string $agentName, string $schoolName): array
    {
        $message = "Caro(a) {$agentName}, a sua inscricao foi registada com sucesso no SIGEF. "
            . "Por favor, apresente-se na {$schoolName} para recolher a sua ficha de inscricao e ser apurado para o curso. "
            . "Policia Nacional de Angola.";

        return $this->send($phone, $message);
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PnaAgentService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $cacheTtl;

    public function __construct()
    {
        $this->baseUrl = config('services.piips.base_url', 'http://localhost:3333/api/v1/integration');
        $this->apiKey = config('services.piips.api_key', '');
        $this->cacheTtl = config('services.piips.cache_ttl', 3600);
    }

    /**
     * Busca informações completas de um agente pelo NIP
     */
    public function buscarPorNip(string $nip, bool $useCache = true): ?array
    {
        $cacheKey = "piips_agente_{$nip}";
        
        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(30)->get("{$this->baseUrl}/agente/{$nip}");

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['success'] && $data['data']) {
                    Cache::put($cacheKey, $data['data'], $this->cacheTtl);
                    return $data['data'];
                }
            }

            if ($response->status() === 404) {
                Log::info("Agente com NIP {$nip} não encontrado no PIIPS.");
                return null;
            }

            Log::error("Erro ao buscar agente no PIIPS", [
                'nip' => $nip,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error("Exceção ao buscar agente no PIIPS", [
                'nip' => $nip,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Busca informações básicas de um agente pelo NIP (versão leve)
     */
    public function buscarBasicoPorNip(string $nip): ?array
    {
        $cacheKey = "piips_agente_basico_{$nip}";
        
        // Cache temporariamente desabilitado para debug
        // if (Cache::has($cacheKey)) {
        //     return Cache::get($cacheKey);
        // }

        try {
            Log::info("PIIPS: Buscando NIP", ['nip' => $nip, 'url' => "{$this->baseUrl}/agente/{$nip}/basico"]);
            
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(15)->get("{$this->baseUrl}/agente/{$nip}/basico");

            Log::info("PIIPS: Resposta HTTP", [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info("PIIPS: Dados JSON", [
                    'success' => $data['success'] ?? null,
                    'data' => $data['data'] ?? null,
                ]);
                
                if (isset($data['success']) && $data['success'] && !empty($data['data'])) {
                    Cache::put($cacheKey, $data['data'], $this->cacheTtl);
                    return $data['data'];
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error("PIIPS: Exceção", ['nip' => $nip, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verifica se um NIP existe no PIIPS
     */
    public function verificarNip(string $nip): bool
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(10)->get("{$this->baseUrl}/agente/{$nip}/verificar");

            if ($response->successful()) {
                $data = $response->json();
                return $data['success'] && ($data['data']['existe'] ?? false);
            }

            return false;

        } catch (\Exception $e) {
            Log::error("Exceção ao verificar NIP no PIIPS", [
                'nip' => $nip,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Verifica se a API está disponível
     */
    public function healthCheck(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");
            return $response->successful() && ($response->json()['status'] ?? '') === 'online';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Limpa o cache de um agente específico
     */
    public function limparCache(string $nip): void
    {
        Cache::forget("piips_agente_{$nip}");
        Cache::forget("piips_agente_basico_{$nip}");
    }

    /**
     * Formata o telefone do agente para exibição
     */
    public function formatarTelefone(?array $telefones): string
    {
        if (!$telefones) {
            return '';
        }

        return $telefones['principal'] 
            ?? $telefones['profissional'] 
            ?? $telefones['alternativo'] 
            ?? '';
    }

    /**
     * Obtém a URL da foto do agente
     */
    public function obterUrlFoto(?array $fotos, string $tipo = 'efectivo'): ?string
    {
        if (!$fotos) {
            return null;
        }

        $foto = $tipo === 'civil' ? ($fotos['foto_civil'] ?? null) : ($fotos['foto_efectivo'] ?? null);
        
        if (!$foto) {
            return null;
        }

        $baseStorageUrl = config('services.piips.storage_url', 'http://localhost:3333');
        return "{$baseStorageUrl}/{$foto}";
    }
}

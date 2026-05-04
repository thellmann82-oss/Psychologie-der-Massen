<?php
class LLMClient {

    private string $apiKey;
    private string $baseUrl;
    private string $model;

    public function __construct() {
        $this->apiKey  = LLM_API_KEY;
        $this->baseUrl = rtrim(LLM_BASE_URL, '/');
        $this->model   = LLM_MODEL;
    }

    public function chat(string $systemPrompt, string $userPrompt, float $temperature = 0.7): string {
        $payload = json_encode([
            'model'       => $this->model,
            'temperature' => $temperature,
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
        ]);

        $ch = curl_init($this->baseUrl . '/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT        => 120,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            throw new RuntimeException("LLM-API Fehler (HTTP $httpCode): $response");
        }

        $data = json_decode($response, true);
        $content = $data['choices'][0]['message']['content'] ?? '';

        // <thinking>-Tags entfernen (MiniMax, DeepSeek etc.)
        $content = preg_replace('/<thinking>.*?<\/thinking>/s', '', $content);
        return trim($content);
    }

    // JSON-Antwort anfordern und parsen
    public function chatJson(string $systemPrompt, string $userPrompt): array {
        $raw = $this->chat($systemPrompt, $userPrompt, 0.3);

        // JSON aus Markdown-Codeblock extrahieren falls nötig
        if (preg_match('/```(?:json)?\s*([\s\S]+?)```/', $raw, $m)) {
            $raw = $m[1];
        }
        $raw = trim($raw);

        $result = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Ungültiges JSON vom LLM: ' . substr($raw, 0, 200));
        }
        return $result;
    }
}

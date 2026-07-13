<?php
/**
 * SoulMD Hub - OpenAI-compatible chat completion streaming proxy (SSE).
 * Proxies upstream token deltas (content + reasoning/thinking) to the client.
 */
class LlmStreamProxy
{
    /**
     * Switch response to Server-Sent Events and disable buffering.
     */
    public static function beginSse(): void
    {
        // Drop any prior JSON content-type if headers not sent yet
        if (!headers_sent()) {
            header_remove('Content-Type');
            header('Content-Type: text/event-stream; charset=utf-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no'); // nginx
        }

        // Disable PHP / zlib buffering so tokens flush immediately
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }
        @ini_set('zlib.output_compression', '0');
        @ini_set('implicit_flush', '1');
        ob_implicit_flush(true);
    }

    /**
     * Emit one SSE data event (JSON object).
     */
    public static function emit(array $payload): void
    {
        echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) . "\n\n";
        if (function_exists('ob_flush')) {
            @ob_flush();
        }
        @flush();
    }

    public static function emitDone(): void
    {
        echo "data: [DONE]\n\n";
        if (function_exists('ob_flush')) {
            @ob_flush();
        }
        @flush();
    }

    /**
     * Parse one upstream SSE "data:" payload line into accumulators + onDelta.
     *
     * @return bool true if a fatal stream error object was seen
     */
    private static function ingestDataPayload(
        string $data,
        string &$content,
        string &$reasoning,
        string &$finishReason,
        ?string &$streamErrorMsg,
        string &$rawErrorBody,
        callable $onDelta
    ): bool {
        $data = trim($data);
        if ($data === '' || $data === '[DONE]') {
            return false;
        }

        $json = json_decode($data, true);
        if (!is_array($json)) {
            return false;
        }

        if (!empty($json['error'])) {
            $streamErrorMsg = is_array($json['error'])
                ? (string)($json['error']['message'] ?? json_encode($json['error']))
                : (string)$json['error'];
            $rawErrorBody = $data;
            return true;
        }

        $choice = $json['choices'][0] ?? null;
        if (!is_array($choice)) {
            return false;
        }

        if (!empty($choice['finish_reason'])) {
            $finishReason = (string)$choice['finish_reason'];
        }

        $delta = $choice['delta'] ?? [];
        if (!is_array($delta)) {
            return false;
        }

        // Thinking / chain-of-thought (DeepSeek V4, reasoner, etc.)
        foreach (['reasoning_content', 'reasoning'] as $rk) {
            if (!empty($delta[$rk]) && is_string($delta[$rk])) {
                $reasoning .= $delta[$rk];
                $onDelta('thinking', $delta[$rk]);
            }
        }

        // Final answer tokens (allow whitespace-only pieces for fidelity)
        if (array_key_exists('content', $delta) && $delta['content'] !== null && $delta['content'] !== '') {
            $piece = is_string($delta['content']) ? $delta['content'] : '';
            if ($piece !== '') {
                $content .= $piece;
                $onDelta('content', $piece);
            }
        }

        return false;
    }

    /**
     * Drain buffered upstream SSE lines.
     */
    private static function drainLineBuffer(
        string &$lineBuffer,
        bool &$sawSseData,
        string &$content,
        string &$reasoning,
        string &$finishReason,
        ?string &$streamErrorMsg,
        string &$rawErrorBody,
        callable $onDelta,
        bool $flushRemainder = false
    ): void {
        while (($pos = strpos($lineBuffer, "\n")) !== false) {
            $line = substr($lineBuffer, 0, $pos);
            $lineBuffer = substr($lineBuffer, $pos + 1);
            $line = rtrim($line, "\r");

            if ($line === '' || str_starts_with($line, ':')) {
                continue;
            }
            if (!str_starts_with($line, 'data:')) {
                continue;
            }

            $sawSseData = true;
            self::ingestDataPayload(
                substr($line, 5),
                $content,
                $reasoning,
                $finishReason,
                $streamErrorMsg,
                $rawErrorBody,
                $onDelta
            );
        }

        // Last upstream chunk sometimes has no trailing newline — still parse it.
        if ($flushRemainder && $lineBuffer !== '') {
            $line = rtrim($lineBuffer, "\r");
            $lineBuffer = '';
            if ($line !== '' && !str_starts_with($line, ':') && str_starts_with($line, 'data:')) {
                $sawSseData = true;
                self::ingestDataPayload(
                    substr($line, 5),
                    $content,
                    $reasoning,
                    $finishReason,
                    $streamErrorMsg,
                    $rawErrorBody,
                    $onDelta
                );
            }
        }
    }

    /**
     * Stream a chat completion from an OpenAI-compatible endpoint.
     *
     * @param string   $apiUrl
     * @param string   $apiKey
     * @param array    $requestBody  Must include model, messages; stream will be forced true
     * @param callable $onDelta      function(string $kind, string $text): void  kind = thinking|content
     * @param int      $timeoutSec
     * @return array{content:string,reasoning:string,finish_reason:string,http_code:int,error:?string,raw_error_body:string,emitted_tokens:bool}
     */
    public static function streamCompletion(
        string $apiUrl,
        string $apiKey,
        array $requestBody,
        callable $onDelta,
        int $timeoutSec = 150
    ): array {
        $requestBody['stream'] = true;

        $payload = json_encode($requestBody, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        $lineBuffer = '';
        $content = '';
        $reasoning = '';
        $finishReason = '';
        $httpCode = 0;
        $rawErrorBody = '';
        $sawSseData = false;
        $streamErrorMsg = null;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
                'Accept: text/event-stream',
            ],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => $timeoutSec,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION => static function ($ch, $chunk) use (
                &$lineBuffer,
                &$content,
                &$reasoning,
                &$finishReason,
                &$rawErrorBody,
                &$sawSseData,
                &$streamErrorMsg,
                $onDelta
            ) {
                $len = strlen($chunk);
                $lineBuffer .= $chunk;

                // Upstream error bodies may be plain JSON (no SSE framing)
                if (!$sawSseData && strpos($lineBuffer, 'data:') === false) {
                    if (strlen($lineBuffer) < 65536) {
                        $rawErrorBody = $lineBuffer;
                    }
                }

                self::drainLineBuffer(
                    $lineBuffer,
                    $sawSseData,
                    $content,
                    $reasoning,
                    $finishReason,
                    $streamErrorMsg,
                    $rawErrorBody,
                    $onDelta,
                    false
                );

                return $len;
            },
        ]);

        $ok = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrNo = curl_errno($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        // Flush incomplete final line (no trailing \n)
        self::drainLineBuffer(
            $lineBuffer,
            $sawSseData,
            $content,
            $reasoning,
            $finishReason,
            $streamErrorMsg,
            $rawErrorBody,
            $onDelta,
            true
        );

        $error = null;
        if ($curlErrNo) {
            $error = $curlErr !== '' ? $curlErr : 'Upstream stream connection failed';
        } elseif ($streamErrorMsg !== null) {
            $error = $streamErrorMsg;
        } elseif ($httpCode !== 200) {
            $errJson = json_decode($rawErrorBody, true);
            $error = $errJson['error']['message']
                ?? $errJson['message']
                ?? (trim($rawErrorBody) !== '' ? mb_substr(trim($rawErrorBody), 0, 400) : "HTTP {$httpCode}");
        } elseif (!$ok) {
            $error = 'Upstream stream aborted';
        }

        return [
            'content' => $content,
            'reasoning' => $reasoning,
            'finish_reason' => $finishReason,
            'http_code' => $httpCode,
            'error' => $error,
            'raw_error_body' => $rawErrorBody,
            'emitted_tokens' => ($content !== '' || $reasoning !== ''),
        ];
    }

    /**
     * Prefer final answer text; fall back to reasoning if content empty.
     */
    public static function pickReply(string $content, string $reasoning): string
    {
        $content = trim($content);
        if ($content !== '') {
            return $content;
        }
        return trim($reasoning);
    }

    /**
     * Whether an upstream 400 is likely caused by unsupported thinking param.
     * Intentionally narrow — do NOT match generic "invalid api key" etc.
     */
    public static function isThinkingParamRejected(?string $error, ?string $rawBody = null): bool
    {
        $hay = strtolower(trim((string)$error . ' ' . (string)$rawBody));
        if ($hay === '') {
            return false;
        }
        // Must mention thinking / reasoning_effort / extra_body style keys
        $mentionsThinking = (strpos($hay, 'thinking') !== false)
            || (strpos($hay, 'reasoning_effort') !== false)
            || (strpos($hay, 'reasoning effort') !== false);
        if (!$mentionsThinking) {
            return false;
        }
        // And look like a schema / unknown-field rejection
        return (strpos($hay, 'unknown') !== false)
            || (strpos($hay, 'invalid') !== false)
            || (strpos($hay, 'unsupported') !== false)
            || (strpos($hay, 'not allowed') !== false)
            || (strpos($hay, 'unexpected') !== false)
            || (strpos($hay, 'extra') !== false);
    }
}

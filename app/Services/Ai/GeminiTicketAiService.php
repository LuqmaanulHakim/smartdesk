<?php

namespace App\Services\Ai;

use App\Contracts\TicketAiService;
use App\DTOs\TicketClassification;
use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketSentiment;
use App\Models\Ticket;
use App\Models\AiRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use JsonException;

class GeminiTicketAiService implements TicketAiService
{
    
private function generateContent(
        Ticket $ticket,
        string $type,
        string $systemInstruction,
        string $prompt,
        bool $jsonMode = false
    ): string {
        $startedAt = hrtime(true);
        $apiKey = config('ai.api_key');
        $model = config('ai.model');

        try {
            if (empty($apiKey)) {
                throw new RuntimeException('Gemini API key is missing.');
            }

            $generationConfig = [
                'temperature' => 0.2,
                'maxOutputTokens' => 1024,
            ];

            if ($jsonMode) {
                $generationConfig['responseMimeType'] = 'application/json';
            }

            $response = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
            ])
                ->timeout(30)
                ->retry(2, 500, function ($exception) {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }

                    if ($exception instanceof RequestException) {
                        return in_array(
                            $exception->response->status(),
                            [429, 500, 502, 503, 504],
                            true
                        );
                    }

                    return false;
                })
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                    [
                        'systemInstruction' => [
                            'parts' => [
                                ['text' => $systemInstruction],
                            ],
                        ],
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                ],
                            ],
                        ],
                        'generationConfig' => $generationConfig,
                    ]
                )
                ->throw();

            $text = $response->json('candidates.0.content.parts.0.text');

            if (!is_string($text) || trim($text) === '') {
                throw new RuntimeException('Gemini returned an empty response.');
            }

            $usage = $response->json('usageMetadata', []);

            $inputTokens = $usage['promptTokenCount'] ?? null;
            $outputTokens = $usage['candidatesTokenCount'] ?? null;

            $result = trim($text);

        } catch (\Throwable $exception) {
            AiRequest::create([
                'ticket_id' => $ticket->id,
                'type' => $type,
                'model' => $model,
                'duration_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
                'success' => false,
                'error_message' => mb_substr($exception->getMessage(), 0, 1000),
            ]);

            throw $exception;
        }

        AiRequest::create([
            'ticket_id' => $ticket->id,
            'type' => $type,
            'model' => $model,
            'duration_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
            'input_tokens' => is_numeric($inputTokens) ? (int) $inputTokens : null,
            'output_tokens' => is_numeric($outputTokens) ? (int) $outputTokens : null,
            'success' => true,
            'error_message' => null,
        ]);

        return $result;
    }


    public function classify(Ticket $ticket): TicketClassification
    {
        $systemInstruction = file_get_contents(
            resource_path('prompts/classification-system.txt')
        );

        $customerDetails = json_encode([
            'name' => $ticket->name,
            'subject' => $ticket->subject,
            'message' => $ticket->message,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $promptTemplate = file_get_contents(
            resource_path('prompts/classification-user.txt')
        );

        if ($systemInstruction === false || $promptTemplate === false) {
            throw new RuntimeException('Unable to load classification prompts.');
        }

        $prompt = str_replace(
            '{{customer_details}}',
            $customerDetails,
            $promptTemplate
        );

        $strictPrompt = $prompt . "\n\nIMPORTANT: Your previous response was invalid. Return exactly five required fields with valid values. Do not include additional fields or Markdown.";

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $rawResponse = $this->generateContent(
                $ticket,
                'classify',
                $systemInstruction,
                $attempt === 0 ? $prompt : $strictPrompt,
                true
            );

            try {
                $data = json_decode(
                    $rawResponse,
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

                $requiredFields = [
                    'category',
                    'priority',
                    'sentiment',
                    'summary',
                    'language',
                ];

                if (
                    !is_array($data) ||
                    array_is_list($data) ||
                    count($data) !== count($requiredFields) ||
                    array_diff($requiredFields, array_keys($data)) !== []
                ) {
                    throw new InvalidArgumentException(
                        'Classification fields are invalid.'
                    );
                }

                if (
                    !is_string($data['category']) ||
                    !is_string($data['priority']) ||
                    !is_string($data['sentiment']) ||
                    !is_string($data['summary']) ||
                    !is_string($data['language'])
                ) {
                    throw new InvalidArgumentException(
                        'Classification fields must contain strings.'
                    );
                }

                $category = TicketCategory::tryFrom($data['category']);
                $priority = TicketPriority::tryFrom($data['priority']);
                $sentiment = TicketSentiment::tryFrom($data['sentiment']);

                $summary = $data['summary'];
                $language = $data['language'];

                if (
                    !$category ||
                    !$priority ||
                    !$sentiment ||
                    !is_string($summary) ||
                    trim($summary) === '' ||
                    mb_strlen($summary) > 200 ||
                    !is_string($language) ||
                    !preg_match('/^[a-z]{2}$/i', $language)
                ) {
                    throw new InvalidArgumentException(
                        'Classification values are invalid.'
                    );
                }

                return new TicketClassification(
                    category: $category,
                    priority: $priority,
                    sentiment: $sentiment,
                    summary: trim($summary),
                    language: strtolower($language),
                );

            } catch (JsonException | InvalidArgumentException $exception) {
                if ($attempt === 1) {
                    Log::error('Gemini returned an invalid ticket classification.', [
                        'ticket_id' => $ticket->id,
                        'response' => mb_substr($rawResponse, 0, 5000),
                        'error' => $exception->getMessage(),
                    ]);

                    throw new RuntimeException(
                        'Gemini returned an invalid classification.',
                        0,
                        $exception
                    );
                }
            }
        }

        throw new RuntimeException('Unable to classify ticket.');
    }

    private function findRelevantFaqs(Ticket $ticket): string
    {
        $faqContent = file_get_contents(
            resource_path('prompts/faq.txt')
        );

        if ($faqContent === false) {
            throw new RuntimeException('Unable to load FAQ knowledge base.');
        }

        $entries = preg_split('/^\s*---\s*$/m', $faqContent);

        $ticketText = mb_strtolower(
            $ticket->subject . ' ' . $ticket->message
        );

        $stopWords = [
            'the', 'and', 'for', 'how', 'what', 'when', 'where',
            'why', 'can', 'does', 'do', 'is', 'are', 'my', 'i',
            'me', 'to', 'a', 'an', 'in', 'on', 'of', 'it', 'this',
            'that', 'with', 'from', 'your', 'you', 'please',
        ];

        $ticketWords = preg_split(
            '/[^\p{L}\p{N}]+/u',
            $ticketText,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        $ticketWords = array_unique(array_diff($ticketWords, $stopWords));

        $matches = [];

        foreach ($entries as $entry) {
            if (!preg_match('/Q:\s*(.+)/u', $entry, $questionMatch)) {
                continue;
            }

            $question = mb_strtolower($questionMatch[1]);

            $faqWords = preg_split(
                '/[^\p{L}\p{N}]+/u',
                $question,
                -1,
                PREG_SPLIT_NO_EMPTY
            );

            $faqWords = array_unique(array_diff($faqWords, $stopWords));

            $score = count(array_intersect($ticketWords, $faqWords));

            if ($score >= 2) {
                $matches[] = [
                    'score' => $score,
                    'content' => trim($entry),
                ];
            }
        }

        usort(
            $matches,
            fn ($a, $b) => $b['score'] <=> $a['score']
        );

        return implode(
            "\n\n---\n\n",
            array_column(array_slice($matches, 0, 1), 'content')
        );
    }

    public function draftReply(Ticket $ticket): string
    {
        $systemInstruction = file_get_contents(
            resource_path('prompts/draft-system.txt')
        );

        $customerDetails = json_encode([
            'name' => $ticket->name,
            'subject' => $ticket->subject,
            'message' => $ticket->message,
            'category' => $ticket->category?->value,
            'priority' => $ticket->priority?->value,
            'summary' => $ticket->summary,
            'language' => $ticket->language,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $promptTemplate = file_get_contents(
            resource_path('prompts/draft-user.txt')
        );

        if ($systemInstruction === false || $promptTemplate === false) {
            throw new RuntimeException('Unable to load draft prompts.');
        }

        $prompt = str_replace(
            '{{customer_details}}',
            $customerDetails,
            $promptTemplate
        );

        $relevantFaqs = $this->findRelevantFaqs($ticket);

        if ($relevantFaqs !== '') {
            $prompt .= "\n\nRelevant company FAQ information:\n";
            $prompt .= $relevantFaqs;
        }

        return $this->generateContent(
            $ticket,
            'draft',
            $systemInstruction,
            $prompt
        );
    }
}

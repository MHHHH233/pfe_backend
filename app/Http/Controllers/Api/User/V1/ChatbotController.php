<?php

namespace App\Http\Controllers\Api\User\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    /**
     * The default system message for the chatbot
     */
    protected $defaultSystemMessage = "You are a helpful assistant that provides accurate and concise information.";

    /**
     * Send a message to the AI chatbot via OpenRouter API
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
            'system_message' => 'nullable|string|max:1000',
            'conversation_history' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get user message from request
            $userMessage = $request->input('message');
            
            // Get system message or use default
            $systemMessage = $request->input('system_message', $this->defaultSystemMessage);
            
            // Get conversation history from request or initialize new one
            $conversationHistory = $request->input('conversation_history', []);
            
            // If this is the first message or no system message, add the system message
            if (empty($conversationHistory)) {
                $conversationHistory[] = [
                    'role' => 'system',
                    'content' => $systemMessage
                ];
            } else if ($request->has('system_message')) {
                // If system message is provided and conversation exists, update the system message
                if (isset($conversationHistory[0]) && $conversationHistory[0]['role'] === 'system') {
                    $conversationHistory[0]['content'] = $systemMessage;
                } else {
                    // Insert system message at the beginning
                    array_unshift($conversationHistory, [
                        'role' => 'system',
                        'content' => $systemMessage
                    ]);
                }
            }
            
            // Add user message to conversation history
            $conversationHistory[] = [
                'role' => 'user',
                'content' => $userMessage
            ];
            
            // Prepare the messages for OpenRouter API
            $messages = $conversationHistory;
            
            // If conversation is getting too long, keep system message and last 10 messages
            if (count($messages) > 11) {
                $systemMessage = $messages[0];
                $messages = array_slice($messages, -10);
                array_unshift($messages, $systemMessage);
            }
            
            // Get API key from environment
            $apiKey = env('OPENROUTER_API_KEY');
            
            if (!$apiKey) {
                Log::error('OpenRouter API key not found in environment variables');
                return response()->json([
                    'success' => false,
                    'message' => 'API configuration error'
                ], 500);
            }
            
            // Get model from request or use default
            $model = $request->input('model', 'openai/gpt-3.5-turbo');
            
            // Make the API request to OpenRouter
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => env('APP_URL', 'http://localhost'), // Required by OpenRouter
                'X-Title' => env('APP_NAME', 'Laravel App') // Recommended by OpenRouter
            ])->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 1000
            ]);
            
            // Check if the request was successful
            if ($response->successful()) {
                $responseData = $response->json();
                
                // Extract the assistant's message
                $assistantMessage = $responseData['choices'][0]['message']['content'] ?? null;
                
                if ($assistantMessage) {
                    // Add assistant response to conversation history
                    $conversationHistory[] = [
                        'role' => 'assistant',
                        'content' => $assistantMessage
                    ];
                    
                    // Return response with additional metadata and updated conversation history
                    return response()->json([
                        'success' => true,
                        'message' => $assistantMessage,
                        'conversation_history' => $conversationHistory,
                        'metadata' => [
                            'model' => $responseData['model'] ?? $model,
                            'usage' => $responseData['usage'] ?? null,
                            'conversation_length' => count($conversationHistory)
                        ]
                    ]);
                } else {
                    Log::error('No message content in OpenRouter API response', ['response' => $responseData]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to get response from AI'
                    ], 500);
                }
            } else {
                Log::error('OpenRouter API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to communicate with AI service',
                    'error' => $response->json()['error']['message'] ?? 'Unknown error'
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Exception in ChatbotController::sendMessage', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Clear the conversation history
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearConversation(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Conversation history cleared',
            'conversation_history' => []
        ]);
    }
    
    /**
     * Get available AI models from OpenRouter
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableModels(Request $request)
    {
        try {
            $apiKey = env('OPENROUTER_API_KEY');
            
            if (!$apiKey) {
                Log::error('OpenRouter API key not found in environment variables');
                return response()->json([
                    'success' => false,
                    'message' => 'API configuration error'
                ], 500);
            }
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey
            ])->get('https://openrouter.ai/api/v1/models');
            
            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'models' => $response->json()['data']
                ]);
            } else {
                Log::error('Failed to fetch models from OpenRouter', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch available models'
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Exception in ChatbotController::getAvailableModels', [
                'exception' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching available models',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 
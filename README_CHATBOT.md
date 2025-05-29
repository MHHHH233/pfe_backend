# ChatbotController Documentation

This document provides information on how to use the ChatbotController for AI-powered conversations in your application.

## Setup

1. Add the OpenRouter API key to your `.env` file:
```
OPENROUTER_API_KEY=your_api_key_here
```

2. You can get an API key from [OpenRouter](https://openrouter.ai/).

## Available Endpoints

### 1. Send a Message

**Endpoint:** `POST /api/user/v1/chatbot/send`

**Rate Limit:** 5 requests per minute

**Request Body:**
```json
{
  "message": "Your message to the AI",
  "system_message": "(Optional) Custom system message to guide the AI's behavior",
  "model": "(Optional) Specific model ID to use (defaults to openai/gpt-3.5-turbo)",
  "conversation_history": "(Optional) Array of previous messages in the conversation"
}
```

**Response:**
```json
{
  "success": true,
  "message": "AI's response message",
  "conversation_history": [
    {"role": "system", "content": "System message"},
    {"role": "user", "content": "User message"},
    {"role": "assistant", "content": "AI response"}
  ],
  "metadata": {
    "model": "The model that was used",
    "usage": {
      "prompt_tokens": 123,
      "completion_tokens": 456,
      "total_tokens": 579
    },
    "conversation_length": 3
  }
}
```

### 2. Clear Conversation

**Endpoint:** `POST /api/user/v1/chatbot/clear`

**Rate Limit:** 5 requests per minute

**Response:**
```json
{
  "success": true,
  "message": "Conversation history cleared",
  "conversation_history": []
}
```

### 3. Get Available Models

**Endpoint:** `GET /api/user/v1/chatbot/models`

**Rate Limit:** 5 requests per minute

**Response:**
```json
{
  "success": true,
  "models": [
    {
      "id": "openai/gpt-3.5-turbo",
      "name": "GPT-3.5 Turbo",
      "description": "...",
      "pricing": {
        "prompt": "0.0015",
        "completion": "0.002"
      },
      "context_length": 16385
    },
    // ... other models
  ]
}
```

## Usage Examples

### Frontend Example (JavaScript)

```javascript
// Initialize conversation history
let conversationHistory = [];

// Send a message to the chatbot
async function sendMessage(message) {
  try {
    const response = await fetch('/api/user/v1/chatbot/send', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ 
        message,
        conversation_history: conversationHistory
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Update conversation history with the response
      conversationHistory = data.conversation_history;
      
      // Handle successful response
      console.log('AI response:', data.message);
      return data.message;
    } else {
      // Handle error
      console.error('Error:', data.message);
      return null;
    }
  } catch (error) {
    console.error('Failed to send message:', error);
    return null;
  }
}

// Clear the conversation history
async function clearConversation() {
  try {
    const response = await fetch('/api/user/v1/chatbot/clear', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      }
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Update local conversation history
      conversationHistory = data.conversation_history;
      console.log('Conversation cleared');
      return true;
    } else {
      console.error('Error clearing conversation:', data.message);
      return false;
    }
  } catch (error) {
    console.error('Failed to clear conversation:', error);
    return false;
  }
}

// Change the AI model
async function changeModel(model) {
  // Store the current model to use in the next request
  currentModel = model;
  
  // You can send a message with the new model
  return sendMessage("Hello, I'm now using a different model.", currentModel);
}
```

## Error Handling

The API will return appropriate HTTP status codes:

- `200 OK`: Request was successful
- `422 Unprocessable Entity`: Validation failed
- `429 Too Many Requests`: Rate limit exceeded
- `500 Internal Server Error`: Server error or issue with OpenRouter API

## Notes

- The conversation history is maintained on the client side and should be sent with each request
- The system automatically limits conversation history to the last 10 messages to prevent token overages
- You can customize the default system message by modifying the `$defaultSystemMessage` property in the controller 
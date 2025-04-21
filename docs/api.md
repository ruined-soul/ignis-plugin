Ignis Plugin REST API Documentation
Overview
The Ignis Plugin provides REST API endpoints for Shortener, Chatroom, and user data interactions, secured with private keys or user authentication.
Base URL
https://your-site.com/wp-json/ignis/v1/

Authentication

Shortener/Chatroom Endpoints: Require X-Ignis-Shortener-Key or X-Ignis-Chatroom-Key headers, set in Ignis Control > Settings.
User Endpoints: Require user authentication via WordPress cookies.

Endpoints
1. Shortener
POST /shortener
Creates a shortened URL.

Parameters:
url (string, required): The URL to shorten (e.g., https://example.com).


Headers:
X-Ignis-Shortener-Key: Private key from settings.


Response:{
  "success": true,
  "short_url": "https://short.url/abc123"
}


Error Response:{
  "success": false,
  "message": "Failed to shorten URL"
}


Status Codes:
200: Success
400: Invalid URL or key
401: Unauthorized



2. Chatroom
GET /chatroom
Retrieves the chatroom URL.

Headers:
X-Ignis-Chatroom-Key: Private key from settings.


Response:{
  "success": true,
  "chatroom_url": "https://chatroom.url/join"
}


Error Response:{
  "success": false,
  "message": "Failed to retrieve chatroom URL"
}


Status Codes:
200: Success
400: Invalid request
401: Unauthorized



3. User Points
GET /user/points
Retrieves the authenticated user’s points balance.

Authentication: Requires logged-in user.
Response:{
  "success": true,
  "points": 150
}


Error Response:{
  "success": false,
  "message": "Unauthorized"
}


Status Codes:
200: Success
401: Unauthorized



Usage Example
curl -X POST https://your-site.com/wp-json/ignis/v1/shortener \
  -H "X-Ignis-Shortener-Key: your_key" \
  -d '{"url":"https://example.com"}'

Notes

Shortener and Chatroom endpoints require external service integration (e.g., Bitly, Telegram). Configure in core/api.php.
Extend endpoints by adding routes in core/api.php.
Secure private keys and avoid exposing them in client-side code.

Support
For issues, contact @IgnisReborn on Telegram.

# API Documentation

Base URL: `http://localhost:8000/api`

## 📝 Table of Contents
- [Authentication](#authentication)
- [User Profile](#user-profile)
- [Summaries](#summaries)
- [Guest Summaries](#guest-summaries)

---

## 🔐 Authentication

### Register
**POST** `/auth/register`

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "name": "John Doe"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Registration successful",
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refresh_token": "a1b2c3d4e5f6g7h8i9j0...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "John Doe",
    "auth_provider": "email"
  }
}
```

---

### Login
**POST** `/auth/login`

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refresh_token": "a1b2c3d4e5f6g7h8i9j0...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "John Doe",
    "auth_provider": "email"
  }
}
```

---

### Refresh Token
**POST** `/auth/refresh`

**Request:**
```json
{
  "refresh_token": "a1b2c3d4e5f6g7h8i9j0..."
}
```

**Response (200):**
```json
{
  "success": true,
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "John Doe"
  }
}
```

---

### Google OAuth
**POST** `/auth/google`

**Request:**
```json
{
  "token": "google_id_token_here"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Google authentication successful",
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refresh_token": "a1b2c3d4e5f6g7h8i9j0...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "John Doe",
    "auth_provider": "google"
  }
}
```

---

### Logout
**POST** `/auth/logout`

**Request:**
```json
{
  "refresh_token": "a1b2c3d4e5f6g7h8i9j0..."
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Logout successful"
}
```

---

### Logout from All Devices
**POST** `/auth/logout-all`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Logged out from all devices successfully"
}
```

---

## 👤 User Profile

### Get Profile
**GET** `/user/profile`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "John Doe",
    "auth_provider": "email",
    "created_at": "2025-12-27 20:00:00"
  }
}
```

### Update Profile
**PUT** `/user/profile`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Request:**
```json
{
  "name": "John Updated"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Profile updated successfully",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "John Updated",
    "auth_provider": "email"
  }
}
```

---

## 📄 Summaries (Authenticated)

### Create Summary
**POST** `/summary`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Request:**
```json
{
  "text": "Your long text to summarize...",
  "summary_type": "brief"
}
```

**Response (201):**
```json
{
  "success": true,
  "summary": {
    "id": 1,
    "original_text": "Your long text...",
    "summary": "Summarized version...",
    "summary_type": "brief",
    "created_at": "2025-12-27 20:00:00"
  }
}
```

---

### Get Summary History
**GET** `/summary/history`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "summaries": [
    {
      "id": 1,
      "summary": "Summarized version...",
      "summary_type": "brief",
      "created_at": "2025-12-27 20:00:00"
    }
  ]
}
```

---

### Get Single Summary
**GET** `/summary/:id`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "summary": {
    "id": 1,
    "original_text": "Your long text...",
    "summary": "Summarized version...",
    "summary_type": "brief",
    "created_at": "2025-12-27 20:00:00"
  }
}
```

---

### Delete Summary
**DELETE** `/summary/:id`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Summary deleted successfully"
}
```

---

## 🌐 Guest Summaries

### Create Guest Summary
**POST** `/summary/guest`

**Request:**
```json
{
  "text": "Your long text to summarize...",
  "summary_type": "brief"
}
```

**Response (201):**
```json
{
  "success": true,
  "summary": "Summarized version...",
  "remaining_summaries": 2
}
```

**Rate Limit:** 3 summaries per day per IP

---

### Get Guest Status
**GET** `/guest/status`

**Response (200):**
```json
{
  "remaining_summaries": 2,
  "daily_limit": 3,
  "reset_at": "2025-12-28 00:00:00"
}
```

---

## ❌ Error Responses

### 400 Bad Request
```json
{
  "error": "Validation failed",
  "errors": {
    "email": ["Email is required"],
    "password": ["Password must be at least 8 characters"]
  }
}
```

### 401 Unauthorized
```json
{
  "error": "Invalid token",
  "message": "Token has expired"
}
```

### 404 Not Found
```json
{
  "error": "Route not found",
  "path": "/api/wrong-route",
  "method": "GET"
}
```

### 500 Internal Server Error
```json
{
  "error": "Internal server error",
  "message": "Something went wrong"
}
```

---

## 🔄 Token Lifecycle

1. **Login/Register** → Get `access_token` + `refresh_token`
2. **Store both tokens** in localStorage/secure storage
3. **Use access_token** for all API requests (valid for 15 minutes)
4. **When access_token expires** → Use refresh_token to get new access_token
5. **Logout** → Revoke refresh_token

### Frontend Token Management Example
```javascript
// Store tokens after login
localStorage.setItem('access_token', data.access_token);
localStorage.setItem('refresh_token', data.refresh_token);

// Make API request with auto-refresh
async function apiRequest(url, options = {}) {
  let token = localStorage.getItem('access_token');
  
  let response = await fetch(url, {
    ...options,
    headers: {
      ...options.headers,
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  // If 401, try to refresh
  if (response.status === 401) {
    const refreshed = await refreshAccessToken();
    if (refreshed) {
      // Retry with new token
      token = localStorage.getItem('access_token');
      response = await fetch(url, {
        ...options,
        headers: {
          ...options.headers,
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });
    } else {
      // Redirect to login
      window.location.href = '/login';
    }
  }
  
  return response.json();
}

// Refresh token function
async function refreshAccessToken() {
  const refreshToken = localStorage.getItem('refresh_token');
  
  const response = await fetch('/api/auth/refresh', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ refresh_token: refreshToken })
  });
  
  if (response.ok) {
    const data = await response.json();
    localStorage.setItem('access_token', data.access_token);
    return true;
  }
  
  return false;
}
```
#!/bin/bash

echo "=========================================="
echo "Complete Authentication Flow Test"
echo "=========================================="
echo ""

GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

BASE_URL="http://localhost:8000"

# Test 1: No token
echo -e "${BLUE}Test 1: Access protected route without token${NC}"
NO_TOKEN=$(curl -s -X GET "$BASE_URL/api/user/profile")
echo "$NO_TOKEN" | jq '.'

if echo "$NO_TOKEN" | jq -e '.error' > /dev/null; then
    echo -e "${GREEN}✅ Correctly rejected request without token${NC}"
else
    echo -e "${RED}❌ Should have rejected request without token${NC}"
fi
echo ""

# Test 2: Register and get tokens
echo -e "${BLUE}Test 2: Register new user${NC}"
REGISTER=$(curl -s -X POST "$BASE_URL/api/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "complete-test@example.com",
    "password": "password123",
    "name": "Complete Test"
  }')

echo "$REGISTER" | jq '.'

ACCESS_TOKEN=$(echo $REGISTER | jq -r '.access_token')
REFRESH_TOKEN=$(echo $REGISTER | jq -r '.refresh_token')

if [ "$ACCESS_TOKEN" != "null" ]; then
    echo -e "${GREEN}✅ Registration successful${NC}"
    echo -e "${YELLOW}Access Token: ${ACCESS_TOKEN:0:40}...${NC}"
    echo -e "${YELLOW}Refresh Token: ${REFRESH_TOKEN:0:40}...${NC}"
else
    echo -e "${RED}❌ Registration failed${NC}"
    exit 1
fi
echo ""

# Test 3: Use access token
echo -e "${BLUE}Test 3: Access protected route with valid token${NC}"
PROFILE=$(curl -s -X GET "$BASE_URL/api/user/profile" \
  -H "Authorization: Bearer $ACCESS_TOKEN")

echo "$PROFILE" | jq '.'

if echo "$PROFILE" | jq -e '.user' > /dev/null; then
    echo -e "${GREEN}✅ Successfully accessed protected route${NC}"
else
    echo -e "${RED}❌ Failed to access protected route${NC}"
fi
echo ""

# Test 4: Invalid token
echo -e "${BLUE}Test 4: Try with invalid token${NC}"
INVALID=$(curl -s -X GET "$BASE_URL/api/user/profile" \
  -H "Authorization: Bearer invalid_token_12345")

echo "$INVALID" | jq '.'

if echo "$INVALID" | jq -e '.error' > /dev/null; then
    echo -e "${GREEN}✅ Correctly rejected invalid token${NC}"
else
    echo -e "${RED}❌ Should have rejected invalid token${NC}"
fi
echo ""

# Test 5: Refresh access token
echo -e "${BLUE}Test 5: Refresh access token${NC}"
REFRESH_RESPONSE=$(curl -s -X POST "$BASE_URL/api/auth/refresh" \
  -H "Content-Type: application/json" \
  -d "{\"refresh_token\": \"$REFRESH_TOKEN\"}")

echo "$REFRESH_RESPONSE" | jq '.'

NEW_ACCESS_TOKEN=$(echo $REFRESH_RESPONSE | jq -r '.access_token')

if [ "$NEW_ACCESS_TOKEN" != "null" ]; then
    echo -e "${GREEN}✅ Token refresh successful${NC}"
    echo -e "${YELLOW}New Access Token: ${NEW_ACCESS_TOKEN:0:40}...${NC}"
else
    echo -e "${RED}❌ Token refresh failed${NC}"
fi
echo ""

# Test 6: Use new access token
echo -e "${BLUE}Test 6: Use new access token${NC}"
PROFILE2=$(curl -s -X GET "$BASE_URL/api/user/profile" \
  -H "Authorization: Bearer $NEW_ACCESS_TOKEN")

echo "$PROFILE2" | jq '.'

if echo "$PROFILE2" | jq -e '.user' > /dev/null; then
    echo -e "${GREEN}✅ New access token works${NC}"
else
    echo -e "${RED}❌ New access token failed${NC}"
fi
echo ""

# Test 7: Logout all devices
echo -e "${BLUE}Test 7: Logout from all devices${NC}"
LOGOUT_ALL=$(curl -s -X POST "$BASE_URL/api/auth/logout-all" \
  -H "Authorization: Bearer $NEW_ACCESS_TOKEN")

echo "$LOGOUT_ALL" | jq '.'

if echo "$LOGOUT_ALL" | jq -e '.success' > /dev/null; then
    echo -e "${GREEN}✅ Logout from all devices successful${NC}"
else
    echo -e "${RED}❌ Logout from all devices failed${NC}"
fi
echo ""

# Test 8: Try refresh with revoked token
echo -e "${BLUE}Test 8: Try to refresh with revoked token${NC}"
REVOKED=$(curl -s -X POST "$BASE_URL/api/auth/refresh" \
  -H "Content-Type: application/json" \
  -d "{\"refresh_token\": \"$REFRESH_TOKEN\"}")

echo "$REVOKED" | jq '.'

if echo "$REVOKED" | jq -e '.error' > /dev/null; then
    echo -e "${GREEN}✅ Correctly rejected revoked refresh token${NC}"
else
    echo -e "${RED}❌ Should have rejected revoked refresh token${NC}"
fi
echo ""

echo "=========================================="
echo "All Tests Complete!"
echo "=========================================="
#!/bin/bash

echo "=========================================="
echo "Testing Refresh Token Flow"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Step 1: Register
echo -e "${BLUE}Step 1: Registering user...${NC}"
REGISTER_RESPONSE=$(curl -s -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "refresh-test@example.com",
    "password": "password123",
    "name": "Refresh Test User"
  }')

echo "$REGISTER_RESPONSE" | jq '.'

# Extract tokens
ACCESS_TOKEN=$(echo $REGISTER_RESPONSE | jq -r '.access_token')
REFRESH_TOKEN=$(echo $REGISTER_RESPONSE | jq -r '.refresh_token')

if [ "$ACCESS_TOKEN" == "null" ] || [ -z "$ACCESS_TOKEN" ]; then
    echo -e "${RED}❌ Failed to get access token${NC}"
    exit 1
fi

if [ "$REFRESH_TOKEN" == "null" ] || [ -z "$REFRESH_TOKEN" ]; then
    echo -e "${RED}❌ Failed to get refresh token${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Got access token: ${ACCESS_TOKEN:0:30}...${NC}"
echo -e "${GREEN}✅ Got refresh token: ${REFRESH_TOKEN:0:30}...${NC}"
echo ""

# Step 2: Use access token
echo -e "${BLUE}Step 2: Testing access token on protected route...${NC}"
PROFILE_RESPONSE=$(curl -s -X GET http://localhost:8000/api/user/profile \
  -H "Authorization: Bearer $ACCESS_TOKEN")

echo "$PROFILE_RESPONSE" | jq '.'

if echo "$PROFILE_RESPONSE" | jq -e '.user' > /dev/null; then
    echo -e "${GREEN}✅ Access token works!${NC}"
else
    echo -e "${RED}❌ Access token failed${NC}"
fi
echo ""

# Step 3: Refresh the access token
echo -e "${BLUE}Step 3: Refreshing access token...${NC}"
REFRESH_RESPONSE=$(curl -s -X POST http://localhost:8000/api/auth/refresh \
  -H "Content-Type: application/json" \
  -d "{\"refresh_token\": \"$REFRESH_TOKEN\"}")

echo "$REFRESH_RESPONSE" | jq '.'

NEW_ACCESS_TOKEN=$(echo $REFRESH_RESPONSE | jq -r '.access_token')

if [ "$NEW_ACCESS_TOKEN" == "null" ] || [ -z "$NEW_ACCESS_TOKEN" ]; then
    echo -e "${RED}❌ Failed to refresh token${NC}"
else
    echo -e "${GREEN}✅ Got new access token: ${NEW_ACCESS_TOKEN:0:30}...${NC}"
fi
echo ""

# Step 4: Use new access token
echo -e "${BLUE}Step 4: Testing new access token...${NC}"
PROFILE_RESPONSE2=$(curl -s -X GET http://localhost:8000/api/user/profile \
  -H "Authorization: Bearer $NEW_ACCESS_TOKEN")

echo "$PROFILE_RESPONSE2" | jq '.'

if echo "$PROFILE_RESPONSE2" | jq -e '.user' > /dev/null; then
    echo -e "${GREEN}✅ New access token works!${NC}"
else
    echo -e "${RED}❌ New access token failed${NC}"
fi
echo ""

# Step 5: Logout (revoke refresh token)
echo -e "${BLUE}Step 5: Logging out (revoking refresh token)...${NC}"
LOGOUT_RESPONSE=$(curl -s -X POST http://localhost:8000/api/auth/logout \
  -H "Content-Type: application/json" \
  -d "{\"refresh_token\": \"$REFRESH_TOKEN\"}")

echo "$LOGOUT_RESPONSE" | jq '.'
echo -e "${GREEN}✅ Logout successful${NC}"
echo ""

# Step 6: Try to refresh with revoked token
echo -e "${BLUE}Step 6: Trying to refresh with revoked token (should fail)...${NC}"
REFRESH_FAIL=$(curl -s -X POST http://localhost:8000/api/auth/refresh \
  -H "Content-Type: application/json" \
  -d "{\"refresh_token\": \"$REFRESH_TOKEN\"}")

echo "$REFRESH_FAIL" | jq '.'

if echo "$REFRESH_FAIL" | jq -e '.error' > /dev/null; then
    echo -e "${GREEN}✅ Correctly rejected revoked token${NC}"
else
    echo -e "${RED}❌ Should have rejected revoked token${NC}"
fi
echo ""

echo "=========================================="
echo "Test Complete!"
echo "=========================================="
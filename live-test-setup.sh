#!/bin/bash

# Live API Test Setup Script for Laravel Lexware Office Package
echo "🚀 Setting up Live API Testing Environment"
echo "=========================================="

# Check if API key is provided
if [ -z "$1" ]; then
    echo ""
    echo "❌ Error: API key required"
    echo ""
    echo "Usage: ./live-test-setup.sh YOUR_API_KEY [BASE_URL]"
    echo ""
    echo "Example:"
    echo "  ./live-test-setup.sh 'your-api-key-here'"
    echo "  ./live-test-setup.sh 'your-api-key-here' 'https://api.lexoffice.io'"
    echo ""
    echo "Get your API key from: https://app.lexoffice.de/addons/public-api"
    exit 1
fi

API_KEY="$1"
BASE_URL="${2:-https://api.lexoffice.io}"

echo ""
echo "📋 Configuration:"
echo "   API Key: ${API_KEY:0:10}..." 
echo "   Base URL: $BASE_URL"
echo ""

# Export environment variables
export LEXWARE_API_KEY="$API_KEY"
export LEXWARE_BASE_URL="$BASE_URL"

echo "✅ Environment variables set!"
echo ""

# Run basic connectivity test
echo "🔌 Testing API connectivity..."
echo ""

# Run profile test first (quick connectivity check)
vendor/bin/phpunit tests/Live/LiveApiTest.php --filter=test_profile_endpoint --group=live 2>/dev/null

if [ $? -eq 0 ]; then
    echo ""
    echo "🎉 API Connection Successful!"
    echo ""
    echo "Available test suites:"
    echo "  📊 All endpoints:     vendor/bin/phpunit tests/Live/LiveApiTest.php --group=live"
    echo "  📁 File upload only:  vendor/bin/phpunit tests/Live/LiveApiTest.php --group=file-upload" 
    echo "  🔍 Single test:       vendor/bin/phpunit tests/Live/LiveApiTest.php --filter=test_contacts_crud_operations"
    echo ""
    echo "🚀 Ready to run live tests!"
else
    echo ""
    echo "❌ API Connection Failed"
    echo ""
    echo "Please check:"
    echo "  1. Your API key is correct"
    echo "  2. You have internet connectivity"
    echo "  3. The base URL is correct"
    echo "  4. Your API key has the required permissions"
    echo ""
fi
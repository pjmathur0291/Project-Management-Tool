#!/bin/bash

# XAMPP Restart Script for 10GB File Uploads
# This script restarts XAMPP with the new configuration

echo "🔄 Restarting XAMPP for 10GB file upload support..."

# Stop Apache
echo "⏹️  Stopping Apache..."
sudo /Applications/XAMPP/xamppfiles/bin/apachectl stop

# Stop MySQL
echo "⏹️  Stopping MySQL..."
sudo /Applications/XAMPP/xamppfiles/bin/mysql.server stop

# Wait a moment
sleep 2

# Start MySQL
echo "▶️  Starting MySQL..."
sudo /Applications/XAMPP/xamppfiles/bin/mysql.server start

# Start Apache
echo "▶️  Starting Apache..."
sudo /Applications/XAMPP/xamppfiles/bin/apachectl start

# Wait for services to be ready
sleep 3

# Check if services are running
echo "🔍 Checking service status..."

if pgrep -f "httpd" > /dev/null; then
    echo "✅ Apache is running"
else
    echo "❌ Apache failed to start"
fi

if pgrep -f "mysqld" > /dev/null; then
    echo "✅ MySQL is running"
else
    echo "❌ MySQL failed to start"
fi

echo ""
echo "🎉 XAMPP restarted with 10GB upload support!"
echo ""
echo "📋 Next steps:"
echo "1. Test upload limits: http://localhost/management-tool/test-upload-limits.php"
echo "2. Try uploading your 10GB file"
echo "3. Monitor upload progress (may take 10-30 minutes)"
echo ""
echo "⚠️  Important notes:"
echo "- Ensure stable internet connection"
echo "- Don't close browser during upload"
echo "- Check server disk space (need >10GB free)"
echo "- Upload may take 10-30 minutes depending on connection speed"

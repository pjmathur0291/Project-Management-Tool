#!/bin/bash

echo "🔧 Fixing Upload Directory Permissions..."

# Check if uploads directory exists
if [ ! -d "uploads" ]; then
    echo "❌ Uploads directory not found. Creating it..."
    mkdir -p uploads/{documents,images,videos,thumbnails}
fi

# Set permissions for uploads directory and subdirectories
echo "📁 Setting permissions for uploads directory..."
chmod -R 777 uploads/

# Verify permissions
echo "✅ Verifying permissions..."
ls -la uploads/

echo ""
echo "🎉 Upload permissions fixed!"
echo ""
echo "📋 Next steps:"
echo "1. Try uploading your file again"
echo "2. If still having issues, restart XAMPP: ./restart-xampp-for-10gb.sh"
echo "3. Test upload limits: http://localhost/management-tool/test-upload-limits.php"
echo ""

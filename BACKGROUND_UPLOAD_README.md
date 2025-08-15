# 🚀 Background File Upload System

## Overview

The **Background File Upload System** allows you to upload large files (up to 10GB) while continuing to work on other tasks. Files upload in the background, and you receive real-time notifications about upload progress and completion.

## 🎯 Key Features

### ✅ **Background Processing**
- **Start upload and continue working** - No need to wait for uploads to complete
- **Switch between tabs** - Uploads continue in the background
- **Close upload page** - Files still upload to completion
- **Real-time progress tracking** - Monitor upload status with visual progress bars

### ✅ **10GB File Support**
- **Maximum file size**: 10GB per file
- **Multiple file uploads** - Upload several files simultaneously
- **All file types supported** - Images, videos, documents, archives, etc.
- **Smart file validation** - Automatic size and type checking

### ✅ **User-Friendly Interface**
- **Drag & drop support** - Simply drag files onto the upload area
- **Task selection** - Choose which task to attach files to
- **Upload queue management** - View all pending and active uploads
- **Cancel/retry functionality** - Control uploads as needed

### ✅ **Real-Time Notifications**
- **Success notifications** - Know when files upload successfully
- **Error notifications** - Get detailed error messages
- **Progress updates** - See upload percentage and speed
- **Auto-dismissing alerts** - Clean, non-intrusive notifications

## 📁 Available Upload Pages

### 1. **Background Upload** (`background-upload-simple.php`)
- **Primary upload interface** for background processing
- **Task selection dropdown** - Choose destination task
- **Upload queue display** - See all active uploads
- **Quick action links** - Easy navigation to other features

### 2. **Large File Upload** (`large-file-upload.php`)
- **Enhanced progress tracking** with detailed statistics
- **Upload speed monitoring** and time remaining estimates
- **Server configuration display** - See current upload limits
- **File information preview** before upload

### 3. **Task Detail Upload** (`task-detail.php`)
- **Direct file attachment** to specific tasks
- **File gallery view** - See all attached files
- **Inline upload forms** - Upload while viewing task details

## 🛠️ How to Use

### **Step 1: Access Background Upload**
1. **Login** to your account
2. **Navigate** to "Background Upload" in the sidebar
3. **Or** click "Background Upload" button in Task Management

### **Step 2: Select Destination Task**
1. **Choose a task** from the dropdown menu
2. **Verify task details** (title, status, etc.)
3. **Ensure task exists** before starting upload

### **Step 3: Upload Files**
1. **Drag & drop** files onto the upload area
2. **Or click** to browse and select files
3. **Watch progress** in real-time
4. **Continue working** on other tasks

### **Step 4: Monitor Progress**
- **Upload queue** shows all active uploads
- **Progress bars** display completion percentage
- **Status indicators** show current state (queued, uploading, completed, error)
- **Notifications** appear for important events

## 🔧 Technical Configuration

### **Server Settings (Already Configured)**
```apache
# .htaccess Configuration
php_value upload_max_filesize 10G
php_value post_max_size 10G
php_value max_execution_time 1800
php_value max_input_time 1800
php_value memory_limit 1G
LimitRequestBody 10737418240
```

### **Database Settings**
```sql
-- Maximum file size: 10GB
UPDATE system_settings SET setting_value = '10737418240' WHERE setting_key = 'max_file_size';
```

### **PHP Configuration**
```ini
; php.ini settings for 10GB uploads
upload_max_filesize = 10G
post_max_size = 10G
max_execution_time = 1800
memory_limit = 1G
max_input_time = 1800
```

## 📊 Upload Performance

### **Expected Upload Times**
| Connection Speed | 1GB File | 5GB File | 10GB File |
|------------------|----------|----------|-----------|
| **100 Mbps**     | ~2 min   | ~10 min  | ~20 min   |
| **50 Mbps**      | ~4 min   | ~20 min  | ~40 min   |
| **25 Mbps**      | ~8 min   | ~40 min  | ~80 min   |
| **10 Mbps**      | ~20 min  | ~100 min | ~200 min  |

### **Best Practices**
- **Use wired connection** when possible
- **Close unnecessary applications** to free bandwidth
- **Monitor upload progress** but don't interrupt
- **Keep browser tab active** for best performance
- **Backup important files** before large uploads

## 🚨 Troubleshooting

### **Common Issues**

#### **Upload Fails with 413 Error**
- **Cause**: File too large for server configuration
- **Solution**: Verify server limits with `test-upload-limits.php`
- **Action**: Restart XAMPP with `./restart-xampp-for-10gb.sh`

#### **Upload Stuck at 0%**
- **Cause**: Network connectivity issues
- **Solution**: Check internet connection
- **Action**: Try smaller file first, then retry large file

#### **Upload Cancelled Unexpectedly**
- **Cause**: Browser tab closed or network interruption
- **Solution**: Keep upload page open
- **Action**: Use background upload to continue working

#### **File Not Appearing in Task**
- **Cause**: Upload completed but file not linked
- **Solution**: Refresh task detail page
- **Action**: Check upload notifications for success messages

### **Error Messages**
- **"File too large"** - Exceeds 10GB limit
- **"Network error"** - Connection issues
- **"Invalid file type"** - Unsupported file format
- **"Server error"** - Backend processing issue

## 🔗 Integration Points

### **Navigation Links**
- **Sidebar**: "Background Upload" menu item
- **Task Management**: Upload buttons in action bar
- **Dashboard**: Quick access links
- **Task Details**: Inline upload forms

### **API Endpoints**
- **`api/upload.php`** - File upload processing
- **`api/tasks.php`** - Task data retrieval
- **`api/projects.php`** - Project information

### **Database Tables**
- **`multimedia_files`** - File metadata storage
- **`tasks`** - Task information and file counts
- **`system_settings`** - Upload configuration

## 📱 Browser Compatibility

### **Supported Browsers**
- ✅ **Chrome** (recommended)
- ✅ **Firefox**
- ✅ **Safari**
- ✅ **Edge**

### **Required Features**
- **File API** - For drag & drop functionality
- **XMLHttpRequest** - For background uploads
- **ES6 Support** - For modern JavaScript features

## 🔒 Security Features

### **File Validation**
- **Size limits** - Enforced server-side
- **Type checking** - MIME type validation
- **Virus scanning** - Optional integration point
- **Access control** - Role-based permissions

### **User Permissions**
- **All users** can upload files to assigned tasks
- **Admins/Managers** can upload to any task
- **File deletion** restricted to uploader or admin/manager
- **Task access** controlled by role and assignment

## 🎨 User Interface

### **Upload Area**
- **Drag & drop zone** with visual feedback
- **File input** for traditional file selection
- **Progress indicators** for each file
- **Status badges** showing upload state

### **Queue Management**
- **File list** with names and sizes
- **Progress bars** for active uploads
- **Action buttons** (cancel, retry)
- **Status indicators** (queued, uploading, completed, error)

### **Notifications**
- **Toast notifications** in top-right corner
- **Auto-dismissing** after 5 seconds
- **Color-coded** by type (success, error, warning)
- **Click to dismiss** functionality

## 📈 Performance Monitoring

### **Upload Statistics**
- **Total files** in queue
- **Active uploads** count
- **Completed uploads** count
- **Total data size** being uploaded

### **Progress Tracking**
- **Percentage complete** for each file
- **Upload speed** in real-time
- **Time remaining** estimates
- **Bytes transferred** counter

## 🔄 Future Enhancements

### **Planned Features**
- **Resume uploads** after interruption
- **Chunked uploads** for very large files
- **Upload scheduling** for off-peak hours
- **Batch operations** for multiple files
- **Cloud storage** integration

### **Integration Possibilities**
- **Google Drive** sync
- **Dropbox** integration
- **OneDrive** support
- **FTP** upload options
- **Email** file sharing

## 📞 Support

### **Getting Help**
1. **Check troubleshooting** section above
2. **Test upload limits** with `test-upload-limits.php`
3. **Review server logs** for detailed errors
4. **Contact administrator** for system issues

### **Useful Links**
- **Upload Limits Test**: `test-upload-limits.php`
- **Large File Upload**: `large-file-upload.php`
- **Background Upload**: `background-upload-simple.php`
- **Task Management**: `task-management.php`
- **Dashboard**: `index.php`

---

## 🎉 Ready to Upload!

Your system is now fully configured for **background file uploads up to 10GB**. Start uploading your large files while continuing to work on other tasks!

**Quick Start:**
1. Go to **Background Upload** in the sidebar
2. Select a **destination task**
3. **Drag & drop** your files
4. **Continue working** while files upload in the background
5. **Monitor progress** with real-time notifications

**Happy Uploading! 🚀**

# Task Creation Troubleshooting Guide

## Issue: Unable to Create Tasks

### Step 1: Check User Login Status
1. Make sure you are logged in to the system
2. Go to `login.php` and log in with valid credentials
3. Check that you see your name and role in the top-right corner

### Step 2: Check User Role and Permissions
- **Only users with 'admin' or 'manager' role can create tasks**
- **Regular 'member' users cannot create tasks**
- Check your role displayed in the top-right corner of the application

### Step 3: Verify the "Add Task" Button is Visible
- Navigate to the Tasks section
- Look for the "New Task" button in the top-right of the Tasks section
- If the button is not visible, you don't have permission to create tasks

### Step 4: Check Browser Console for Errors
1. Open your browser's Developer Tools (F12)
2. Go to the Console tab
3. Try to create a task and look for any error messages
4. Common errors:
   - "User does not have permission to create tasks"
   - "Task form not found"
   - JavaScript errors

### Step 5: Test Basic Functionality
1. Try the test files:
   - `test-task-creation.php` - Tests backend functionality
   - `test-modal.html` - Tests modal functionality
   - `session-test.php` - Tests session functionality

### Step 6: Common Solutions

#### If you don't see the "Add Task" button:
- Your user role is not 'admin' or 'manager'
- Contact an administrator to change your role

#### If the button is visible but clicking does nothing:
- Check browser console for JavaScript errors
- Try refreshing the page
- Make sure JavaScript is enabled

#### If the modal opens but form submission fails:
- Check browser console for API errors
- Verify you're logged in (session not expired)
- Check network tab for failed requests

### Step 7: Database Verification
Run the test file to verify:
- Database connection is working
- Tasks table exists and has correct structure
- User authentication is working

### Step 8: Still Having Issues?
1. Check the browser console for detailed error messages
2. Verify your user account has the correct role in the database
3. Try logging out and logging back in
4. Clear browser cache and cookies
5. Try a different browser

## Quick Fix Checklist
- [ ] User is logged in
- [ ] User role is 'admin' or 'manager'
- [ ] "Add Task" button is visible
- [ ] No JavaScript errors in console
- [ ] Database connection is working
- [ ] Session is active

## Contact Support
If you continue to have issues, provide:
1. Your user role
2. Browser console error messages
3. Steps to reproduce the issue
4. Screenshots if applicable

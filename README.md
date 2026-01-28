# School Social Network

A modern, responsive social networking platform for schools with integrated messaging functionality.

## Features

✨ **Social Feed**
- Create posts with text and images
- Like and react to posts
- Comment on posts
- Delete your own posts
- Report inappropriate content

💬 **Messaging System**
- Real-time 1-on-1 messaging
- Group conversations support
- Message history
- Online/offline status
- Unread message indicators

👥 **User Profiles**
- Customizable profiles
- Avatar support
- Year level and major information
- User search

🎨 **Professional UI/UX**
- Modern, clean design
- Fully responsive (mobile, tablet, desktop)
- Smooth animations
- Intuitive navigation

## Installation Instructions

### Requirements
- XAMPP (Apache + MySQL + PHP 7.4+)
- Modern web browser

### Setup Steps

1. **Extract the files**
   - Unzip the `school-social-network.zip` file
   - Copy the `school-social-network` folder to your XAMPP `htdocs` directory
   - Path should be: `C:\xampp\htdocs\school-social-network\`

2. **Start XAMPP**
   - Open XAMPP Control Panel
   - Start Apache and MySQL

3. **Create Database**
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Click "New" to create a new database
   - Name it: `school_social`
   - Click "Create"

4. **Import Database Schema**
   - Select the `school_social` database
   - Click on "Import" tab
   - Click "Choose File" and select `school_social_network.sql`
   - Click "Go" to import

5. **Configure Database Connection** (if needed)
   - Open `config.php` in a text editor
   - Update database credentials if different from defaults:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', ''); // Your MySQL password
     define('DB_NAME', 'school_social');
     ```

6. **Access the Application**
   - Open your browser
   - Go to: http://localhost/school-social-network/
   - You'll be redirected to the login page

7. **Create Your First Account**
   - Click "Sign up"
   - Fill in the registration form
   - Login with your credentials

## Default Directory Structure

```
school-social-network/
├── api/                    # API endpoints
│   ├── comments.php
│   ├── create_post.php
│   ├── delete_post.php
│   ├── messages.php
│   ├── report_post.php
│   ├── search_users.php
│   └── toggle_reaction.php
├── assets/
│   ├── css/
│   │   └── style.css      # Main stylesheet
│   └── js/
│       ├── main.js        # Core JavaScript
│       └── messages.js    # Messaging functionality
├── includes/
│   └── header.php         # Header component
├── uploads/               # User uploads (auto-created)
│   └── posts/
├── config.php             # Database configuration
├── index.php              # Main feed page
├── login.php              # Login page
├── register.php           # Registration page
├── messages.php           # Messaging page
├── logout.php             # Logout script
└── school_social_network.sql  # Database schema
```

## Usage Guide

### Creating Posts
1. On the main feed, type your message in the "What's on your mind?" box
2. Optionally click the photo button to add an image
3. Click "Post" to share

### Messaging
1. Click the Messages icon in the header or sidebar
2. Click "+ New" to start a conversation
3. Search for a user and click their name
4. Type your message and press Send

### Reactions & Comments
- Click the ❤️ button to like a post
- Click the 💬 button to view and add comments
- Comments appear in chronological order

### Reporting Content
- Click the ⋮ menu on any post
- Select "Report"
- Choose a reason for the report

## Troubleshooting

**Can't access the site?**
- Make sure Apache is running in XAMPP
- Check that you're using the correct URL: http://localhost/school-social-network/

**Database connection errors?**
- Verify MySQL is running in XAMPP
- Check database credentials in `config.php`
- Ensure the database `school_social` exists

**Can't upload images?**
- Check that the `uploads/posts/` directory exists
- Ensure Apache has write permissions to the uploads folder

**Login issues?**
- Clear your browser cache
- Check that the `users` table has data
- Try creating a new account

## Security Notes

⚠️ **For Development/Educational Use Only**

This is a demonstration project for learning purposes. Before deploying to production:

1. Use HTTPS
2. Implement CSRF protection
3. Add rate limiting
4. Use prepared statements for all queries (already implemented)
5. Validate and sanitize all user inputs (already implemented)
6. Hash passwords properly (already using password_hash)
7. Add email verification
8. Implement proper session management
9. Add XSS protection headers
10. Regular security updates

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Verify all installation steps were completed
3. Check Apache/PHP error logs in XAMPP

## License

This project is for educational purposes. Feel free to modify and learn from it!

## Technologies Used

- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Server**: Apache (XAMPP)

---

Enjoy your school social network! 🎓✨

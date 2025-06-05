# Voter Registration and Face Recognition Login System

A secure PHP-based election system with server-side face recognition authentication for voter and candidate registration and login.

## Features

- **Secure Authentication**
  - Face recognition login for voters (server-side processing)
  - Password-based login for all users
  - CSRF protection
  - Rate limiting for login attempts
  - Secure session management

- **User Roles**
  - Voters with face recognition login
  - Candidates with detailed profiles
  - Admin dashboard (coming soon)

- **Voter Features**
  - Face capture during registration (client-side camera access)
  - Face recognition login (server-side processing)
  - Voter ID generation
  - Wereda-based registration

- **Candidate Features**
  - Detailed candidate profiles
  - Party affiliation
  - Manifesto management
  - Campaign tools (coming soon)

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser with camera access (for capturing image)
- Python 3.6 or higher
- Python libraries: `face_recognition`, `Flask`, `Pillow`, `pymysql` (or your database connector)

## Installation

1. Clone or download this repository to your web server's document root.

2. Create a MySQL database:
   ```sql
   CREATE DATABASE election_system;
   ```

3. Import the database schema and add the face encoding column:
   ```bash
   php db_setup.php
   # Add the face_encoding column
   mysql -u your_db_user -p your_db_name <ALTER_TABLE_STATEMENT> # Replace with your details and the SQL command
   # ALTER TABLE users ADD COLUMN face_encoding LONGTEXT NULL; 
   ```
   **Note:** Manually run the `ALTER TABLE` statement using a MySQL client or tool to add the `face_encoding` column to the `users` table.

4. Set up the Python environment and install dependencies:
   ```bash
   pip install face_recognition Flask Pillow pymysql # Or your database connector
   ```

5. Configure the Python backend:
   - Edit `face_recognition_backend.py` with your database credentials.

6. Run the Python backend server:
   ```bash
   python face_recognition_backend.py
   ```
   Keep this server running in the background.

7. Set up directory permissions:
   ```bash
   chmod 755 face_img
   ```

8. Configure your web server:
   - For Apache, ensure mod_rewrite is enabled
   - For Nginx, configure URL rewriting

## Configuration

1. Database settings:
   - Edit `config/db.php` for PHP database connection.
   - Edit `face_recognition_backend.py` for Python database connection.

2. Security settings:
   - Review and adjust session settings in `config/functions.php`
   - Configure CSRF token expiration
   - Set up rate limiting parameters

3. Python backend URL:
    - If your Python backend is not running on `http://localhost:5000`, update the `PYTHON_BACKEND_URL` and `PYTHON_RECOGNITION_URL` constants in `js/register.js` and `js/login.js` respectively.

## Usage

1. Access the application through your web server:
   ```
   http://localhost/project/
   ```

2. Ensure the Python face recognition backend is running (`python face_recognition_backend.py`).

3. Default admin credentials:
   - Username: admin
   - Password: admin123
   - **Important**: Change the admin password after first login!

4. Registration process:
   - Voters: Complete registration with face capture (image sent to Python backend for encoding).
   - Candidates: Provide party and manifesto details.

5. Login options:
   - Voters: Password or Face recognition (image sent to Python backend for recognition).
   - Candidates: Password only.

## Security Considerations

- All passwords are hashed using PHP's password_hash
- Face encodings are stored in the database (requires appropriate security measures for your database).
- CSRF protection on all forms
- Rate limiting for login attempts
- Secure session management
- Input validation and sanitization
- Prepared statements for all database queries
- **Secure your Python backend:** Ensure your Flask application is not exposed directly to the internet without a proper web server (like Nginx or Apache) acting as a reverse proxy and handling HTTPS.

## Development

1. Directory structure:
   ```
   project/
   ├── config/         # Configuration files (PHP & DB)
   ├── css/           # Stylesheets
   ├── face_img/      # Directory for storing face images (if still used for other purposes, though encodings are primary)
   ├── js/            # JavaScript files (client-side camera capture and communication with backend)
   └── face_recognition_backend.py # Python backend script
   ```

2. Adding new features:
   - Follow the existing code structure.
   - Implement proper security measures (including secure communication between frontend, PHP, and Python).
   - Add appropriate error handling.
   - Update documentation.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please open an issue in the GitHub repository or contact the development team.

## Acknowledgments

- [face_recognition](https://github.com/ageitgey/face_recognition) and dlib for face recognition
- [Flask](https://flask.palletsprojects.com/) for the Python web framework
- [Pillow (PIL)](https://pillow.readthedocs.io/) for image handling in Python
- [pymysql](https://github.com/PyMySQL/PyMySQL) (or your chosen connector) for database interaction in Python
- [Inter font](https://rsms.me/inter/) for typography
- [Tailwind CSS](https://tailwindcss.com/) for utility classes
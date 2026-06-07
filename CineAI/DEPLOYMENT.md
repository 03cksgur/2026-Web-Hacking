# CineAI Deployment Guide (Windows Server & IIS)

This guide provides instructions to meet the **"개발환경 (Vmware 동작)"**, **"WAS (IIS)"**, and **"SSL (Network Security)"** requirements.

## 1. Environment Setup
*   **OS**: Windows Server (or Windows 10/11 for local testing).
*   **Database**: MySQL/MariaDB (Already migrated to `movie_reviews_db`).
*   **Web Server**: Internet Information Services (IIS).

## 2. Installing & Configuring IIS
1.  **Enable IIS**: Open "Turn Windows features on or off" and check **Internet Information Services**.
2.  **Install PHP for IIS**:
    *   Download the Non-Thread Safe (NTS) version of PHP from [windows.php.net](https://windows.php.net/download/).
    *   Install **CGI** in IIS features (Web Management Tools -> World Wide Web Services -> Application Development Features -> CGI).
    *   Use **IIS Manager** -> **Handler Mappings** -> **Add Module Mapping**:
        *   Request path: `*.php`
        *   Module: `FastCgiModule`
        *   Executable: `C:\php\php-cgi.exe`
        *   Name: `PHP_via_FastCGI`

## 3. Database Migration (MySQL)
*   Ensure MySQL/MariaDB is running on port 3306.
*   The application is already configured to use `root` with no password (default XAMPP).
*   If you change credentials, update `config/database.php`.

## 4. SSL (HTTPS) Configuration
To get the **"가산점 (SSL 구현)"**:
1.  **Create Self-Signed Certificate**:
    *   In **IIS Manager**, select the Server node -> **Server Certificates**.
    *   Click **Create Self-Signed Certificate**.
2.  **Add HTTPS Binding**:
    *   Go to **Sites** -> **Default Web Site** -> **Bindings**.
    *   Click **Add**.
    *   Type: `https`, Port: `443`.
    *   SSL certificate: Select the one you just created.
3.  **Access with HTTPS**: Open `https://localhost` in your browser.

## 5. File Permissions
*   Ensure the `uploads/` directory has **Write** permissions for the `IIS_IUSRS` group.

## 6. Accessing the App
*   Once configured in IIS, you can access the movie site via your server's IP or `localhost` (Port 80/443).

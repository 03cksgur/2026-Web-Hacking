@echo off
echo Initializing CineAI Database (One-time setup)...

:: Start MySQL temporarily to run commands
start /b "" "C:\xampp\mysql\bin\mysqld.exe" --console
echo Waiting for MySQL to start...
timeout /t 5 /nobreak >nul

:: Create database and import SQL
echo Creating database 'movie_reviews_db'...
"C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS movie_reviews_db;"

echo Importing data from movie_reviews_db_perfect.sql...
cmd /c ""C:\xampp\mysql\bin\mysql.exe" -u root movie_reviews_db < movie_reviews_db_perfect.sql"

echo.
echo Database setup complete!
echo Now you can close this window and run 'start_cineai.bat' to start the site.
pause

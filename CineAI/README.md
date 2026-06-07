# CineAI - Security Monitoring Center & Movie Review Platform

## 🛡️ Project Overview
CineAI is an AI-powered movie review platform designed with a "Security Monitoring Center" theme. It demonstrates advanced integration of Large Language Models (LLMs) with modern web technologies, prioritizing security and user experience.

### 🌟 Key AI Features
- **AI Sentiment Analysis**: Real-time analysis of user reviews to categorize emotions (Positive/Neutral/Negative).
- **AI Summary Engine**: Automatic generation of concise summaries for long-form reviews.
- **AI Spoiler Detection**: Intelligent identification of plot leaks to protect user experience.
- **AI Recommendation**: Personalization algorithms to suggest films based on user history.
- **AI Chatbot**: 24/7 security-themed movie discovery helper.

---

## 🚀 How to Run (교수님 채점 / 실행 안내)

이 프로젝트는 **PHP 8.x + SQLite(혹은 MySQL)** 기반으로 구축되어 있습니다. 윈도우 환경에서 즉시 실행이 가능하도록 배치 파일이 준비되어 있습니다.

### 방법 1. 제공된 배치 파일로 즉시 실행 (가장 권장)
1. 프로젝트 루트 폴더 내의 `setup_database_once.bat` 파일을 더블 클릭하여 데이터베이스를 초기화합니다.
2. `start_cineai.bat` 파일을 실행합니다. (실행되는 검은 콘솔 창은 닫지 마시고 최소화해 주세요.)
3. 웹 브라우저를 열고 다음 주소로 접속합니다:
   👉 **[http://localhost:8000](http://localhost:8000)**

### 방법 2. PHP 내장 웹 서버 명령어로 실행
1. 터미널(cmd 또는 PowerShell)을 열고 프로젝트 폴더 경로로 이동합니다.
2. 아래 명령어를 실행하여 내장 서버를 켭니다:
   ```bash
   php -S 127.0.0.1:8000
   ```
3. 웹 브라우저에서 **[http://127.0.0.1:8000](http://127.0.0.1:8000)** 에 접속합니다.

---

## 📁 Submission Criteria Checklist

### 1. Subject: AI Web Application development
- [x] LLM (Claude/GPT) integrated via API/Mocking logic.
- [x] Real-time AI processing for reviews.

### 2. Essential Items
- [x] **Homepage Concept**: Security Monitoring Center theme.
- [x] **Login Function**: Secure Register/Login system.
- [x] **Board Function**: Full CRUD (Create, Read, Update, Delete) for movie reviews and comments.

### 3. Environment (VMware Ready)
- **Language**: PHP 8.x
- **Server**: Compatible with Windows Server (IIS / Apache / PHP Built-in).
- **Database**: Dual Compatibility (SQLite for demo, MySQL for production).

### 4. Bonus Features (Extra Points)
- [x] **File Upload/Download**: Secure poster uploads and profile picture management.
- [x] **Network Security**: CSRF protection, SQL Injection prevention, and XSS sanitization (OWASP Top 10 Harden).
- [x] **Account Management**: ID and Password recovery system implemented.
- [x] **Role Management**: Dedicated Admin and User roles with different permission levels.

---
**Developed by Antigravity AI Assistant & Student**

# API Documentation - Ujian Online

Base URL: `/api`

## Authentication

### Login
```
POST /api/login
Content-Type: application/json

{
    "email": "admin@admin.com",
    "password": "password"
}

Response:
{
    "message": "Login berhasil",
    "user": {...},
    "token": "1|abc123..."
}
```

### Logout
```
POST /api/logout
Authorization: Bearer {token}

Response:
{
    "message": "Logout berhasil"
}
```

### Get Current User
```
GET /api/me
Authorization: Bearer {token}

Response:
{
    "id": 1,
    "name": "Administrator",
    "email": "admin@admin.com",
    "role": "admin"
}
```

---

## Students

### List Students
```
GET /api/students
Authorization: Bearer {token}

Query Parameters:
- classroom_id (optional): Filter by classroom
- search (optional): Search by name
- per_page (optional): Items per page (default: 15)

Response:
{
    "data": [...],
    "current_page": 1,
    "total": 100
}
```

### Get Student
```
GET /api/students/{id}
Authorization: Bearer {token}
```

### Create Student
```
POST /api/students
Authorization: Bearer {token}
Content-Type: application/json

{
    "nisn": "1234567890",
    "name": "John Doe",
    "classroom_id": 1,
    "password": "secret123",
    "gender": "L"
}
```

### Update Student
```
PUT /api/students/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "nisn": "1234567890",
    "name": "John Doe Updated",
    "classroom_id": 1,
    "gender": "L",
    "password": "newpassword" // optional
}
```

### Delete Student
```
DELETE /api/students/{id}
Authorization: Bearer {token}
```

---

## Exams

### List Exams
```
GET /api/exams
Authorization: Bearer {token}

Query Parameters:
- lesson_id (optional): Filter by lesson
- classroom_id (optional): Filter by classroom
- per_page (optional): Items per page
```

### Get Exam Detail
```
GET /api/exams/{id}
Authorization: Bearer {token}

Response includes: lesson, classroom, questions
```

### List Exam Sessions
```
GET /api/exam-sessions
Authorization: Bearer {token}

Query Parameters:
- active (optional): Set to 1 for active sessions only
```

---

## Grades

### List Grades
```
GET /api/grades
Authorization: Bearer {token}

Query Parameters:
- exam_id (optional): Filter by exam
- student_id (optional): Filter by student
- status (optional): passed/failed/pending
- per_page (optional): Items per page
```

### Get Grade Detail
```
GET /api/grades/{id}
Authorization: Bearer {token}
```

### Get Statistics
```
GET /api/grades-statistics
Authorization: Bearer {token}

Query Parameters:
- exam_id (optional): Statistics for specific exam

Response:
{
    "total": 100,
    "average": 75.5,
    "highest": 100,
    "lowest": 30,
    "passed": 80,
    "failed": 20
}
```

---

## Error Responses

### 401 Unauthorized
```json
{
    "message": "Unauthenticated."
}
```

### 422 Validation Error
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

### 404 Not Found
```json
{
    "message": "No query results for model..."
}
```

---

## Example Usage (cURL)

```bash
# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@admin.com","password":"password"}'

# Get students with token
curl http://localhost:8000/api/students \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Get exam statistics
curl http://localhost:8000/api/grades-statistics \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

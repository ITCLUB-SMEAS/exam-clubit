// K6 Test Configuration
export const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
export const API_URL = `${BASE_URL}/api/v1`;

// Test credentials
export const ADMIN_CREDENTIALS = {
    email: 'admin@admin.com',
    password: 'password',
};

export const STUDENT_CREDENTIALS = {
    email: __ENV.STUDENT_EMAIL || 'student@test.com',
    password: __ENV.STUDENT_PASSWORD || 'password',
};

// Thresholds
export const THRESHOLDS = {
    http_req_duration: ['p(95)<500', 'p(99)<1000'],
    http_req_failed: ['rate<0.01'],
};

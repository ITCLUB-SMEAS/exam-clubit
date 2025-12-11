import http from 'k6/http';
import { check, sleep } from 'k6';
import { API_URL, ADMIN_CREDENTIALS, THRESHOLDS } from './config.js';

export const options = {
    stages: [
        { duration: '30s', target: 10 },  // ramp up
        { duration: '1m', target: 10 },   // steady
        { duration: '30s', target: 0 },   // ramp down
    ],
    thresholds: THRESHOLDS,
};

export default function () {
    // Login
    const loginRes = http.post(`${API_URL}/login`, JSON.stringify(ADMIN_CREDENTIALS), {
        headers: { 'Content-Type': 'application/json' },
    });

    check(loginRes, {
        'login status 200': (r) => r.status === 200,
        'has token': (r) => r.json('token') !== undefined,
    });

    if (loginRes.status === 200) {
        const token = loginRes.json('token');
        const authHeaders = {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
        };

        // Get current user
        const meRes = http.get(`${API_URL}/me`, { headers: authHeaders });
        check(meRes, { 'me status 200': (r) => r.status === 200 });

        // Logout
        const logoutRes = http.post(`${API_URL}/logout`, null, { headers: authHeaders });
        check(logoutRes, { 'logout status 200': (r) => r.status === 200 });
    }

    sleep(1);
}

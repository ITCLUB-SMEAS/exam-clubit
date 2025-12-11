import http from 'k6/http';
import { check, sleep } from 'k6';
import { API_URL, ADMIN_CREDENTIALS } from './config.js';

// Stress test - find breaking point
export const options = {
    stages: [
        { duration: '2m', target: 50 },   // ramp up to 50 users
        { duration: '3m', target: 50 },   // stay at 50
        { duration: '2m', target: 100 },  // ramp up to 100
        { duration: '3m', target: 100 },  // stay at 100
        { duration: '2m', target: 150 },  // ramp up to 150
        { duration: '3m', target: 150 },  // stay at 150
        { duration: '5m', target: 0 },    // ramp down
    ],
    thresholds: {
        http_req_duration: ['p(95)<2000'],
        http_req_failed: ['rate<0.1'],
    },
};

export function setup() {
    const res = http.post(`${API_URL}/login`, JSON.stringify(ADMIN_CREDENTIALS), {
        headers: { 'Content-Type': 'application/json' },
    });
    return { token: res.json('token') };
}

export default function (data) {
    const headers = {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${data.token}`,
    };

    // Mix of endpoints
    const endpoints = [
        `${API_URL}/exams`,
        `${API_URL}/students`,
        `${API_URL}/grades`,
        `${API_URL}/exam-sessions`,
    ];

    const endpoint = endpoints[Math.floor(Math.random() * endpoints.length)];
    const res = http.get(endpoint, { headers });

    check(res, {
        'status is 200': (r) => r.status === 200,
        'response time < 2s': (r) => r.timings.duration < 2000,
    });

    sleep(Math.random() * 2);
}

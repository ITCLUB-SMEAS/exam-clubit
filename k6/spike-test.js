import http from 'k6/http';
import { check, sleep } from 'k6';
import { API_URL, ADMIN_CREDENTIALS } from './config.js';

// Spike test - sudden traffic surge (simulasi ujian dimulai)
export const options = {
    stages: [
        { duration: '10s', target: 5 },    // warm up
        { duration: '1m', target: 5 },     // normal load
        { duration: '10s', target: 200 },  // SPIKE! (ujian dimulai)
        { duration: '3m', target: 200 },   // stay at spike
        { duration: '10s', target: 5 },    // scale down
        { duration: '1m', target: 5 },     // recovery
        { duration: '10s', target: 0 },    // ramp down
    ],
    thresholds: {
        http_req_duration: ['p(95)<3000'],
        http_req_failed: ['rate<0.15'],
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

    // Simulate exam start - heavy read
    const examsRes = http.get(`${API_URL}/exams`, { headers });
    check(examsRes, { 'exams ok': (r) => r.status === 200 });

    const sessionsRes = http.get(`${API_URL}/exam-sessions`, { headers });
    check(sessionsRes, { 'sessions ok': (r) => r.status === 200 });

    sleep(0.5);
}

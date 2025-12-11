import http from 'k6/http';
import { check, sleep } from 'k6';
import { API_URL, ADMIN_CREDENTIALS, THRESHOLDS } from './config.js';

export const options = {
    stages: [
        { duration: '30s', target: 15 },
        { duration: '1m', target: 15 },
        { duration: '30s', target: 0 },
    ],
    thresholds: THRESHOLDS,
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

    // List grades
    const gradesRes = http.get(`${API_URL}/grades`, { headers });
    check(gradesRes, {
        'grades status 200': (r) => r.status === 200,
    });

    // Get statistics
    const statsRes = http.get(`${API_URL}/grades-statistics`, { headers });
    check(statsRes, {
        'statistics status 200': (r) => r.status === 200,
    });

    sleep(1);
}

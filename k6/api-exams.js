import http from 'k6/http';
import { check, sleep } from 'k6';
import { API_URL, ADMIN_CREDENTIALS, THRESHOLDS } from './config.js';

export const options = {
    stages: [
        { duration: '30s', target: 20 },
        { duration: '1m', target: 20 },
        { duration: '30s', target: 0 },
    ],
    thresholds: THRESHOLDS,
};

let token = null;

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

    // List exams
    const examsRes = http.get(`${API_URL}/exams`, { headers });
    check(examsRes, {
        'exams status 200': (r) => r.status === 200,
        'exams has data': (r) => r.json('data') !== undefined,
    });

    // Get exam sessions
    const sessionsRes = http.get(`${API_URL}/exam-sessions`, { headers });
    check(sessionsRes, {
        'sessions status 200': (r) => r.status === 200,
    });

    // Get single exam if exists
    const exams = examsRes.json('data');
    if (exams && exams.length > 0) {
        const examRes = http.get(`${API_URL}/exams/${exams[0].id}`, { headers });
        check(examRes, {
            'single exam status 200': (r) => r.status === 200,
        });
    }

    sleep(1);
}

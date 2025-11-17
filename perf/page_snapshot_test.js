import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: 20,
  duration: '5m',
  thresholds: {
    http_req_duration: ['p(95)<250'],
    http_req_failed: ['rate<0.01']
  }
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost';
const SNAPSHOT_URL = `${BASE_URL}/api/page.php?action=get_snapshot`;

export default function () {
  const response = http.get(SNAPSHOT_URL);
  check(response, {
    'status is 200': (r) => r.status === 200
  });
  sleep(1);
}

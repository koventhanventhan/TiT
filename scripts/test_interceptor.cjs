const assert = require('assert');

// Logic extracted from apiClient.js
function isBlockedPayload(status, data) {
  if (status !== 402 && status !== 403) return false;
  return Boolean(
    data && (data.code === 'institute_expired' || data.code === 'institute_suspended')
  );
}

// Test cases
console.log('Running interceptor unit tests...');

// 1. 402 with institute_expired -> true
assert.strictEqual(
  isBlockedPayload(402, { code: 'institute_expired', message: 'Subscription has ended' }),
  true,
  '402 with institute_expired must trigger notice'
);

// 2. 403 with institute_suspended -> true
assert.strictEqual(
  isBlockedPayload(403, { code: 'institute_suspended', message: 'Institute access is suspended' }),
  true,
  '403 with institute_suspended must trigger notice'
);

// 3. 403 plain (context mismatch) -> false
assert.strictEqual(
  isBlockedPayload(403, { message: 'Institute context mismatch' }),
  false,
  '403 without blocked code must NOT trigger notice'
);

// 4. 403 forbidden / role error -> false
assert.strictEqual(
  isBlockedPayload(403, { code: 'forbidden', message: 'Forbidden' }),
  false,
  '403 forbidden must NOT trigger notice'
);

// 5. 401 unauthenticated -> false
assert.strictEqual(
  isBlockedPayload(401, { message: 'Unauthenticated.' }),
  false,
  '401 must NOT trigger notice'
);

// 6. 404 not found -> false
assert.strictEqual(
  isBlockedPayload(404, { code: 'tenant_not_found', message: 'Unknown institute' }),
  false,
  '404 tenant_not_found must NOT trigger blocked notice'
);

// 7. 200 ok -> false
assert.strictEqual(
  isBlockedPayload(200, { institute_name: 'Test' }),
  false,
  '200 must NOT trigger notice'
);

console.log('ALL 7 INTERCEPTOR UNIT TESTS PASSED!');

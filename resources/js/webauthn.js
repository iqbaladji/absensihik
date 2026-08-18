import axios from 'axios';
import { startRegistration, startAuthentication, browserSupportsWebAuthn } from '@simplewebauthn/browser';

// Dedicated axios instance that sends session cookies for WebAuthn challenges.
const wa = axios.create({
    baseURL: '/api',
    headers: { Accept: 'application/json' },
    withCredentials: true,
    xsrfCookieName: 'XSRF-TOKEN',
    xsrfHeaderName: 'X-XSRF-TOKEN',
});

wa.interceptors.request.use((config) => {
    const token = localStorage.getItem('absensihik_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

export function isBiometricSupported() {
    return browserSupportsWebAuthn();
}

async function ensureCsrfCookie() {
    // Laravel Sanctum SPA endpoint issues XSRF cookie; harmless if unused.
    try { await axios.get('/sanctum/csrf-cookie', { withCredentials: true }); } catch (_) {}
}

// Register a new credential (user must be logged in with Bearer token).
export async function registerBiometric() {
    if (!browserSupportsWebAuthn()) throw new Error('Perangkat/ browser tidak mendukung biometrik.');
    await ensureCsrfCookie();

    const { data: options } = await wa.post('/webauthn/register/options');
    // simplewebauthn expects the PublicKeyCredentialCreationOptionsJSON format.
    const attResp = await startRegistration({ optionsJSON: options });
    const { data } = await wa.post('/webauthn/register', attResp);
    return data;
}

// Login with a previously-registered credential.
export async function loginBiometric(username) {
    if (!browserSupportsWebAuthn()) throw new Error('Perangkat/ browser tidak mendukung biometrik.');
    await ensureCsrfCookie();

    const { data: options } = await wa.post('/webauthn/login/options', { username });
    const asseResp = await startAuthentication({ optionsJSON: options });
    const { data } = await wa.post('/webauthn/login', asseResp);
    return data;
}

export async function listCredentials() {
    const { data } = await wa.get('/webauthn/credentials');
    return data.data || [];
}

export async function deleteCredential(id) {
    const { data } = await wa.delete(`/webauthn/credentials/${id}`);
    return data;
}

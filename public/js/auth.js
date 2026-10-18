

document.addEventListener('DOMContentLoaded', () => {
    const protectedNav = document.getElementById('protected-nav');
    const currentUser = document.getElementById('current-user');
    const logoutBtn = document.getElementById('logout-btn');

    const authenticateBtn = document.getElementById('authenticateBtn');
    const authModal = document.getElementById('auth-modal');
    const authStatus = document.getElementById('auth-status');

    function showStatus(message, type) {
        if (!authStatus) {
            return;
        }

        authStatus.textContent = message;
        authStatus.className = `auth-status ${type}`;
        authStatus.hidden = false;
    }

    function hideModal() {
        if (!authModal) {
            return;
        }

        authModal.classList.remove('show');
        authModal.hidden = true;

        otpEmail.value = '';
        otpCode.value = '';

        otpVerifyForm.hidden = true;
        otpRequestForm.reset();
        otpVerifyForm.reset();

        setOtpMessage('');

        setOtpLoading(
            otpRequestBtn,
            false,
            'Send verification code'
        );

        setOtpLoading(
            otpVerifyBtn,
            false,
            'Verify and sign in'
        );
    }

    async function requestMe() {
        return fetch('/api/me', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
            },
        });
    }

    async function getAuthState() {
        let response = await requestMe();

        if (response.ok) {
            const data = await response.json();

            if (data.authenticated) {
                return data;
            }
        } else if (response.status !== 401) {
            throw new Error(`Auth status request failed: ${response.status}`);
        }

        const refreshResponse = await fetch('/api/refresh', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
            },
        });

        if (!refreshResponse.ok) {
            return { authenticated: false };
        }

        // refresh new cookie.
        response = await requestMe();

        if (!response.ok) {
            return { authenticated: false };
        }

        return response.json();
    }

    async function updateAuthUI() {
        const oauthAttempted =
            sessionStorage.getItem('oauth_attempted') === '1';
        const otpLoginAttempted =
            sessionStorage.getItem('otp_login_success') === '1';
        const passkeyLoginAttempted =
            sessionStorage.getItem('passkey_login_attempted') === '1';

        try {
            const data = await getAuthState();
            const authenticated = data.authenticated === true;

            if (protectedNav) {
                protectedNav.hidden = !authenticated;
            }

            if (currentUser) {
                currentUser.hidden = !authenticated;
                currentUser.textContent = authenticated
                    ? data.name
                    : '';
            }

            const passkeyMenuBtn = document.getElementById('passkey-menu-btn');

            passkeyMenuBtn?.addEventListener('click', openPasskeyMenu);

            document.getElementById('close-passkey-btn')
                ?.addEventListener('click', closePasskeyMenu);

            document.getElementById('create-passkey-btn')
                ?.addEventListener('click', createPasskeyFromMenu);

            if (passkeyMenuBtn) {
                passkeyMenuBtn.hidden = !authenticated;
            }

            if (logoutBtn) {
                logoutBtn.hidden = !authenticated;
            }

            if (authenticateBtn) {
                authenticateBtn.hidden = authenticated;
            }

            if (oauthAttempted || otpLoginAttempted || passkeyLoginAttempted) {
                if (authenticated) {
                    showStatus(
                        `You have successfully signed in${data.name ? `, ${data.name}` : ''}.`,
                        'success'
                    );
                } else {
                    showStatus(
                        'Sign-in failed. Please try again.',
                        'failure'
                    );
                }

                sessionStorage.removeItem('oauth_attempted');
                sessionStorage.removeItem('otp_login_success');
                sessionStorage.removeItem('passkey_login_attempted');
            }
        } catch (error) {
            console.error('Unable to check authentication:', error);

            if (protectedNav) {
                protectedNav.hidden = true;
            }

            if (currentUser) {
                currentUser.hidden = true;
                currentUser.textContent = '';
            }

            if (logoutBtn) {
                logoutBtn.hidden = true;
            }

            if (authenticateBtn) {
                authenticateBtn.hidden = false;
            }

            if (oauthAttempted || otpLoginAttempted || passkeyLoginAttempted) {
                showStatus(
                    'Unable to verify sign-in. Please try again.',
                    'failure'
                );

                sessionStorage.removeItem('oauth_attempted');
                sessionStorage.removeItem('otp_login_success');
                sessionStorage.removeItem('passkey_login_attempted');
            }
        }
    }

    function openModal() {
        if (!authModal) {
            return;
        }

        authModal.hidden = false;
        authModal.classList.add('show');
    }

    if (authenticateBtn) {
        authenticateBtn.addEventListener('click', openModal);
    }

    document.getElementById('auth-cancel')?.addEventListener('click', hideModal);

    document.getElementById('google-login')?.addEventListener('click', () => {
        sessionStorage.setItem('oauth_attempted', '1');
        window.location.href = '/api/connect/google';
    });

    document.getElementById('github-login')?.addEventListener('click', () => {
        sessionStorage.setItem('oauth_attempted', '1');
        window.location.href = '/api/connect/github';
    });

    document.getElementById('passkey-login')?.addEventListener('click', loginWithPasskey);

    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            logoutBtn.disabled = true;

            try {
                await fetch('/api/logout', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                    },
                });
            } catch (error) {
                console.error('Logout request failed:', error);
            }

            window.location.href = '/';
        });
    }

    updateAuthUI();
});

//**************************OTP************************/

const otpRequestForm = document.getElementById('otp-request-form');
const otpVerifyForm = document.getElementById('otp-verify-form');
const otpEmail = document.getElementById('otp-email');
const otpCode = document.getElementById('otp-code');
const otpMessage = document.getElementById('otp-message');
const otpRequestBtn = document.getElementById('otp-request-btn');
const otpVerifyBtn = document.getElementById('otp-verify-btn');

function setOtpMessage(message, type = 'error') {
    otpMessage.textContent = message;
    otpMessage.className = `otp-message ${type}`;
}

function setOtpLoading(button, loading, originalText) {
    if (!button) {
        return;
    }

    button.disabled = loading;
    button.textContent = loading ? 'Please wait...' : originalText;
}

// Request OTP code.
if (otpRequestForm) {
    otpRequestForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const email = otpEmail.value.trim();

        setOtpMessage('');

        if (!email || !otpEmail.checkValidity()) {
            otpEmail.reportValidity();
            return;
        }

        setOtpLoading(
            otpRequestBtn,
            true,
            'Send verification code'
        );

        try {
            const response = await fetch('/api/iam/otp', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ email }),
            });

            if (response.status === 404) {
                setOtpMessage(
                    'No account found with this email address.',
                    'error'
                );
                otpVerifyForm.hidden = true;
                otpCode.value = '';
                return;
            }

            if (!response.ok) {
                setOtpMessage(
                    `Unable to send verification code (${response.status}). Please try again.`,
                    'error'
                );
                return;
            }

            otpVerifyForm.hidden = false;
            otpCode.value = '';
            otpCode.focus();

            setOtpMessage(
                'Verification code sent. It is valid for 5 minutes.',
                'success'
            );
        } catch (error) {
            console.error('OTP request failed:', error);

            setOtpMessage(
                'Network error. Please try again.',
                'error'
            );
        } finally {
            setOtpLoading(
                otpRequestBtn,
                false,
                'Send verification code'
            );
        }
    });
}

// Verify OTP code and sign in.
if (otpVerifyForm) {
    otpVerifyForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const email = otpEmail.value.trim();
        const otp = otpCode.value.trim();

        setOtpMessage('');

        if (!email || !otpEmail.checkValidity()) {
            setOtpMessage('Enter a valid email address.', 'error');
            otpEmail.focus();
            return;
        }

        if (!/^\d{6}$/.test(otp)) {
            setOtpMessage(
                'Enter the 6-digit verification code.',
                'error'
            );
            otpCode.focus();
            return;
        }

        setOtpLoading(
            otpVerifyBtn,
            true,
            'Verify and sign in'
        );

        try {
            const response = await fetch('/api/iam/otp-verification', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ email, otp }),
            });

            if (response.status === 401) {
                otpCode.value = '';
                otpVerifyForm.hidden = true;

                setOtpMessage(
                    'Invalid or expired verification code. Request a new code.',
                    'error'
                );

                return;
            }

            if (!response.ok) {
                setOtpMessage(
                    `Verification failed (${response.status}). Please try again.`,
                    'error'
                );

                return;
            }

            // Authentication succeeded; the server has set the cookies.
            sessionStorage.setItem('otp_login_success', '1');
            sessionStorage.removeItem('oauth_attempted');

            window.location.href = '/';
        } catch (error) {
            console.error('OTP verification failed:', error);

            setOtpMessage(
                'Network error. Please try again.',
                'error'
            );
        } finally {
            setOtpLoading(
                otpVerifyBtn,
                false,
                'Verify and sign in'
            );
        }
    });
}

//**************************PASSKEY************************/

async function loginWithPasskey() {
    const authStatus = document.getElementById('auth-status');

    if (authStatus) {
        authStatus.hidden = true;
    }

    try {
        const response = await fetch(
            '/api/iam/passkey/authentication/options',
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                },
            }
        );

        const options = await response.json();

        if (!response.ok) {
            showPasskeyError();
            return;
        }

        const credential = await navigator.credentials.get({
            publicKey: decodeAuthenticationOptions(options),
        });

        if (!credential) {
            showPasskeyError();
            return;
        }

        const verifyResponse = await fetch(
            '/api/iam/passkey/authentication/verify',
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    credential: encodeCredential(credential),
                }),
            }
        );

        if (!verifyResponse.ok) {
            showPasskeyError();
            return;
        }

        sessionStorage.setItem('passkey_login_attempted', '1');
        sessionStorage.removeItem('oauth_attempted');
        sessionStorage.removeItem('otp_login_success');

        window.location.href = '/';
    } catch (error) {
        console.error('Passkey authentication failed:', error);

        showPasskeyError();
    }
}

function showPasskeyError() {
    const authStatus = document.getElementById('auth-status');

    if (!authStatus) {
        return;
    }

    authStatus.textContent = 'Sign-in failed. Please try again.';
    authStatus.className = 'auth-status failure';
    authStatus.hidden = false;
}

function decodeAuthenticationOptions(options) {
    options.challenge = base64urlToUint8Array(options.challenge);

    if (options.allowCredentials) {
        options.allowCredentials = options.allowCredentials.map(credential => ({
            ...credential,
            id: base64urlToUint8Array(credential.id),
        }));
    }

    return options;
}

function encodeCredential(credential) {
    return {
        id: credential.id,
        rawId: uint8ArrayToBase64url(
            new Uint8Array(credential.rawId)
        ),
        type: credential.type,
        response: {
            clientDataJSON: uint8ArrayToBase64url(
                new Uint8Array(credential.response.clientDataJSON)
            ),
            authenticatorData: uint8ArrayToBase64url(
                new Uint8Array(credential.response.authenticatorData)
            ),
            signature: uint8ArrayToBase64url(
                new Uint8Array(credential.response.signature)
            ),
            userHandle: credential.response.userHandle
                ? uint8ArrayToBase64url(
                    new Uint8Array(credential.response.userHandle)
                )
                : null,
        },
    };
}

function base64urlToUint8Array(base64url) {
    const padding = '='.repeat((4 - base64url.length % 4) % 4);
    const base64 = (base64url + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');

    return Uint8Array.from(
        atob(base64),
        character => character.charCodeAt(0)
    );
}

function uint8ArrayToBase64url(bytes) {
    let binary = '';

    bytes.forEach(byte => {
        binary += String.fromCharCode(byte);
    });

    return btoa(binary)
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=/g, '');
}


function showPasskeyMessage(message, type = 'error') {
    const authStatus = document.getElementById('auth-status');
    const globalAlert = document.getElementById('globalAlert');
    const target = authStatus || globalAlert;

    if (!target) {
        console.error(message);
        return;
    }

    target.textContent = message;
    target.hidden = false;

    if (target.id === 'globalAlert') {
        target.className = type === 'success'
            ? 'alert alert-success'
            : 'alert alert-danger';
    } else {
        target.className = type === 'success'
            ? 'auth-status success'
            : 'auth-status failure';
    }
}

function clearPasskeyMessage() {
    for (const id of ['auth-status', 'globalAlert']) {
        const element = document.getElementById(id);

        if (!element) {
            continue;
        }

        element.textContent = '';
        element.hidden = true;

        if (id === 'globalAlert') {
            element.className = 'alert alert-danger hidden';
        } else {
            element.className = 'auth-status';
        }
    }
}

async function readPasskeyResponse(response) {
    try {
        return await response.json();
    } catch {
        return {};
    }
}

async function openPasskeyMenu() {
    const modal = document.getElementById('passkey-modal');
    const list = document.getElementById('passkey-list');

    if (!modal || !list) {
        showPasskeyMessage('Passkey menu is unavailable on this page.');
        return;
    }

    clearPasskeyMessage();

    modal.hidden = false;
    modal.classList.add('show');

    list.textContent = 'Loading...';

    try {
        const response = await fetch('/api/iam/passkeys', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        });

        const data = await readPasskeyResponse(response);

        if (!response.ok || !Array.isArray(data)) {
            list.replaceChildren();
            showPasskeyMessage(
                data.error || 'Failed to load Passkeys.'
            );
            return;
        }

        list.replaceChildren();

        if (data.length === 0) {
            const emptyMessage = document.createElement('div');
            emptyMessage.className = 'passkey-empty-message';
            emptyMessage.textContent = 'No Passkeys registered.';
            list.appendChild(emptyMessage);
            return;
        }

        for (const passkey of data) {
            const item = document.createElement('div');
            item.className = 'passkey-item';

            const info = document.createElement('div');
            info.className = 'passkey-info';

            const name = document.createElement('div');
            name.className = 'passkey-name';
            name.textContent = passkey.name || 'Passkey';

            const date = document.createElement('div');
            date.className = 'passkey-date';

            const createdAt = new Date(passkey.createdAt);
            date.textContent = `Added: ${
                Number.isNaN(createdAt.getTime())
                    ? 'Unknown'
                    : createdAt.toLocaleDateString()
            }`;

            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'passkey-delete';
            deleteButton.textContent = 'Delete';

            deleteButton.addEventListener('click', async () => {
                await deletePasskey(passkey.id);
            });

            info.append(name, date);
            item.append(info, deleteButton);
            list.appendChild(item);
        }
    } catch (error) {
        console.error('Failed to load Passkeys:', error);
        list.replaceChildren();
        showPasskeyMessage('Failed to load Passkeys. Please try again.');
    }
}

function closePasskeyMenu() {
    const modal = document.getElementById('passkey-modal');

    if (modal) {
        modal.classList.remove('show');
        modal.hidden = true;
    }
}

async function createPasskeyFromMenu() {
    closePasskeyMenu();
    await createPasskey();
}

async function createPasskey() {
    clearPasskeyMessage();

    try {
        const optionsResponse = await fetch(
            '/api/iam/passkey/registration/options',
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: { Accept: 'application/json' }
            }
        );

        const options = await readPasskeyResponse(optionsResponse);

        if (!optionsResponse.ok) {
            showPasskeyMessage(
                options.error || 'Failed to start Passkey registration.'
            );
            return;
        }

        options.challenge = base64urlToUint8Array(options.challenge);
        options.user.id = base64urlToUint8Array(options.user.id);

        if (options.excludeCredentials) {
            options.excludeCredentials = options.excludeCredentials.map(
                credential => ({
                    ...credential,
                    id: base64urlToUint8Array(credential.id)
                })
            );
        }

        const credential = await navigator.credentials.create({
            publicKey: options
        });

        if (!credential) {
            showPasskeyMessage('Passkey registration was cancelled.');
            return;
        }

        const credentialData = {
            id: credential.id,
            rawId: uint8ArrayToBase64url(
                new Uint8Array(credential.rawId)
            ),
            type: credential.type,
            response: {
                clientDataJSON: uint8ArrayToBase64url(
                    new Uint8Array(credential.response.clientDataJSON)
                ),
                attestationObject: uint8ArrayToBase64url(
                    new Uint8Array(credential.response.attestationObject)
                )
            }
        };

        const verifyResponse = await fetch(
            '/api/iam/passkey/registration/verify',
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                body: JSON.stringify({
                    credential: credentialData
                })
            }
        );

        const result = await readPasskeyResponse(verifyResponse);

        if (!verifyResponse.ok) {
            showPasskeyMessage(
                result.error || 'Passkey registration failed.'
            );
            return;
        }

        showPasskeyMessage(
            'Passkey created successfully.',
            'success'
        );

        await openPasskeyMenu();
    } catch (error) {
        console.error('Passkey registration failed:', error);

        showPasskeyMessage(
            error.name === 'NotAllowedError'
                ? 'Passkey registration was cancelled.'
                : 'Passkey registration failed. Please try again.'
        );
    }
}

async function deletePasskey(id) {
    clearPasskeyMessage();

    try {
        const response = await fetch(
            `/api/iam/passkeys/${encodeURIComponent(id)}`,
            {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: { Accept: 'application/json' }
            }
        );

        const data = await readPasskeyResponse(response);

        if (!response.ok) {
            showPasskeyMessage(
                data.error || 'Failed to delete Passkey.'
            );
            return;
        }

        await openPasskeyMenu();
        showPasskeyMessage(
            'Passkey deleted successfully.',
            'success'
        );
    } catch (error) {
        console.error('Passkey deletion failed:', error);
        showPasskeyMessage(
            'Failed to delete Passkey. Please try again.'
        );
    }
}

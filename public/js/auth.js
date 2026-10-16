

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
    }

    async function requestMe() {
        return fetch('/me', {
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

        const refreshResponse = await fetch('/refresh', {
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

            if (logoutBtn) {
                logoutBtn.hidden = !authenticated;
            }

            if (authenticateBtn) {
                authenticateBtn.hidden = authenticated;
            }

            if (oauthAttempted) {
                if (authenticated) {
                    showStatus(
                        `You have successfully signed in${data.name ? `, ${data.name}` : ''}.`,
                        'success'
                    );
                } else {
                    showStatus(
                        'Sign-in failed. Your account may not be registered in the system.',
                        'failure'
                    );
                }

                sessionStorage.removeItem('oauth_attempted');
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

            if (oauthAttempted) {
                showStatus(
                    'Unable to verify sign-in. Please try again.',
                    'failure'
                );

                sessionStorage.removeItem('oauth_attempted');
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

    authModal?.addEventListener('click', (event) => {
        if (event.target === authModal) {
            hideModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && authModal?.classList.contains('show')) {
            hideModal();
        }
    });

    document.getElementById('google-login')?.addEventListener('click', () => {
        sessionStorage.setItem('oauth_attempted', '1');
        window.location.href = '/api/connect/google';
    });

    document.getElementById('github-login')?.addEventListener('click', () => {
        sessionStorage.setItem('oauth_attempted', '1');
        window.location.href = '/api/connect/github';
    });

    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            logoutBtn.disabled = true;

            try {
                await fetch('/logout', {
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

function setOtpMessage(message) {
    document.getElementById('otp-message').textContent = message;
}

function setOtpLoading(button, loading, originalText) {
    if (!button) return;

    button.disabled = loading;
    button.textContent = loading ? 'Please wait...' : originalText;
}

if (otpRequestForm) {
    otpRequestForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const email = otpEmail.value.trim();

        setOtpMessage('');

        if (!otpEmail.checkValidity() || !email) {
            otpEmail.reportValidity();
            return;
        }

        setOtpLoading(
            otpRequestBtn,
            true,
            'Send verification code'
        );

        try {
            const response = await fetch('/iam/otp', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ email }),
            });

            if (response.status === 200) {
                otpVerifyForm.hidden = false;
                otpCode.value = '';
                otpCode.focus();

                setOtpMessage(
                    'Verification code sent. It is valid for 5 minutes.',
                    'success'
                );

                return;
            }

            if (response.status === 400) {
                setOtpMessage('Please enter a valid email address.', 'error');
                return;
            }

            if (response.status === 404) {
                setOtpMessage('User not found.', 'error');
                return;
            }

            setOtpMessage(
                `Unable to send verification code (${response.status}).`,
                'error'
            );
        } catch (error) {
            console.error('OTP request failed:', error);
            setOtpMessage('Network error. Please try again.', 'error');
        } finally {
            setOtpLoading(
                otpRequestBtn,
                false,
                'Send verification code'
            );
        }
    });
}

if (otpVerifyForm) {
    otpVerifyForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const email = otpEmail.value.trim();
        const otp = otpCode.value.trim();

        setOtpMessage('');

        if (!/^\d{6}$/.test(otp)) {
            setOtpMessage('Enter the 6-digit verification code.', 'error');
            otpCode.focus();
            return;
        }

        setOtpLoading(otpVerifyBtn, true, 'Verify and sign in');

        try {
            const response = await fetch('/iam/otp-verification', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ email, otp }),
            });

            if (!response.ok) {
                if (response.status === 401) {
                    otpCode.value = '';
                    otpVerifyForm.hidden = true;

                    setOtpMessage(
                        'Invalid or expired verification code. Request a new code.',
                        'error'
                    );

                    return;
                }

                setOtpMessage(
                    `Verification failed (${response.status}).`,
                    'error'
                );

                return;
            }

            // check auth by JWT-cookie.
            const meResponse = await requestMe();

            if (meResponse.ok) {
                const user = await meResponse.json();

                if (user.authenticated === true) {
                    sessionStorage.removeItem('oauth_attempted');
                    window.location.href = '/';
                    return;
                }
            }

            setOtpMessage(
                'The code could not be verified. Please try again.',
                'error'
            );
        } catch (error) {
            console.error('OTP verification failed:', error);
            setOtpMessage('Network error. Please try again.', 'error');
        } finally {
            setOtpLoading(otpVerifyBtn, false, 'Verify and sign in');
        }
    });
}

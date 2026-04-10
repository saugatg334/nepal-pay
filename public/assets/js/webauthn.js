// Nepal Pay WebAuthn Client Library
// Enhanced biometric integration for dashboard/profile

class NepalPayWebAuthn {
    constructor(baseUrl = '/') {
        this.baseUrl = baseUrl;
    }

    async registerDevice(deviceName = 'Device') {
        try {
            const response = await fetch(`${this.baseUrl}biometric-register.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=register'
            });
            
            const options = await response.json();
            if (!options.publicKey) throw new Error('Failed to get registration options');
            
            const credential = await SimpleWebAuthnBrowser.register(options.publicKey);
            
            const verifyResponse = await fetch(`${this.baseUrl}biometric-register.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_id: credential.rawId, // Simplified
                    id: credential.id,
                    rawId: credential.rawId,
                    response: credential.response,
                    type: credential.type,
                    device_name: deviceName
                })
            });
            
            const result = await verifyResponse.json();
            return result;
        } catch (error) {
            console.error('Registration failed:', error);
            throw error;
        }
    }

    async authenticate() {
        try {
            // Get phone or use current session
            const phone = prompt('Enter your phone number for biometric login:');
            if (!phone) throw new Error('Phone required');
            
            const response = await fetch(`${this.baseUrl}biometric-login.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `phone=${phone}`
            });
            
            const options = await response.json();
            if (options.error) throw new Error(options.error);
            
            const assertion = await SimpleWebAuthnBrowser.authenticate(options.publicKey);
            
            const verifyResponse = await fetch(`${this.baseUrl}biometric-login.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'verify',
                    id: assertion.id,
                    rawId: assertion.rawId,
                    response: assertion.response,
                    type: assertion.type
                })
            });
            
            const result = await verifyResponse.json();
            return result;
        } catch (error) {
            console.error('Authentication failed:', error);
            throw error;
        }
    }

    // Check biometric support
    static isSupported() {
        return 'credentials' in navigator && 
               window.PublicKeyCredential && 
               PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable;
    }

    // Check if user has registered credentials
    async hasCredentials() {
        // Simplified - check session or make API call
        return true;
    }
}

// Global instance
window.NepalPayBiometrics = new NepalPayWebAuthn();

// Auto-init if on biometric pages
if (document.querySelector('[data-biometric]')) {
    document.querySelectorAll('[data-biometric="register"]').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                const deviceName = prompt('Device name (e.g., iPhone 14)') || 'Device';
                await window.NepalPayBiometrics.registerDevice(deviceName);
                location.reload();
            } catch (err) {
                alert('Registration failed: ' + err.message);
            }
        });
    });

    document.querySelectorAll('[data-biometric="login"]').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                await window.NepalPayBiometrics.authenticate();
            } catch (err) {
                alert('Login failed: ' + err.message);
            }
        });
    });
}


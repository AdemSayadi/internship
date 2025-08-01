export const handleGitHubAuth = async (isLogin = true) => {
    try {
        // 1. Get GitHub OAuth URL from backend
        const response = await fetch('http://localhost:8000/api/auth/github');
        const { url } = await response.json();

        // 2. Open a popup window for GitHub auth
        const popup = window.open(
            url,
            'github_auth',
            'width=600,height=600,top=100,left=100,scrollbars=yes,resizable=yes'
        );

        if (!popup) {
            throw new Error('Popup blocked. Please allow popups for this site.');
        }

        // 3. Listen for message from popup
        return new Promise((resolve, reject) => {
            const handleMessage = (event) => {
                // Make sure we're receiving from our frontend origin
                if (event.origin !== 'http://localhost:5173') return;

                console.log('Received message from popup:', event.data);

                if (event.data.success === true || event.data.success === 'true') {
                    // Store token in localStorage
                    if (event.data.token) {
                        localStorage.setItem('token', event.data.token);
                    }

                    resolve({
                        success: true,
                        user: event.data.user,
                        redirect: '/repositories'
                    });
                } else {
                    reject(new Error(event.data.error || 'Authentication failed'));
                }

                // Clean up
                window.removeEventListener('message', handleMessage);
                if (popup && !popup.closed) {
                    popup.close();
                }
            };

            // Listen for messages from popup
            window.addEventListener('message', handleMessage);

            // Fallback: Check if popup is closed manually
            const popupCheck = setInterval(() => {
                if (popup.closed) {
                    clearInterval(popupCheck);
                    window.removeEventListener('message', handleMessage);
                    reject(new Error('Authentication was cancelled'));
                }
            }, 1000);

            // Timeout after 5 minutes
            setTimeout(() => {
                clearInterval(popupCheck);
                window.removeEventListener('message', handleMessage);
                if (popup && !popup.closed) {
                    popup.close();
                }
                reject(new Error('Authentication timeout'));
            }, 300000); // 5 minutes
        });
    } catch (error) {
        console.error('GitHub auth error:', error);
        throw error;
    }
};

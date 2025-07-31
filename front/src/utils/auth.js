export const handleGitHubAuth = async (isLogin = true) => {
    try {
        // 1. Get GitHub OAuth URL from backend
        const response = await fetch('http://localhost:8000/api/auth/github');
        const { url } = await response.json();

        // 2. Open a popup window for GitHub auth
        const popup = window.open(
            url,
            'github_auth',
            'width=600,height=600,top=100,left=100'
        );

        // 3. Listen for message from popup
        return new Promise((resolve, reject) => {
            const handleMessage = (event) => {
                if (event.origin !== 'http://localhost:8000') return;

                if (event.data.success) {
                    localStorage.setItem('token', event.data.token);
                    resolve(event.data);
                } else {
                    reject(new Error(event.data.message || 'Authentication failed'));
                }

                window.removeEventListener('message', handleMessage);
                popup.close();
            };

            window.addEventListener('message', handleMessage);

            // Fallback in case popup is blocked
            const popupCheck = setInterval(() => {
                if (popup.closed) {
                    clearInterval(popupCheck);
                    reject(new Error('Popup was closed'));
                }
            }, 500);
        });
    } catch (error) {
        console.error('GitHub auth error:', error);
        throw error;
    }
};

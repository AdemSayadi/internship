import { createRouter, createWebHistory } from 'vue-router';

const router = createRouter({
    history: createWebHistory(),
    routes: [
    //custom pages routes
        {
            path: '/',
            name: 'home',
            component: () => import('@/views/CustomPages/Home.vue'),
            meta: { requiresAuth: false }
        },
        {
            path: '/auth/login1',
            name: 'login',
            component: () => import('@/views/CustomPages/Auth/Login.vue')
        },
        {
            path: '/auth/signup1',
            name: 'signup',
            component: () => import('@/views/CustomPages/Auth/Signup.vue'),
            meta: { requiresGuest: true }
        },

        {
            path: '/repositories',
            name: 'repositories',
            component: () => import('@/views/CustomPages/Repositories.vue')
        },
        { path: '/submissions/:repositoryId',
            name: 'submissions',
            component: () => import('@/views/CustomPages/Submissions.vue')
        },

        { path: '/reviews/:submissionId',
            name: 'reviews',
            component: () => import('@/views/CustomPages/Reviews.vue')
        },
        { path: '/notifications',
            name: 'notifications',
            component: () => import('@/views/CustomPages/Notifications.vue')
        },
        {
            path: '/dashboard',
            name: 'dashboard2',
            component: () => import('@/views/CustomPages/Dashboard/Dashboard.vue')
        },
        {
            path: '/auth/github/callback',
            name: 'GitHubCallback',
            component: () => import('@/components/CustomComponents/GitHubCallback.vue')
        },
        {
            path: '/pull-requests',
            name: 'pull-requests',
            component: () => import('@/views/CustomPages/PullRequests.vue')
        }

    ]
})
// Add navigation guard
router.beforeEach((to, from, next) => {
    const publicPages = ['/auth/login1', '/auth/signup1', '/'];
    const authRequired = !publicPages.includes(to.path);
    const loggedIn = localStorage.getItem('token');

    if (authRequired && !loggedIn) {
        return next('/auth/login1');
    }

    next();
});

export default router;

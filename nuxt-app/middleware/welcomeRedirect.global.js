export default defineNuxtRouteMiddleware((to) => {
    if (to.path === '/') {
        if (import.meta.server) return
        const seen = localStorage.getItem('welcome_seen')
        if (!seen) {
            localStorage.setItem('welcome_seen', 'true')
            return navigateTo('/user/welcome')
        }
    }
})
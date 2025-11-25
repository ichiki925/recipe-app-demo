import {
    createUserWithEmailAndPassword,
    signInWithEmailAndPassword,
    sendEmailVerification,
    sendPasswordResetEmail,
    signOut,
    onAuthStateChanged,
    getIdToken
} from 'firebase/auth'

import { computed, readonly } from 'vue'

export const useAuth = () => {
    const { $auth } = useNuxtApp()
    const config = useRuntimeConfig()
    const user = useState('auth.user', () => null)
    const loading = useState('auth.loading', () => false)
    const _initOnce = useState('auth._initOnce', () => null)

    const API_BASE_URL = config.public.apiBaseUrl
    const APP_BASE_URL = config.public.apiBaseUrl

    const getCurrentUser = () => $auth.currentUser
    const waitForAuth = () =>
        new Promise((resolve) => {
            if ($auth.currentUser) return resolve($auth.currentUser)
            const unsubscribe = onAuthStateChanged($auth, (firebaseUser) => {
                unsubscribe()
                resolve(firebaseUser)
            })
        })

    const getFirebaseIdToken = async () => {
        const currentUser = $auth.currentUser
        if (!currentUser) throw new Error('User not authenticated')
        return await getIdToken(currentUser)
    }

    const cleanupFirebaseUser = async () => {
        if ($auth.currentUser) {
            try {
                await $auth.currentUser.delete()
            } catch (err) {
                console.error('Firebase認証ユーザー削除エラー:', err)
            }
        }
    }

    const registerUser = async (userData, endpoint) => {
        loading.value = true
        try {
            // 1) Firebase アカウント作成
            const { user: firebaseUser } = await createUserWithEmailAndPassword(
                $auth,
                userData.email,
                userData.password
            )

            if (process.env.NODE_ENV === 'production') {
                const isAdminRegistration = endpoint.includes('admin')
                const type = isAdminRegistration ? 'admin' : 'user'
                await sendEmailVerification(firebaseUser, {
                    url: `${APP_BASE_URL}/auth/action?type=${type}`,
                    handleCodeInApp: true,
                })
            }

            // 2) Laravel に登録（パスワードは送らない）
            const response = await $fetch(endpoint, {
                method: 'POST',
                baseURL: API_BASE_URL,
                body: {
                    firebase_uid: firebaseUser.uid,
                    name: userData.name,
                    email: userData.email,
                    ...(userData.admin_code ? { admin_code: userData.admin_code } : {})
                },
                credentials: 'omit',
                redirect: 'error'
            })

            if (!response?.success) {
                throw new Error(response?.error || 'ユーザー登録に失敗しました')
            }

            // ログアウト（メール確認が必要）
            // 🔧 開発環境ではログアウトしない
            if (process.env.NODE_ENV === 'production') {
                await signOut($auth)
            }

            return {
                ...response,
                needsVerification: process.env.NODE_ENV === 'production',
                message: process.env.NODE_ENV === 'production' 
                    ? '登録完了！確認メールを送信しました。メールを確認してログインしてください。'
                    : '登録完了！そのままログインできます。'
            }
        } catch (error) {
            await cleanupFirebaseUser()
            throw error
        } finally {
            loading.value = false
        }
    }

    const authenticateUser = async (idToken) => {
        const headers = {
            Authorization: `Bearer ${idToken}`,
            Accept: 'application/json',
            'Content-Type': 'application/json',
        }

        let isAdmin = false
        let userInfo = null

        // 1) admin 判定（401/403 は想定内）
        try {
            const a = await $fetch('/api/admin/check', {
                baseURL: API_BASE_URL,
                headers,
                credentials: 'omit',
                redirect: 'error'
            })
            isAdmin = !!a?.admin

            // 管理者の場合、トークンからユーザー情報を抽出
            if (isAdmin) {
                const tokenParts = idToken.split('.')
                if (tokenParts.length === 3) {
                    const payload = JSON.parse(atob(tokenParts[1]))
                    userInfo = {
                        firebase_uid: payload.sub,
                        email: payload.email,
                        role: 'admin'
                    }
                }
            }
        } catch (e) {
            const s = e?.response?.status ?? e?.statusCode ?? e?.status
            if (s !== 401 && s !== 403) throw e
        }

        // 2) 認証状態（user 情報）- 管理者でない場合のみ
        if (!isAdmin) {
            try {
                const b = await $fetch('api/auth/check', { 
                    baseURL: API_BASE_URL, 
                    headers, 
                    credentials: 'omit', 
                    redirect: 'error' 
                })
                if (b?.user) userInfo = b.user
                if (b?.admin) isAdmin = true
            } catch (_) {}
        }
        if (!userInfo) throw new Error('認証に失敗しました')

        return {
            ...userInfo,
            admin: isAdmin,
            role: isAdmin ? 'admin' : 'user',
        }
    }

    const register = (userData) =>
        registerUser(
            { name: userData.name, email: userData.email, password: userData.password },
            'api/auth/register'
        )

    const registerAdmin = (adminData) =>
        registerUser(
            {
                name: adminData.name,
                email: adminData.email,
                password: adminData.password,
                admin_code: adminData.adminCode
            },
            '/api/admin/register'
        )

    const login = async (email, password) => {
        loading.value = true
        try {
            const { user: firebaseUser } = await signInWithEmailAndPassword($auth, email, password)

            const isDemo = email.includes('@example.com')
            if (process.env.NODE_ENV === 'production' && !firebaseUser.emailVerified && !isDemo) {
                await signOut($auth)
                throw new Error('メールアドレスが確認されていません。確認メールをご確認ください。')
            }

            const idToken = await firebaseUser.getIdToken()
            user.value = await authenticateUser(idToken)
            return user.value
        } catch (error) {
            console.error('ログインエラー:', error)
            throw error
        } finally {
            loading.value = false
        }

    }

    const logout = async () => {
        try {
            const route = useRoute()
            const isAdminArea = route.path.startsWith('/admin')

            await signOut($auth)
            user.value = null
            await navigateTo(isAdminArea ? '/admin/login' : '/')
        } catch (error) {
            console.error('ログアウトエラー:', error)
            throw error
        }
    }

    const resetPassword = async (email, type = 'user') => {
        try {
            await sendPasswordResetEmail($auth, email, {
                url: `${APP_BASE_URL}/auth/action?type=${type}`,
                handleCodeInApp: true,
            })
        } catch (error) {
            console.error('パスワードリセットメール送信エラー:', error)
            throw error
        }
    }

    const initAuth = () =>
   // ★ 既に初期化中/済みなら同じ Promise を返す（多重 onAuthStateChanged を防止）
    (_initOnce.value ||= new Promise((resolve) => {
        const unsubscribe = onAuthStateChanged($auth, async (firebaseUser) => {
            try {
                loading.value = true
                if (firebaseUser) {
                    const idToken = await firebaseUser.getIdToken()
                    user.value = await authenticateUser(idToken)
                } else {
                    user.value = null
                }
            } catch (error) {
                console.error('認証初期化エラー:', error)
                user.value = null
            } finally {
                loading.value = false
                resolve()
                unsubscribe()
            }
        })
    }))

    const isAdmin = computed(() => user.value?.role === 'admin')
    const isLoggedIn = computed(() => !!user.value)

    return {
        user: readonly(user),
        loading: readonly(loading),
        isAdmin,
        isLoggedIn,
        getCurrentUser,
        waitForAuth,
        getIdToken: getFirebaseIdToken,
        register,
        registerAdmin,
        login,
        logout,
        resetPassword,
        initAuth
    }
}

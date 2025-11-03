<script setup>
import { ref, onMounted } from 'vue'
import { applyActionCode, verifyPasswordResetCode } from 'firebase/auth'

definePageMeta({ layout: false })

const { $auth } = useNuxtApp()
const route = useRoute()
const auth = $auth

const loading = ref(true)
const status = ref('')
const errorMessage = ref('')

onMounted(async () => {
    const mode = route.query.mode
    const oob = route.query.oobCode
    const type = route.query.type === 'admin' ? 'admin' : 'user'

    const loginPath = type === 'admin' ? '/admin/login' : '/auth/login'

    if (!mode || !oob) {
        return navigateTo(loginPath, { replace: true })
    }

    try {
        if (mode === 'verifyEmail') {
            await applyActionCode(auth, oob)
            status.value = 'success'
            
            // 成功メッセージを表示してからリダイレクト
            setTimeout(() => {
                navigateTo(`${loginPath}?verified=1`, { replace: true })
            }, 2000)
            return
        }

        if (mode === 'resetPassword') {
            // パスワードリセットコードを検証
            await verifyPasswordResetCode(auth, oob)
            return navigateTo(
                `/auth/reset-password?oobCode=${encodeURIComponent(oob)}&type=${type}`,
                { replace: true }
            )
        }

        // 未知のmode
        return navigateTo(loginPath, { replace: true })
        
    } catch (error) {
        loading.value = false
        status.value = 'error'
        
        // エラーメッセージを詳細に
        switch (error.code) {
            case 'auth/expired-action-code':
                errorMessage.value = 'リンクの有効期限が切れています'
                break
            case 'auth/invalid-action-code':
                errorMessage.value = 'リンクが無効です'
                break
            case 'auth/user-disabled':
                errorMessage.value = 'このアカウントは無効化されています'
                break
            default:
                errorMessage.value = '処理に失敗しました'
        }
        
        // エラー表示後にリダイレクト
        setTimeout(() => {
            navigateTo(`${loginPath}?error=action_failed`, { replace: true })
        }, 3000)
    } finally {
        loading.value = false
    }
})
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50">
        <div class="max-w-md w-full space-y-8 p-8">
            <!-- ローディング -->
            <div v-if="loading" class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-4 text-gray-600">処理中...</p>
            </div>

            <!-- 成功 -->
            <div v-else-if="status === 'success'" class="text-center">
                <div class="text-green-600 text-5xl mb-4">✓</div>
                <h2 class="text-2xl font-bold text-gray-900">メール認証完了</h2>
                <p class="mt-2 text-gray-600">ログインページへリダイレクトします...</p>
            </div>

            <!-- エラー -->
            <div v-else-if="status === 'error'" class="text-center">
                <div class="text-red-600 text-5xl mb-4">✕</div>
                <h2 class="text-2xl font-bold text-gray-900">エラー</h2>
                <p class="mt-2 text-red-600">{{ errorMessage }}</p>
                <p class="mt-4 text-gray-600">ログインページへリダイレクトします...</p>
            </div>
        </div>
    </div>
</template>
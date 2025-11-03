<template>
    <div class="reset-password-page">
        <div class="form-container">
        <h1 class="title">新しいパスワードの設定</h1>

        <div v-if="errors.general" class="error-message">
            {{ errors.general }}
        </div>

        <form @submit.prevent="handleSubmit" class="form">

            <div class="form-group">
            <label class="form-label">新しいパスワード</label>
            <input
                type="password"
                v-model="form.password"
                class="form-input"
                :class="{ 'error-input': errors.password }"
                :disabled="isSubmitting"
                required
            >
            <div v-if="errors.password" class="error">{{ errors.password }}</div>
            </div>

            <div class="form-group">
            <label class="form-label">パスワード確認</label>
            <input
                type="password"
                v-model="form.passwordConfirmation"
                class="form-input"
                :class="{ 'error-input': errors.passwordConfirmation }"
                :disabled="isSubmitting"
                required
            >
            <div v-if="errors.passwordConfirmation" class="error">{{ errors.passwordConfirmation }}</div>
            </div>

            <button
            type="submit"
            class="submit-button"
            :disabled="isSubmitting || !oobCode"
            >
            {{ isSubmitting ? '変更中...' : 'パスワードを変更' }}
            </button>
        </form>

            <NuxtLink :to="type === 'admin' ? '/admin/login' : '/auth/login'" class="login-link">
                ログイン画面に戻る
            </NuxtLink>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { confirmPasswordReset } from 'firebase/auth'

definePageMeta({ layout: false })

const { $auth } = useNuxtApp()
const route = useRoute()
const auth = $auth

const form = ref({ password: '', passwordConfirmation: '' })
const errors = ref({})
const isSubmitting = ref(false)
const oobCode = ref(null)

// ① type を決定する（?type= か、continueUrl の中から取得）
const type = computed(() => {
    // 直接 ?type=admin|user が付いていれば最優先
    if (route.query.type === 'admin' || route.query.type === 'user') {
        return route.query.type
    }

    const continueUrl = route.query.continueUrl
    if (typeof continueUrl === 'string') {
        try {
            const decodedUrl = decodeURIComponent(continueUrl)

            const parsedUrl = new URL(decodedUrl)

            const typeParam = parsedUrl.searchParams.get('type')

            if (typeParam === 'admin' || typeParam === 'user') {
                return typeParam
            }

        } catch (error) {

            console.error('continueUrl のパースに失敗しました:', error)
        }
    }

    return 'user'
})

onMounted(() => {
    oobCode.value = route.query.oobCode || null
    if (!oobCode.value) {
        errors.value.general = '無効なリンクです。再度パスワードリセットを行ってください。'
    }
})

const validateForm = () => {
    let ok = true
    errors.value = {}

    // ✅ type に応じたバリデーション（ここだけで判定完了）
    if (!form.value.password || !validateByType(form.value.password, type.value)) {
        errors.value.password = (type.value === 'admin')
            ? '管理者パスワードは大文字・小文字・数字を含む8文字以上にしてください'
            : 'パスワードは小文字・数字を含む6文字以上にしてください'
        ok = false
    }

    if (!form.value.passwordConfirmation) {
        errors.value.passwordConfirmation = 'パスワード確認を入力してください'
        ok = false
    } else if (form.value.password !== form.value.passwordConfirmation) {
        errors.value.passwordConfirmation = 'パスワードが一致しません'
        ok = false
    }

    return ok
}

const handleSubmit = async () => {
    if (!validateForm()) return
    if (!oobCode.value) {
        errors.value.general = '無効なリンクです'
        return
    }

    isSubmitting.value = true
    try {
        await confirmPasswordReset(auth, oobCode.value, form.value.password)
        // 成功後の遷移を type で出し分け
        await navigateTo(type.value === 'admin' ? '/admin/login' : '/auth/login')
    } catch (error) {
        let msg = 'エラーが発生しました。'
        if (error.code === 'auth/expired-action-code') msg = 'リンクの有効期限が切れています。'
        else if (error.code === 'auth/invalid-action-code') msg = '無効なリンクです。'
        else if (error.code === 'auth/weak-password') msg = 'パスワードが弱すぎます。'
        errors.value.general = msg
    } finally {
        isSubmitting.value = false
    }
}

const validateByType = (pwd, kind) => {
    if (kind === 'admin') {
        return pwd.length >= 8 && /[A-Z]/.test(pwd) && /[a-z]/.test(pwd) && /\d/.test(pwd)
    } else {
        return pwd.length >= 6 && /[a-z]/.test(pwd) && /\d/.test(pwd)
    }
}

    
</script>


<style scoped>
.reset-password-page {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    overflow: hidden;

    background-color: #f2f2f2;
    font-family: 'Noto Sans JP', sans-serif;
    color: #555;
    font-weight: 300;

    display: flex;
    align-items: center;
    justify-content: center;

    margin: 0;
    padding: 20px;
}

.form-container {
    max-width: 360px;
    width: 100%;
    padding: 2rem;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 3px 5px rgba(0, 0, 0, 0.1);
}

.title {
    text-align: center;
    font-size: 1.3rem;
    font-family: sans-serif;
    margin-bottom: 2rem;
    font-weight: 300;
    color: #555;
}

.form-group {
    margin-bottom: 1.5rem;
    text-align: left;
}

.form-label {
    display: block;
    margin-bottom: 0.4rem;
    font-size: 0.9rem;
    font-weight: 300;
    color: #333;
}

.form-input {
    width: 100%;
    padding: 0.6rem 0.8rem;
    border: none;
    border-bottom: 1px solid #dcdcdc;
    background-color: #fff;
    font-size: 1rem;
    font-weight: 300;
    outline: none;
    box-sizing: border-box;
}

.form-input:focus {
    border-bottom-color: #555;
}

.form-input.error-input {
    border-bottom-color: #d9534f;
}

.error {
    font-size: 0.85rem;
    color: #d9534f;
    margin-top: 0.3rem;
}

.submit-button {
    width: 100%;
    margin-top: 2rem;
    padding: 0.8rem;
    background-color: #dcdcdc;
    color: #555;
    border: none;
    font-size: 1rem;
    font-weight: 300;
    cursor: pointer;
    border-radius: 4px;
}

.submit-button:hover {
    background-color: #cfcfcf;
}

.submit-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.login-link {
    display: block;
    text-align: center;
    margin-top: 1.2rem;
    font-size: 0.85rem;
    color: #555;
    text-decoration: underline;
    font-weight: 300;
}

.login-link:hover {
    color: #9f9b9b;
}

.error-message {
  background-color: #f8d7da;
  color: #721c24;
  padding: 0.75rem;
  border-radius: 4px;
  margin-bottom: 1rem;
  font-size: 0.9rem;
}

@media screen and (max-width: 480px) {
    .reset-password-page {
        background-color: #ffffff;
        padding: 15px;
    }

    .form-container {
        box-shadow: none;
        border-radius: 0;
        max-width: 100%;
        padding: 1.5rem;
    }
}
</style>
<template>
    <div class="admin-register-page">
        <div class="form-container">
            <form class="form" @submit.prevent="handleSubmit">
                <div class="logo">
                    <img src="/images/rabbit-shape.svg" alt="Rabbit Logo" class="logo-image">
                </div>
                <h1 class="title">Admin Sign up</h1>

                <div v-if="errors.general" class="error general-error">{{ errors.general }}</div>

                <div class="form-group">
                    <label class="form-label">管理者コード</label>
                    <input
                        type="password"
                        class="form-input"
                        v-model="form.adminCode"
                        :class="{ 'error-input': errors.adminCode }"
                        @input="handleAdminCodeInput"
                        @blur="handleAdminCodeBlur"
                        :disabled="loading"
                        placeholder="管理者コードを入力してください"
                        required
                    >
                    <div v-if="errors.adminCode" class="error">{{ errors.adminCode }}</div>
                    <div class="help-text">※ 管理者コードが必要です</div>
                </div>

                <div class="form-group">
                    <label class="form-label">管理者名</label>
                    <input
                        type="text"
                        class="form-input"
                        v-model="form.name"
                        :class="{ 'error-input': errors.name }"
                        @input="handleNameInput"
                        @blur="handleNameBlur"
                        :disabled="loading"
                        maxlength="20"
                        required
                    >
                    <div v-if="errors.name" class="error">{{ errors.name }}</div>
                </div>

                <div class="form-group">
                    <label class="form-label">メールアドレス</label>
                    <input
                        type="email"
                        class="form-input"
                        v-model="form.email"
                        :class="{ 'error-input': errors.email }"
                        @input="handleEmailInput"
                        @blur="handleEmailBlur"
                        :disabled="loading"
                        required
                    >
                    <div v-if="errors.email" class="error">{{ errors.email }}</div>
                </div>

                <div class="form-group">
                    <label class="form-label">パスワード</label>
                    <input
                        type="password"
                        class="form-input"
                        v-model="form.password"
                        :class="{ 'error-input': errors.password }"
                        @input="handlePasswordInput"
                        @blur="handlePasswordBlur"
                        :disabled="loading"
                        required
                        minlength="8"
                    >
                    <div v-if="errors.password" class="error">{{ errors.password }}</div>
                    <div class="help-text">※ 管理者パスワードは8文字以上</div>
                </div>

                <div class="form-group">
                    <label class="form-label">パスワード確認</label>
                    <input
                        type="password"
                        class="form-input"
                        v-model="form.password_confirmation"
                        :class="{ 'error-input': errors.password_confirmation }"
                        @input="handlePasswordConfirmInput"
                        @blur="handlePasswordConfirmBlur"
                        :disabled="loading"
                        required
                    >
                    <div v-if="errors.password_confirmation" class="error">{{ errors.password_confirmation }}</div>
                </div>

                <button
                    type="submit"
                    class="submit-btn"
                    :class="{ 'disabled': !isFormValid || loading }"
                    :disabled="!isFormValid || loading"
                >
                    <i v-if="loading" class="fas fa-spinner fa-spin" style="margin-right: 5px;"></i>
                    {{ loading ? '登録中...' : '管理者登録' }}
                </button>
            </form>

            <NuxtLink to="/admin/login" class="login-link">管理者ログインはこちら</NuxtLink>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'

definePageMeta({
    title: '管理者登録',
    layout: false
})

const firebaseErrorMessages = {
    'auth/email-already-in-use': 'このメールアドレスは既に使用されています',
    'auth/invalid-email': '無効なメールアドレスです',
    'auth/weak-password': 'パスワードは8文字以上で入力してください',
    'auth/operation-not-allowed': 'メール/パスワード認証が無効になっています',
    'auth/user-not-found': 'ユーザーが見つかりません',
    'auth/wrong-password': 'パスワードが正しくありません',
    'auth/admin-code-invalid': '管理者コードが正しくありません'
}

const translateFirebaseError = (code) => {
    return firebaseErrorMessages[code] || '管理者登録でエラーが発生しました'
}

const form = reactive({
    adminCode: '',
    name: '',
    email: '',
    password: '',
    password_confirmation: ''
})

const errors = ref({})
const loading = ref(false)

const ADMIN_CODE = process.env.ADMIN_REGISTRATION_CODE || ''

const passwordsMatch = computed(() => {
    return form.password && form.password_confirmation && form.password === form.password_confirmation
})

const isFormValid = computed(() => {
    return !errors.value.adminCode &&
            !errors.value.name &&
            !errors.value.email &&
            !errors.value.password &&
            !errors.value.password_confirmation &&
            form.adminCode.trim().length > 0 &&
            form.name.trim().length > 0 &&
            form.email.trim().length > 0 &&
            form.password.length > 0 &&
            form.password_confirmation.length > 0 &&
            passwordsMatch.value
})

const validateAdminCode = (code) => {
    const trimmed = code.trim()

    if (!trimmed) {
        return '管理者コードを入力してください'
    }


    return null
}

const validateUserName = (name) => {
    const trimmed = name.trim()

    if (!trimmed) {
        return '管理者名を入力してください'
    }

    if (trimmed.length < 2) {
        return '管理者名は2文字以上で入力してください'
    }

    if (trimmed.length > 20) {
        return '管理者名は20文字以内で入力してください'
    }

    const allowedPattern = /^[a-zA-Z0-9\u3040-\u309F\u30A0-\u30FF\u4E00-\u9FAF_\-\s]+$/
    if (!allowedPattern.test(trimmed)) {
        return '使用できない文字が含まれています'
    }

    if (/\s{2,}/.test(trimmed)) {
        return '連続するスペースは使用できません'
    }

    return null
}

const validateEmail = (email) => {
    const trimmed = email.trim()

    if (!trimmed) {
        return 'メールアドレスを入力してください'
    }

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailPattern.test(trimmed)) {
        return '正しいメールアドレスを入力してください'
    }

    return null
}

const validatePassword = (password) => {
    if (!password) {
        return 'パスワードを入力してください'
    }

    if (password.length < 8) {
        return 'パスワードは8文字以上で入力してください'
    }

    if (password.length > 100) {
        return 'パスワードは100文字以内で入力してください'
    }

    const hasUpperCase = /[A-Z]/.test(password)
    const hasLowerCase = /[a-z]/.test(password)
    const hasNumbers = /\d/.test(password)

    if (!hasUpperCase || !hasLowerCase || !hasNumbers) {
        return 'パスワードは大文字・小文字・数字を含めてください'
    }
    return null
}

const validatePasswordConfirmation = (passwordConfirm, password) => {
    if (!passwordConfirm) {
        return 'パスワード確認を入力してください'
    }

    if (passwordConfirm !== password) {
        return 'パスワードが一致しません'
    }

    return null
}

const handleAdminCodeInput = () => {
    errors.value.adminCode = ''
}

const handleAdminCodeBlur = () => {
    const validationError = validateAdminCode(form.adminCode)
    if (validationError) {
        errors.value.adminCode = validationError
    }
}

const handleNameInput = () => {
    errors.value.name = ''
}

const handleNameBlur = () => {
    const validationError = validateUserName(form.name)
    if (validationError) {
        errors.value.name = validationError
    }
}

const handleEmailInput = () => {
    errors.value.email = ''
}

const handleEmailBlur = () => {
    const validationError = validateEmail(form.email)
    if (validationError) {
        errors.value.email = validationError
    }
}

const handlePasswordInput = () => {
    errors.value.password = ''
    // パスワード変更時にパスワード確認も再チェック
    if (form.password_confirmation) {
        const confirmError = validatePasswordConfirmation(form.password_confirmation, form.password)
        if (confirmError) {
            errors.value.password_confirmation = confirmError
        } else {
            errors.value.password_confirmation = ''
        }
    }
}

const handlePasswordBlur = () => {
    const validationError = validatePassword(form.password)
    if (validationError) {
        errors.value.password = validationError
    }
}

const handlePasswordConfirmInput = () => {
    errors.value.password_confirmation = ''
}

const handlePasswordConfirmBlur = () => {
    const validationError = validatePasswordConfirmation(form.password_confirmation, form.password)
    if (validationError) {
        errors.value.password_confirmation = validationError
    }
}

const handleSubmit = async () => {
    const adminCodeError = validateAdminCode(form.adminCode)
    const nameError = validateUserName(form.name)
    const emailError = validateEmail(form.email)
    const passwordError = validatePassword(form.password)
    const passwordConfirmError = validatePasswordConfirmation(form.password_confirmation, form.password)

    if (adminCodeError) errors.value.adminCode = adminCodeError
    if (nameError) errors.value.name = nameError
    if (emailError) errors.value.email = emailError
    if (passwordError) errors.value.password = passwordError
    if (passwordConfirmError) errors.value.password_confirmation = passwordConfirmError

    if (adminCodeError || nameError || emailError || passwordError || passwordConfirmError) {
        return
    }

    if (loading.value) return
    loading.value = true
    errors.value = {}

    try {
        const { registerAdmin } = useAuth()

        const response = await registerAdmin({
            adminCode: form.adminCode,
            name: form.name.trim(),
            email: form.email,
            password: form.password
        })

        errors.value = {}

        if (response.needsVerification) {
            alert(response.message || '登録完了！確認メールを送信しました。')
        }

        await navigateTo('/admin/login?registered=true')

    } catch (error) {
        console.error('❌ 管理者登録エラー:', error)

        const errorMessage = translateFirebaseError(error.code)
        errors.value.general = errorMessage

    } finally {
        loading.value = false
    }
}
</script>

<style scoped>
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css');

.admin-register-page {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;

    background-color: #f2f2f2;
    font-family: 'Noto Sans JP', sans-serif;
    color: #555;
    font-weight: 300;

    display: flex;
    align-items: center;
    justify-content: center;

    margin: 0;
    padding: 20px;
    box-sizing: border-box;

    overflow-y: auto;
}

.form-container {
    max-width: 420px;
    width: 100%;
    padding: 0;
    background: transparent;
    border-radius: 0;
    box-shadow: none;
}

.form {
    text-align: center;
    background: transparent;
    backdrop-filter: none;
    border-radius: 0;
    padding: 2.5rem;
    border: none;
}

.logo {
    text-align: center;
    margin-bottom: 1rem;
    width: 100%;
}

.logo-image {
    width: 60px;
    height: auto;
    opacity: 0.9;
    display: block;
    margin: 0 auto;
}

.title {
    text-align: center;
    font-size: 2.5rem;
    font-family: 'Italianno', cursive;
    font-weight: 400;
    margin-bottom: 2rem;
    color: #222;
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
    border-bottom: 1px solid #ccc;
    background-color: #fff;
    font-size: 1rem;
    font-weight: 400;
    outline: none;
    transition: border-bottom-color 0.3s ease;
    box-sizing: border-box;
}

.form-input::placeholder {
    color: rgba(255, 255, 255, 0.6);
}

.form-input:focus {
    border-bottom-color: #555;
}

.form-input.error-input {
    border-bottom-color: #d9534f;
}

.form-input:disabled {
    background-color: #f8f9fa;
    cursor: not-allowed;
}

.help-text {
    font-size: 0.8rem;
    color: #666;
    margin-top: 0.3rem;
    font-style: italic;
}

.error {
    font-size: 0.85rem;
    color: #d9534f;
    margin-top: 0.3rem;
}

.general-error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 0.75rem;
    border-radius: 4px;
    margin-bottom: 1rem;
    text-align: center;
}

.submit-btn {
    width: 100%;
    margin-top: 1.5rem;
    padding: 0.8rem;
    background-color: #ddd;
    color: #222;
    border: none;
    font-size: 1rem;
    font-weight: 300;
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.3s ease;
}

.submit-btn:hover:not(:disabled) {
    background-color: #bbb;
}

.submit-btn.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.submit-btn.disabled:hover {
    background-color: #ddd;
}

.login-link {
    display: block;
    text-align: center;
    font-size: 0.85rem;
    color: #333;
    text-decoration: underline;
    font-weight: 300;
    transition: color 0.3s ease;
}

.login-link:hover {
    color: #9f9b9b;
}

.fa-spin {
    animation: fa-spin 1s infinite linear;
}

@keyframes fa-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@media screen and (max-width: 480px) {
    .admin-register-page {
        height: auto;
        min-height: 100vh;
        padding: 10px;
        align-items: flex-start;
        justify-content: flex-start;
        padding-top: 20px;
        padding-bottom: 40px;
    }

    .form {
        padding: 1.5rem;
        border-radius: 0;
        min-height: auto;
    }

    .logo-image {
        width: 50px;
        margin-bottom: 10px;
    }

    .title {
        font-size: 1.3rem;
        margin-bottom: 0.3rem;
    }

    .subtitle {
        font-size: 0.8rem;
        margin-bottom: 1.2rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-label {
        font-size: 0.85rem;
        margin-bottom: 0.3rem;
    }

    .form-input {
        padding: 0.6rem 0.5rem;
        font-size: 0.9rem;
    }

    .help-text {
        font-size: 0.75rem;
        margin-top: 0.2rem;
    }

    .submit-btn {
        padding: 0.8rem;
        font-size: 0.9rem;
        margin-top: 1.5rem;
    }

    .login-link {
        margin-top: 1rem;
        font-size: 0.8rem;
        margin-bottom: 20px;
    }
}

@media screen and (max-width: 360px) {
    .admin-register-page {
        padding: 5px;
        padding-top: 15px;
        padding-bottom: 30px;
    }

    .form {
        padding: 1rem;
    }

    .logo-image {
        width: 45px;
    }

    .title {
        font-size: 1.2rem;
    }

    .subtitle {
        font-size: 0.75rem;
    }

    .form-group {
        margin-bottom: 0.8rem;
    }
}

/* 縦長画面（iPhone等）用の追加調整 */
@media screen and (max-width: 480px) and (max-height: 700px) {
    .admin-register-page {
        padding-top: 10px;
        padding-bottom: 20px;
    }

    .form {
        padding: 1.2rem;
    }

    .form-group {
        margin-bottom: 0.8rem;
    }

    .submit-btn {
        margin-top: 1rem;
    }

    .login-link {
        margin-top: 0.8rem;
        margin-bottom: 15px;
    }
}
</style>

<template>
  <div class="login-page">
  <Head>
    <Meta name="robots" content="noindex, nofollow" />
    <Title>Login | Vanilla's Kitchen</Title>
    <Link rel="canonical" href="https://vanilla-kitchen.com/auth/login" />
  </Head>

    <div class="form-container">
      <form class="login-form" @submit.prevent="handleLogin">
        <div class="logo">
          <img src="/images/rabbit-shape.svg" alt="バニラキッチン（Vanilla's Kitchen）のロゴ" class="logo-image">
        </div>
        <h1 class="login-title">Login</h1>

        <div v-if="registeredMessage" class="success-message">
          {{ registeredMessage }}
        </div>

        <div v-if="errors.general" class="error-message">
          {{ errors.general }}
        </div>

        <div v-if="successMessage" class="success-message">
          {{ successMessage }}
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
            autocomplete="email"
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
            autocomplete="current-password"
          >
          <div v-if="errors.password" class="error">{{ errors.password }}</div>
        </div>

        <button
          type="submit"
          class="submit-button"
          :class="{ 'disabled': !isFormValid || loading }"
          :disabled="!isFormValid || loading"
        >
          <i v-if="loading" class="fas fa-spinner fa-spin" style="margin-right: 5px;"></i>
          {{ loading ? 'ログイン中...' : 'ログイン' }}
        </button>

        <button
          type="button"
          class="demo-button"
          @click="handleDemoLogin"
          :disabled="loading"
        >
          🌱 デモアカウントでログイン
        </button>

        <div class="form-footer">
          <NuxtLink to="/auth/forgot-password">パスワードを忘れた方はこちら</NuxtLink>
        </div>
        <div class="form-footer">
          <NuxtLink to="/auth/register">アカウントをお持ちでない方はこちら</NuxtLink>
        </div>
        <div class="form-footer">
          <NuxtLink to="/">トップページに戻る</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'

definePageMeta({
  layout: false
})

const form = reactive({
  email: '',
  password: ''
})

const errors = ref({})
const loading = ref(false)
const successMessage = ref('')
const registeredMessage = ref('')

const { login } = useAuth()

const route = useRoute()

const isFormValid = computed(() => {
  return !errors.value.email &&
        !errors.value.password &&
        form.email.trim().length > 0 &&
        form.password.length > 0
})

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

  return null
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
}

const handlePasswordBlur = () => {
  const validationError = validatePassword(form.password)
  if (validationError) {
    errors.value.password = validationError
  }
}

onMounted(() => {
  if (route.query.registered === 'true') {
    registeredMessage.value = '会員登録が完了しました。ログインしてください。'

    setTimeout(() => {
      registeredMessage.value = ''
    }, 3000)
  }
})

const handleLogin = async () => {
  const emailError = validateEmail(form.email)
  const passwordError = validatePassword(form.password)

  if (emailError) errors.value.email = emailError
  if (passwordError) errors.value.password = passwordError

  if (emailError || passwordError) {
    return
  }

  if (loading.value) return
  loading.value = true
  errors.value = {}
  successMessage.value = ''

  try {
    if (!login) {
      console.error('❌ useAuth composable が利用できません')
      errors.value.general = 'システムエラーが発生しました'
      return
    }

    const result = await login(form.email.trim(), form.password)

    if (!result || (!result.id && !result.firebase_uid)) {
      console.error('❌ ログイン結果が無効です:', result)
      errors.value.general = 'ログインに失敗しました'
      return
    }

    successMessage.value = 'ログインに成功しました！'

    errors.value = {}

    await new Promise(resolve => setTimeout(resolve, 500))

    setTimeout(async () => {
      try {
        await navigateTo('/user', { replace: true })
      } catch (navError) {
        console.error('❌ ナビゲーションエラー:', navError)
        window.location.href = '/user'
      }
    }, 200)

  } catch (error) {
    console.error('❌ ログインエラー:', error)
    console.error('エラーの詳細:', {
      message: error.message,
      code: error.code,
      stack: error.stack
    })

    let errorMessage = 'ログインに失敗しました'

    if (error.code) {
      switch (error.code) {
        case 'auth/user-not-found':
          errorMessage = 'このメールアドレスは登録されていません'
          break
        case 'auth/wrong-password':
          errorMessage = 'パスワードが間違っています'
          break
        case 'auth/invalid-email':
          errorMessage = 'メールアドレスの形式が正しくありません'
          break
        case 'auth/too-many-requests':
          errorMessage = 'ログイン試行回数が多すぎます。しばらく待ってから再試行してください'
          break
        case 'auth/network-request-failed':
          errorMessage = 'ネットワークエラーが発生しました'
          break
        case 'auth/user-disabled':
          errorMessage = 'このアカウントは無効化されています'
          break
        case 'auth/invalid-credential':
          errorMessage = 'メールアドレスまたはパスワードが間違っています'
          break
        default:
          errorMessage = error.message || 'ログインに失敗しました'
      }
    } else {
      errorMessage = error.message || 'システムエラーが発生しました'
    }

    errors.value.general = errorMessage

  } finally {
    loading.value = false
  }
}

const handleDemoLogin = async () => {
  // デモアカウントの情報を自動入力
  form.email = 'demo-user@example.com'
  form.password = 'demo1234'

  // エラーをクリア
  errors.value = {}

  // 少し待ってからログイン実行（ユーザーが情報を確認できるように）
  await new Promise(resolve => setTimeout(resolve, 300))

  // ログイン処理を実行
  await handleLogin()
}
</script>

<style scoped>
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css');

.login-page {
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
    padding: 0;
}

.form-container {
    max-width: 400px;
    width: 90%;
    padding: 2rem;
    background-color: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.login-form {
    text-align: center;
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

.login-title {
    color: #222;
    font-size: 3rem;
    font-family: 'Italianno', cursive;
    font-weight: 400;
    margin-bottom: 0.5rem;
    margin-top: 0.5rem;
    text-align: center;
}

.form-group {
    margin-bottom: 1.5rem;
    text-align: left;
}

.form-label {
    display: block;
    margin-bottom: 0.4rem;
    font-size: 0.95rem;
    color: #333;
    font-weight: 400;
}

.form-input {
    width: 100%;
    padding: 0.75rem;
    border: none;
    border-bottom: 1px solid #dcdcdc;
    background-color: #fff;
    font-size: 1rem;
    font-weight: 400;
    color: #555;
    box-sizing: border-box;
    transition: all 0.3s ease;
}

.form-input:focus {
    outline: none;
    background-color: #f8f8f8;
    border-bottom-color: #555;
}

.form-input.error-input {
    border-bottom-color: #d9534f;
}

.form-input:disabled {
    background-color: #f8f9fa;
    cursor: not-allowed;
}

.error {
    font-size: 0.85rem;
    color: #d9534f;
    margin-top: 0.3rem;
}

.error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 0.75rem;
    margin-bottom: 1rem;
    border-radius: 4px;
    font-size: 0.9rem;
    border: 1px solid #f5c6cb;
}

.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 0.75rem;
    margin-bottom: 1rem;
    border-radius: 4px;
    font-size: 0.9rem;
    border: 1px solid #c3e6cb;
}

.submit-button {
    width: 100%;
    margin-top: 2rem;
    padding: 0.75rem;
    background-color: #ddd;
    color: #222;
    border: none;
    font-size: 1rem;
    font-weight: 400;
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.submit-button:hover:not(:disabled) {
    background-color: #bbb;
}

.submit-button.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.submit-button.disabled:hover {
    background-color: #ddd;
}

.demo-button {
    width: 100%;
    margin-top: 1rem;
    padding: 0.75rem;
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffc107;
    font-size: 1rem;
    font-weight: 400;
    cursor: pointer;
    border-radius: 4px;
    transition: all 0.2s;
}

.demo-button:hover:not(:disabled) {
    background-color: #ffe69c;
    border-color: #ffb300;
}

.demo-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.form-footer {
    text-align: center;
    margin-top: 1rem;
    font-size: 0.95rem;
}

.form-footer a {
    color: #888;
    text-decoration: underline;
    transition: color 0.3s ease;
}

.form-footer a:hover {
    color: #666;
}

.fa-spin {
    animation: fa-spin 1s infinite linear;
}

@keyframes fa-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@media screen and (max-width: 480px) {
    .login-page {
        background-color: #ffffff;
        height: auto;
        min-height: 100vh;
        overflow-y: auto;
        align-items: flex-start;
        padding-top: 20px;
        box-sizing: border-box;
    }

    .form-container {
        box-shadow: none;
        border-radius: 0;
        margin: 10px;
        max-width: 100%;
        padding: 1rem;
        margin-bottom: 30px;
    }
}
</style>
<template>
  <div class="forgot-password-page">
    <Head>
      <Meta name="robots" content="noindex, nofollow" />
      <Title>Forgot password | Vanilla's Kitchen</Title>
    </Head>
    <div class="form-container">
      <h1 class="title">パスワードをお忘れですか？</h1>
      <p class="description">
        アカウントにアクセスするには、<br>
        登録したメールアドレスを入力してください。
      </p>

      <!-- 成功メッセージ -->
      <div v-if="successMessage" class="success-message">
        パスワード再設定用のメールを送信しました。<br>
        ご確認ください。
      </div>

      <!-- エラーメッセージ -->
      <div v-if="errors.general" class="error-message">
        {{ errors.general }}
      </div>

      <!-- フォーム（成功時は非表示） -->
      <form v-if="!successMessage" @submit.prevent="handleSubmit" class="form">
        <div class="form-group">
          <label class="form-label">メールアドレス</label>
          <input
            type="email"
            v-model="form.email"
            class="form-input"
            :class="{ 'error-input': errors.email }"
            @input="handleEmailInput"
            @blur="handleEmailBlur"
            :disabled="isSubmitting"
            required
          >
          <div v-if="errors.email" class="error">{{ errors.email }}</div>
        </div>

        <button
          type="submit"
          class="submit-button"
          :class="{ 'disabled': !isFormValid || isSubmitting }"
          :disabled="!isFormValid || isSubmitting"
        >
          <i v-if="isSubmitting" class="fas fa-spinner fa-spin" style="margin-right: 5px;"></i>
          {{ isSubmitting ? '送信中...' : '再設定リンクを送信' }}
        </button>
      </form>

      <!-- 成功時のみ表示される再送信ボタン -->
      <div v-if="successMessage" class="success-actions">
        <button @click="resetForm" class="resend-button">
          別のメールアドレスで再送信
        </button>
      </div>

      <nuxt-link to="/auth/login" class="login-link">
        ログイン画面に戻る
      </nuxt-link>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

definePageMeta({
  layout: false
})

const returnType = 'user'

const form = ref({ email: '' })
const errors = ref({})
const successMessage = ref(false)
const isSubmitting = ref(false)

// useAuth composableを使用
const { resetPassword } = useAuth()

const isFormValid = computed(() => {
  return !errors.value.email &&
        form.value.email.trim().length > 0
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

const handleEmailInput = () => {
  errors.value.email = ''
}

const handleEmailBlur = () => {
  const validationError = validateEmail(form.value.email)
  if (validationError) {
    errors.value.email = validationError
  }
}

const handleSubmit = async () => {
  // 最終バリデーション
  const emailError = validateEmail(form.value.email)

  if (emailError) {
    errors.value.email = emailError
    return
  }

  // 送信中の重複防止
  if (isSubmitting.value) return
  isSubmitting.value = true
  errors.value = {}
  successMessage.value = false

  try {
    await resetPassword(form.value.email.trim(), returnType)
    successMessage.value = true

    errors.value = {}

    form.value.email = ''

  } catch (error) {
    console.error('❌ パスワード再設定エラー:', error)

    let errorMessage = 'エラーが発生しました。もう一度お試しください。'

    if (error.code) {
      switch (error.code) {
        case 'auth/user-not-found':
          errorMessage = 'このメールアドレスは登録されていません'
          break
        case 'auth/invalid-email':
          errorMessage = 'メールアドレスの形式が正しくありません'
          break
        case 'auth/network-request-failed':
          errorMessage = 'ネットワークエラーが発生しました'
          break
        case 'auth/too-many-requests':
          errorMessage = 'リクエストが多すぎます。しばらく待ってから再試行してください'
          break
        default:
          errorMessage = error.message || 'エラーが発生しました'
      }
    } else {
      errorMessage = error.message || 'システムエラーが発生しました'
    }

    errors.value.general = errorMessage

  } finally {
    isSubmitting.value = false
  }
}

const resetForm = () => {
  form.value.email = ''
  errors.value = {}
  successMessage.value = false
}
</script>

<style scoped>
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css');

.forgot-password-page {
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
    padding-top: 50px;
    padding: 20px;
}

.form-container {
    max-width: 450px;
    width: 100%;
    padding: 2.5rem;
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
    color: #222;
}

.description {
    font-size: 0.95rem;
    color: #555;
    font-weight: 300;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    text-align: center;
}

.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 0.75rem;
    border-radius: 4px;
    margin-bottom: 1rem;
    text-align: center;
    font-size: 0.9rem;
    border: 1px solid #c3e6cb;
}

.error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 0.75rem;
    border-radius: 4px;
    margin-bottom: 1rem;
    text-align: center;
    font-size: 0.9rem;
    border: 1px solid #f5c6cb;
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
    transition: border-bottom-color 0.3s ease;
}

.form-input:focus {
    border-bottom-color: #333;
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

.submit-button {
    width: 100%;
    margin-top: 2rem;
    padding: 0.8rem;
    background-color: #ddd;
    color: #333;
    border: none;
    font-size: 1rem;
    font-weight: 300;
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.3s ease;
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

.success-actions {
    text-align: center;
    margin-top: 1.5rem;
}

.resend-button {
    background-color: transparent;
    color: #333;
    border: 1px solid #ddd;
    padding: 0.6rem 1.2rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.resend-button:hover {
    background-color: #f8f8f8;
    color: #888;
    border-color: #bbb;
}

.login-link {
    display: block;
    text-align: center;
    margin-top: 1.2rem;
    font-size: 0.85rem;
    color: #555;
    text-decoration: underline;
    font-weight: 300;
    transition: color 0.3s ease;
}

.login-link:hover {
    color: #9f9b9b;
}

/* スピナーアニメーション */
.fa-spin {
    animation: fa-spin 1s infinite linear;
}

@keyframes fa-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@media screen and (max-width: 480px) {
    .forgot-password-page {
        background-color: #ffffff;
        padding: 15px;
        align-items: flex-start;
    }

    .form-container {
        box-shadow: none;
        border-radius: 0;
        max-width: 100%;
        padding: 1.5rem;
    }

    .description {
        font-size: 0.9rem;
    }
}
</style>
<template>
  <div class="admin-login-page">
    <div class="form-container">
      <form class="login-form" @submit.prevent="handleLogin">
        <div class="logo">
          <img src="/images/rabbit-shape.svg" alt="Rabbit Logo" class="logo-image">
        </div>
        <h1 class="login-title">Admin Login</h1>
        <div class="form-group">
          <label class="form-label">メールアドレス</label>
          <input
            type="email"
            class="form-input"
            v-model="form.email"
            :class="{ 'error-input': errors.email }"
            :disabled="localLoading || loading"
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
            :disabled="localLoading || loading"
          >
          <div v-if="errors.password" class="error">{{ errors.password }}</div>
        </div>
        <div v-if="errors.general" class="error general-error">{{ errors.general }}</div>
        <button type="submit" class="submit-button" :disabled="localLoading || loading">
          {{ (localLoading || loading) ? 'ログイン中...' : 'ログイン' }}
        </button>
        <div class="form-footer">
          <NuxtLink to="/admin/forgot-password" class="forgot-link">パスワードを忘れた場合はこちら</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  layout: false
})

const { login, logout, loading } = useAuth()

const form = ref({
  email: '',
  password: ''
})

const errors = ref({
  email: '',
  password: '',
  general: ''
})
const localLoading = ref(false)

// 「ログイン」ボタンを押した時の処理
const handleLogin = async () => {
  // いったんエラーをクリア
  errors.value = { email: '', password: '', general: '' }

  // 入力チェック（超シンプル）
  if (!form.value.email)  errors.value.email  = 'メールアドレスを入力してください'
  if (!form.value.password) errors.value.password = 'パスワードを入力してください'
  if (errors.value.email || errors.value.password) return

  localLoading.value = true
  try {
    // 実際のログイン処理
    const userData = await login(form.value.email, form.value.password)

    // 管理者ならダッシュボードへ遷移
    if (userData?.role === 'admin') {
      await navigateTo('/admin/dashboard')
      return
    }

    // 管理者じゃない場合
    await logout()
    errors.value.general = '管理者権限がありません'

  } catch (e) {
    const firebaseMsg = {
      'auth/user-not-found': 'ユーザーが見つかりません',
      'auth/wrong-password': 'パスワードが正しくありません',
      'auth/invalid-email': 'メールアドレスの形式が正しくありません',
      'auth/user-disabled': 'このアカウントは無効になっています',
      'auth/too-many-requests': '試行回数が多すぎます。しばらく待ってください',
      'auth/network-request-failed': 'ネットワークエラーが発生しました'
    }

    errors.value.general =
      firebaseMsg[e?.code] ||
      e?.data?.error ||
      e?.data?.message ||
      (e?.status === 401 ? '未認証です' : '') ||
      (e?.status === 403 ? '管理者権限がありません' : '') ||
      'サーバーエラーが発生しました'

    console.error('login failed:', e)
  } finally {
    localLoading.value = false
  }
}

onUnmounted(() => {
  localLoading.value = false
})
</script>

<style scoped>
.admin-login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.form-container {
  width: 100%;
  max-width: 400px;
  padding: 20px;
  box-sizing: border-box;
}

.login-form {
  background: white;
  padding: 40px;
  border-radius: 8px;
  text-align: center;
  box-sizing: border-box;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.logo {
  margin-bottom: 20px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.logo-image {
  width: 60px;
  height: 60px;
  object-fit: contain;
}

.login-title {
  font-size: 2.5rem;
  color: #333;
  margin: 0 0 30px 0;
  font-family: 'Italianno', cursive;
  font-weight: 400;
}

.form-group {
  margin-bottom: 20px;
  text-align: left;
}

.form-label {
  display: block;
  font-size: 14px;
  font-weight: normal;
  color: #333;
  margin-bottom: 8px;
}

.form-input {
  width: 100%;
  padding: 12px 16px;
  font-size: 16px;
  border: none;
  border-bottom: 1px solid #ddd;
  box-sizing: border-box;
  background: white;
  color: #333;
  transition: all 0.3s ease;
}

.form-input:focus {
  outline: none;
  background-color: #f8f8f8;
  border-color: #999;
}

.form-input:disabled {
  background-color: #f5f5f5;
  color: #999;
  cursor: not-allowed;
}

.error-input {
  border-color: #dc3545;
}

.error {
  color: #dc3545;
  font-size: 12px;
  margin-top: 4px;
}

.general-error {
  text-align: center;
  margin-bottom: 15px;
  font-size: 14px;
}

.submit-button {
  width: 100%;
  padding: 12px;
  background: #ddd;
  color: #333;
  border: none;
  border-radius: 4px;
  font-size: 16px;
  font-weight: normal;
  cursor: pointer;
  margin-top: 20px;
  box-sizing: border-box;
  transition: all 0.3s ease;
}

.submit-button:hover:not(:disabled) {
  background: #ccc;
}

.submit-button:disabled {
  background: #e0e0e0;
  color: #999;
  cursor: not-allowed;
}

.form-footer {
  text-align: center;
}

.form-footer a {
  display: block;
  color: #888;
  text-decoration: underline;
  font-size: 14px;
  margin: 30px auto;
  transition: color 0.3s ease;
}

.form-footer a:hover {
  color: #666;
}

.form-footer a:last-child {
  margin-bottom: 0;
}

@media (max-width: 768px) {
  .admin-login-page {
    min-height: 100vh;
    align-items: flex-start;
    padding: 20px;
    background: white;
  }

  .form-container {
    max-width: 100%;
    padding: 0;
  }

  .login-form {
    padding: 30px 20px;
    border: none;
    box-shadow: none;
    border-radius: 0;
    background: white;
  }
}
</style>
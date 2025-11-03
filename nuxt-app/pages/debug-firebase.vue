<!-- pages/debug-firebase.vue -->
<template>
    <div class="p-8">
      <h1 class="text-2xl font-bold mb-6">Firebase デバッグテスト</h1>
      
      <div class="space-y-4">
        <div class="p-4 border rounded">
          <h2 class="font-semibold">1. Firebase インスタンス確認</h2>
          <button @click="checkFirebaseInstance" class="bg-blue-500 text-white px-4 py-2 rounded mt-2">
            Firebase インスタンスをチェック
          </button>
          <pre class="mt-2 p-2 bg-gray-100 rounded text-sm">{{ firebaseStatus }}</pre>
        </div>
  
        <div class="p-4 border rounded">
          <h2 class="font-semibold">2. 認証テスト</h2>
          <button @click="testAuth" class="bg-green-500 text-white px-4 py-2 rounded mt-2">
            認証機能をテスト
          </button>
          <pre class="mt-2 p-2 bg-gray-100 rounded text-sm">{{ authStatus }}</pre>
        </div>
  
        <div class="p-4 border rounded">
          <h2 class="font-semibold">3. 直接初期化テスト</h2>
          <button @click="testDirectInit" class="bg-purple-500 text-white px-4 py-2 rounded mt-2">
            直接初期化をテスト
          </button>
          <pre class="mt-2 p-2 bg-gray-100 rounded text-sm">{{ directInitStatus }}</pre>
        </div>
  
        <div class="p-4 border rounded">
          <h2 class="font-semibold">4. 実際の登録テスト</h2>
          <div class="flex gap-2 mt-2">
            <input 
              v-model="testEmail" 
              type="email" 
              placeholder="test@example.com"
              class="border px-3 py-2 rounded"
            >
            <input 
              v-model="testPassword" 
              type="password" 
              placeholder="password"
              class="border px-3 py-2 rounded"
            >
            <button @click="testRegister" class="bg-red-500 text-white px-4 py-2 rounded">
              登録テスト
            </button>
          </div>
          <pre class="mt-2 p-2 bg-gray-100 rounded text-sm">{{ registerStatus }}</pre>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { initializeApp } from 'firebase/app'
  import { getAuth, createUserWithEmailAndPassword } from 'firebase/auth'
  
  const firebaseStatus = ref('未チェック')
  const authStatus = ref('未チェック')
  const directInitStatus = ref('未チェック')
  const registerStatus = ref('未チェック')
  
  const testEmail = ref('test@example.com')
  const testPassword = ref('password123')
  
  // 1. Firebase インスタンス確認
  const checkFirebaseInstance = () => {
    try {
      const { $firebase, $auth } = useNuxtApp()
      
      const status = {
        'Nuxtプラグイン利用可能': !!useNuxtApp,
        'Firebase アプリ': !!$firebase,
        'Auth インスタンス': !!$auth,
        'Firebase アプリ名': $firebase?.name || 'なし',
        'Auth 設定': $auth?.config || 'なし'
      }
      
      firebaseStatus.value = JSON.stringify(status, null, 2)
    } catch (error) {
      firebaseStatus.value = `エラー: ${error.message}`
    }
  }
  
  // 2. 認証テスト
  const testAuth = () => {
    try {
      const { $auth } = useNuxtApp()
      
      const status = {
        'Auth インスタンス': !!$auth,
        'Current User': $auth?.currentUser || 'null',
        'Auth Ready': !!$auth?.app,
        'App Name': $auth?.app?.name || 'なし'
      }
      
      authStatus.value = JSON.stringify(status, null, 2)
    } catch (error) {
      authStatus.value = `エラー: ${error.message}`
    }
  }
  
  // 3. 直接初期化テスト
  const testDirectInit = async () => {
    try {
      const firebaseConfig = {
        apiKey: "AIzaSyBqL0fYsO78cm6jSigynjZpaVWoM593PBs",
        authDomain: "recipe-app-5beae.firebaseapp.com",
        projectId: "recipe-app-5beae",
        storageBucket: "recipe-app-5beae.firebasestorage.app",
        messagingSenderId: "358407678834",
        appId: "1:358407678834:web:6c232721462d59b4084beb"
      }
  
      const app = initializeApp(firebaseConfig, 'debug-test')
      const auth = getAuth(app)
      
      const status = {
        'Firebase App 初期化': !!app,
        'Auth 初期化': !!auth,
        'App Name': app.name,
        'Auth App Name': auth.app.name,
        'Config Check': !!auth.config
      }
      
      directInitStatus.value = JSON.stringify(status, null, 2)
    } catch (error) {
      directInitStatus.value = `エラー: ${error.message}\n\nスタック: ${error.stack}`
    }
  }
  
  // 4. 実際の登録テスト
  const testRegister = async () => {
    try {
      const { $auth } = useNuxtApp()
      
      if (!$auth) {
        registerStatus.value = 'Auth インスタンスが利用できません'
        return
      }
  
      const userCredential = await createUserWithEmailAndPassword(
        $auth, 
        testEmail.value, 
        testPassword.value
      )
      
      registerStatus.value = `成功! ユーザーID: ${userCredential.user.uid}`
    } catch (error) {
      registerStatus.value = `エラー: ${error.code}\nメッセージ: ${error.message}`
    }
  }
  
  // ページ読み込み時に自動チェック
  onMounted(() => {
    setTimeout(() => {
      checkFirebaseInstance()
      testAuth()
    }, 1000)
  })
  </script>
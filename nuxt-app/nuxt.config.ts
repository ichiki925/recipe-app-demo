export default defineNuxtConfig({
  compatibilityDate: '2025-05-15',

  css: [
    '@/assets/css/sanitize.css',
  ],

  app: {
    head: {
      link: [
        // ↓ この3行を貼る（画面のEmbed codeと同じ）
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Italianno&display=swap' },

        // 本文でNoto Sans JPを使う場合だけ（任意）
        // { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500&display=swap' }
      ]
    }
  },

  modules: [
    '@nuxt/eslint',
    '@nuxt/icon',
    '@nuxt/image',
    '@nuxt/ui'
  ],

  plugins: [
    '~/plugins/firebase.client.js'
  ],

  devServer: {
    host: '0.0.0.0',
    port: 3000
  },

  // 開発専用プロキシ（本番では使わない）
  // nitro: {
  //   devProxy: {
  //     '/api': {
  //       target: 'http://nginx:80',
  //       changeOrigin: true,
  //     }
  //   }
  // },

  runtimeConfig: {
    public: {
      apiBaseUrl: process.env.API_BASE_URL || 'http://localhost',

      firebaseApiKey: process.env.NUXT_PUBLIC_FIREBASE_API_KEY,
      firebaseAuthDomain: process.env.NUXT_PUBLIC_FIREBASE_AUTH_DOMAIN,
      firebaseProjectId: process.env.NUXT_PUBLIC_FIREBASE_PROJECT_ID,
      firebaseStorageBucket: process.env.NUXT_PUBLIC_FIREBASE_STORAGE_BUCKET,
      firebaseMessagingSenderId: process.env.NUXT_PUBLIC_FIREBASE_MESSAGING_SENDER_ID,
      firebaseAppId: process.env.NUXT_PUBLIC_FIREBASE_APP_ID,
    }
  }
})
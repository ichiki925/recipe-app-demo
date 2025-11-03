// plugins/firebase.client.js - 新しいプロジェクト設定
import { initializeApp } from 'firebase/app'
import { getAuth } from 'firebase/auth'
import { getStorage } from 'firebase/storage'

export default defineNuxtPlugin(() => {
    const config = useRuntimeConfig()

    // 新しいFirebaseプロジェクトの設定
    const firebaseConfig = {
        apiKey: config.public.firebaseApiKey,
        authDomain: config.public.firebaseAuthDomain,
        projectId: config.public.firebaseProjectId,
        storageBucket: config.public.firebaseStorageBucket,
        messagingSenderId: config.public.firebaseMessagingSenderId,
        appId: config.public.firebaseAppId
    }

    const app = initializeApp(firebaseConfig)
    const auth = getAuth(app)
    const storage = getStorage(app)

    // グローバルに firebase を設定
    if (process.client) {
        window.firebase = { auth: () => auth, storage: () => storage }
    }

    return {
        provide: {
            auth,
            storage
        }
    }
})
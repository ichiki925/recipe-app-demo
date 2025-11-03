import { getStorage, ref as storageRef, uploadBytes, getDownloadURL, deleteObject } from 'firebase/storage'

/**
 * プロフィール画像をFirebase Storageにアップロード
 * @param {File} file - アップロードするファイル
 * @param {string} userId - ユーザーID (Firebase UID)
 * @returns {Object} - { url: string, path: string }
 */
export const uploadAvatarToFirebase = async (file, userId) => {
    try {
        if (!file || !userId) {
            throw new Error('ファイルまたはユーザーIDが指定されていません')
        }

        const storage = getStorage()
        const timestamp = Date.now()
        const cleanFileName = file.name.replace(/[^a-zA-Z0-9.-]/g, '_')
        const fileName = `avatars/${userId}_${timestamp}_${cleanFileName}`
        const avatarRef = storageRef(storage, fileName)

        console.log('📤 Firebase Storage - アバター画像アップロード開始:', {
            fileName,
            fileSize: file.size,
            fileType: file.type
        })

        // ファイルをアップロード
        const snapshot = await uploadBytes(avatarRef, file)
        const downloadURL = await getDownloadURL(snapshot.ref)

        console.log('✅ Firebase Storage - アバター画像アップロード完了:', {
            fileName,
            downloadURL
        })

        return {
            url: downloadURL,
            path: fileName
        }
    } catch (error) {
        console.error('❌ Firebase Storage - アバター画像アップロードエラー:', error)
        throw new Error(`画像のアップロードに失敗しました: ${error.message}`)
    }
}

/**
 * Firebase Storageからプロフィール画像を削除
 * @param {string} imagePath - 削除するファイルのパス
 */
export const deleteAvatarFromFirebase = async (imagePath) => {
    try {
        if (!imagePath) {
            console.log('🔍 削除対象のパスが指定されていません')
            return
        }

        const storage = getStorage()
        const avatarRef = storageRef(storage, imagePath)

        await deleteObject(avatarRef)
        console.log('🗑️ Firebase Storage - アバター画像削除完了:', imagePath)
    } catch (error) {
        if (error.code === 'storage/object-not-found') {
            console.log('📝 Firebase Storage - ファイルが見つかりません（既に削除済み）:', imagePath)
        } else {
            console.error('❌ Firebase Storage - アバター画像削除エラー:', error)
            // 削除エラーは致命的ではないので、例外を再スローしない
        }
    }
}

/**
 * Firebase Storage URLからファイルパスを抽出
 * @param {string} firebaseUrl - Firebase Storage URL
 * @returns {string} - ファイルパス
 */
export const extractPathFromFirebaseUrl = (firebaseUrl) => {
    try {
        if (!firebaseUrl || !firebaseUrl.includes('firebasestorage.googleapis.com')) {
            return null
        }

        const parsedUrl = new URL(firebaseUrl)
        const pathMatch = parsedUrl.pathname.match(/\/o\/(.+)/)

        if (pathMatch && pathMatch[1]) {
            return decodeURIComponent(pathMatch[1])
        }

        return null
    } catch (error) {
        console.error('❌ Firebase URL パース エラー:', error)
        return null
    }
}
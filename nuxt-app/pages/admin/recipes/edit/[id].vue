<template>
    <div class="recipe-edit-container">
        <div class="image-preview" @click="triggerImageInput">
            <div v-if="!imagePreview" class="no-image-placeholder">
                <div class="no-image-text">No Image</div>
            </div>
            <img
                v-if="imagePreview"
                :src="imagePreview"
                alt="プレビュー"
                class="preview-image"
                @error="handleImageError"
                @load="handleImageLoad"
            />
        </div>

        <input
            type="file"
            ref="imageInput"
            style="display: none"
            accept="image/*"
            @change="previewImage"
        />

        <form class="recipe-form" @submit.prevent="submitRecipe">
            <h2>Edit Recipe</h2>

            <div v-if="errors.length > 0" class="error-messages">
                <div v-for="error in errors" :key="error" class="error-message">
                    {{ error }}
                </div>
            </div>

            <div v-if="successMessage" class="success-message">
                {{ successMessage }}
            </div>

            <label>料理名</label>
            <input type="text" v-model="form.title" class="recipe-title" required />

            <label>ジャンル</label>
            <input type="text" v-model="form.genre" class="recipe-title" />

            <label>人数</label>
            <select v-model="form.servings" class="servings-input" required>
                <option value="">選択してください</option>
                <option value="1人分">1人分</option>
                <option value="2人分">2人分</option>
                <option value="3人分">3人分</option>
                <option value="4人分">4人分</option>
                <option value="5人分以上">5人分以上</option>
            </select>

            <label>材料</label>
            <div id="ingredients">
                <div
                    class="ingredient-row"
                    v-for="(ingredient, index) in form.ingredients"
                    :key="index"
                >
                    <input
                        type="text"
                        v-model="ingredient.name"
                        class="ingredient-name"
                        placeholder="材料名"
                    />
                    <input
                        type="text"
                        v-model="ingredient.qty"
                        class="ingredient-qty"
                        placeholder="分量"
                    />
                </div>
            </div>

            <label>作り方</label>
            <textarea
                v-model="form.instructions"
                class="auto-resize"
                @input="resizeTextarea"
                placeholder="作り方を入力してください"
                required
            ></textarea>

            <div class="button-container">
                <button
                    type="button"
                    class="save-button"
                    @click="saveRecipe"
                    :disabled="isSaving"
                >
                    {{ isSaving ? '保存中...' : '下書き保存' }}
                </button>
                <button
                    type="submit"
                    class="submit-button"
                    :disabled="isSubmitting"
                >
                    {{ isSubmitting ? '更新中...' : 'レシピを更新' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
definePageMeta({
    layout: 'admin',
    ssr: false
})

import { ref, reactive, watch, onMounted, nextTick  } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { getStorage, ref as storageRef, uploadBytes, getDownloadURL, deleteObject } from 'firebase/storage'

const { getAuth, putAuth, postAuth } = useApi()
const { initAuth, isAdmin } = useAuth()

const router = useRouter()
const route = useRoute()
const config = useRuntimeConfig()

const form = reactive({
    title: '',
    genre: '',
    servings: '',
    ingredients: [{ name: '', qty: '' }],
    instructions: ''
})

const imageInput = ref(null)
const imagePreview = ref('')
const selectedFile = ref(null)
const errors = ref([])
const successMessage = ref('')
const isSubmitting = ref(false)
const isSaving = ref(false)
const currentEditingRecipe = ref(null)
const originalRecipe = ref(null)
const originalRecipeId = ref(null)
const isLoading = ref(true)

const uploadRecipeImage = async (file) => {
    const { $auth } = useNuxtApp()
    const currentUser = $auth.currentUser
    if (!currentUser) throw new Error('認証が必要です')

    const storage = getStorage()

    const y = new Date().getFullYear()
    const m = String(new Date().getMonth() + 1).padStart(2, '0')
    const fileName = `${Date.now()}_${file.name}`
    const finalPath = `recipes/${currentUser.uid}/${y}/${m}/${fileName}`
    const imageRef = storageRef(storage, finalPath)

    const snapshot = await uploadBytes(imageRef, file)
    const downloadURL = await getDownloadURL(snapshot.ref)

    return {
        url: downloadURL,
        path: finalPath
    }
}


const deleteTempImage = async (tempPath) => {
    try {
        const storage = getStorage()
        const imageRef = storageRef(storage, tempPath)
        await deleteObject(imageRef)
        console.log('一時保存画像を削除:', tempPath)
    } catch (error) {
        if (error.code === 'storage/object-not-found') {
            console.log('一時保存画像は既に削除済み:', tempPath)
        } else {
            console.error('一時保存画像削除エラー:', error)
        }
    }
}

const handleImageError = (event) => {
    console.error('画像読み込みエラー:', event.target.src)
    if (event.target.src !== '/images/no-image.png') {
        event.target.src = '/images/no-image.png'
    }
}

const getImageUrl = (imageUrl) => {
    if (!imageUrl || imageUrl === '/images/no-image.png' || imageUrl.trim() === '') {
        return ''
    }

    // Firebase Storage URLの場合はそのまま返す
    if (imageUrl.includes('firebasestorage.googleapis.com') || 
        imageUrl.includes('firebasestorage.app')) {
        return imageUrl
    }

    // ローカルストレージの場合
    if (imageUrl.startsWith('/storage/')) {
        return `${config.public.apiBaseUrl}${imageUrl}`
    }

    // data:URLまたは絶対URLの場合はそのまま
    if (imageUrl.startsWith('data:') || imageUrl.startsWith('http')) {
        return imageUrl
    }

    return imageUrl
}

const fetchOriginalRecipe = async () => {
    try {
        const recipeId = route.params.id
        const data = await getAuth(`admin/recipes/${recipeId}`)

        const recipe = data.data
        originalRecipe.value = recipe
        originalRecipeId.value = recipe.id
        loadRecipeToForm(recipe)

    } catch (error) {
        console.error('レシピ取得エラー:', error)
        errors.value.push('レシピの取得に失敗しました')
    } finally {
        isLoading.value = false
    }
}

const loadRecipeToForm = (recipe) => {
    form.title = recipe.title || ''
    form.genre = recipe.genre || ''
    form.servings = recipe.servings || ''
    form.instructions = recipe.instructions || ''

    if (recipe.ingredients) {
        if (Array.isArray(recipe.ingredients)) {
            form.ingredients = recipe.ingredients.length > 0 ? recipe.ingredients : [{ name: '', qty: '' }]
        } else {
            const ingredientLines = recipe.ingredients.split('\n').filter(line => line.trim())
            form.ingredients = ingredientLines.map(line => {
                const lastSpaceIndex = line.lastIndexOf(' ')
                if (lastSpaceIndex !== -1) {
                    return {
                        name: line.substring(0, lastSpaceIndex).trim(),
                        qty: line.substring(lastSpaceIndex + 1).trim()
                    }
                }
                return { name: line.trim(), qty: '' }
            })
        }
    }

    if (form.ingredients.length === 0) {
        form.ingredients = [{ name: '', qty: '' }]
    }

    if (recipe.imagePreview) {
        imagePreview.value = recipe.imagePreview
    } else {
        const imageUrl = recipe.image_full_url || recipe.image_url
        imagePreview.value = getImageUrl(imageUrl)
    }
}

const saveRecipe = async () => {
    isSaving.value = true

    try {
        const draftId = `edit_${originalRecipeId.value}`

        const recipeData = {
            id: draftId,
            title: form.title,
            genre: form.genre,
            servings: form.servings,
            ingredients: [...form.ingredients],
            instructions: form.instructions,
            hasImage: !!selectedFile.value,
            savedAt: new Date().toISOString(),
            isEditDraft: true,
            originalRecipeId: originalRecipeId.value
        }

        // 画像がある場合はFirebase Storageに一時保存
        if (selectedFile.value?.file) {
            try {
                const tempImageData = await uploadRecipeImage(selectedFile.value.file)
                recipeData.tempImageUrl = tempImageData.url
                recipeData.tempImagePath = tempImageData.path
            } catch (error) {
                console.error('画像一時保存エラー:', error)
                recipeData.hasImage = false
            }
        } else if (selectedFile.value?.isTemp) {
            // 既に一時保存済みの画像の場合
            recipeData.tempImageUrl = selectedFile.value.tempImageUrl
            recipeData.tempImagePath = selectedFile.value.tempImagePath
        } else if (imagePreview.value && !selectedFile.value) {
            // 元画像のまま変更なしの場合
            recipeData.originalImageUrl = imagePreview.value
            recipeData.hasImage = true
        }

        let savedRecipes = []
        try {
            const saved = localStorage.getItem('savedRecipes')
            if (saved) {
                savedRecipes = JSON.parse(saved)
            }
        } catch (error) {
            console.error('保存レシピの読み込みエラー:', error)
        }

        // 既存の編集下書きがあれば古い一時画像を削除
        const existingIndex = savedRecipes.findIndex(r => r.id === recipeData.id)
        if (existingIndex !== -1) {
            const oldRecipe = savedRecipes[existingIndex]
            if (oldRecipe.tempImagePath && oldRecipe.tempImagePath !== recipeData.tempImagePath) {
                await deleteTempImage(oldRecipe.tempImagePath)
            }
            savedRecipes[existingIndex] = recipeData
        } else {
            savedRecipes.unshift(recipeData)
        }

        if (savedRecipes.length > 10) {
            const removedRecipes = savedRecipes.slice(10)
            for (const recipe of removedRecipes) {
                if (recipe.tempImagePath) {
                    await deleteTempImage(recipe.tempImagePath)
                }
            }
            savedRecipes = savedRecipes.slice(0, 10)
        }

        localStorage.setItem('savedRecipes', JSON.stringify(savedRecipes))
        currentEditingRecipe.value = recipeData

        setTimeout(() => {
            router.push('/admin/dashboard')
        }, 1500)
    } catch (error) {
        console.error('保存エラー:', error)
        errors.value.push('保存に失敗しました')
    } finally {
        isSaving.value = false
    }
}

const loadDraftIfExists = () => {
    try {
        const saved = localStorage.getItem('savedRecipes')
        if (saved) {
            const savedRecipes = JSON.parse(saved)
            const draftId = `edit_${originalRecipeId.value}`
            const existingDraft = savedRecipes.find(r => r.id === draftId)

            if (existingDraft) {
                loadRecipeToForm(existingDraft)
                currentEditingRecipe.value = existingDraft

                // 画像復元処理を追加
                if (existingDraft.hasImage) {
                    if (existingDraft.tempImageUrl) {
                        // 一時保存画像の場合
                        imagePreview.value = existingDraft.tempImageUrl
                        selectedFile.value = {
                            tempImageUrl: existingDraft.tempImageUrl,
                            tempImagePath: existingDraft.tempImagePath,
                            isTemp: true
                        }
                    } else if (existingDraft.originalImageUrl) {
                        // 元画像そのままの場合
                        imagePreview.value = existingDraft.originalImageUrl
                        selectedFile.value = null
                    }
                }
            }
        }
    } catch (error) {
        console.error('下書き読み込みエラー:', error)
    }
}

const triggerImageInput = () => {
    imageInput.value?.click()
}

const previewImage = async (event) => {
    const file = event.target.files[0]
    if (!file) return

    if (file.size > 5 * 1024 * 1024) {
        errors.value.push('ファイルサイズは5MB以下にしてください')
        return
    }

    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']
    if (!allowedTypes.includes(file.type)) {
        errors.value.push('JPEG、PNG、WebP形式の画像のみアップロード可能です')
        return
    }

    selectedFile.value = {
        file,
        serverUpload: true
    }

    const reader = new FileReader()
    reader.onload = (e) => {
        imagePreview.value = e.target.result
    }
    reader.onerror = () => {
        console.error('画像読み込みエラー')
        errors.value.push('画像の読み込みに失敗しました')
    }
    reader.readAsDataURL(file)
}

watch(
    () => form.ingredients,
    (newIngredients) => {
        const last = newIngredients[newIngredients.length - 1]
        if (last && (last.name || last.qty)) {
            form.ingredients.push({ name: '', qty: '' })
        }
    },
    { deep: true }
)

const resizeTextarea = (event) => {
    const textarea = event.target
    textarea.style.height = 'auto'
    textarea.style.height = Math.max(80, textarea.scrollHeight) + 'px'
}

const formatIngredients = () => {
    return form.ingredients
        .filter(ingredient => ingredient.name.trim() || ingredient.qty.trim())
        .map(ingredient => `${ingredient.name.trim()} ${ingredient.qty.trim()}`)
        .join('\n')
}

const submitRecipe = async () => {
    errors.value = []
    successMessage.value = ''
    isSubmitting.value = true

    try {
        // バリデーション
        if (!form.title.trim()) {
            errors.value.push('料理名は必須です')
        }
        if (!form.servings) {
            errors.value.push('人数を選択してください')
        }
        if (!form.instructions.trim()) {
            errors.value.push('作り方は必須です')
        }

        // 古い一時画像を削除（修正版）
        const currentEditingId = currentEditingRecipe.value?.id
        if (currentEditingId) {
            try {
                const saved = localStorage.getItem('savedRecipes')
                if (saved) {
                    const savedRecipes = JSON.parse(saved)
                    const existingDraft = savedRecipes.find(r => r.id === currentEditingId)
                    if (existingDraft?.tempImagePath && 
                        existingDraft.tempImagePath !== selectedFile.value?.tempImagePath) {
                        await deleteTempImage(existingDraft.tempImagePath)
                    }
                }
            } catch (error) {
                console.error('古い一時画像削除エラー（無視）:', error)
            }
        }

        const ingredientsText = formatIngredients()
        if (!ingredientsText) {
            errors.value.push('材料は必須です')
        }

        if (errors.value.length > 0) {
            isSubmitting.value = false
            return
        }

        const formData = new FormData()
        formData.append('_method', 'PUT')
        formData.append('title', form.title.trim())
        formData.append('genre', form.genre || '')
        formData.append('servings', form.servings.toString())
        formData.append('ingredients', ingredientsText)
        formData.append('instructions', form.instructions.trim())

        // 画像処理 - 条件を明確化
        if (selectedFile.value?.file instanceof File) {
            const { url } = await uploadRecipeImage(selectedFile.value.file)
            formData.append('temp_image_url', url)
        } else if (selectedFile.value?.isTemp && selectedFile.value?.tempImageUrl) {
            formData.append('temp_image_url', selectedFile.value.tempImageUrl)
        }


        const recipeId = route.params.id
        await postAuth(`admin/recipes/${recipeId}`, formData)

        successMessage.value = 'レシピが更新されました'

        // 更新成功時の一時保存画像削除処理を追加
        if (selectedFile.value?.isTemp && selectedFile.value?.tempImagePath) {
            try {
                await deleteTempImage(selectedFile.value.tempImagePath)
            } catch (error) {
                console.error('一時保存画像削除エラー:', error)
            }
        }

        // 下書きを削除
        if (currentEditingId) {
            try {
                const saved = localStorage.getItem('savedRecipes')
                if (saved) {
                    let savedRecipes = JSON.parse(saved)
                    savedRecipes = savedRecipes.filter(r => r.id !== currentEditingId)
                    localStorage.setItem('savedRecipes', JSON.stringify(savedRecipes))
                }
            } catch (error) {
                console.error('下書き削除エラー:', error)
            }
        }

        currentEditingRecipe.value = null

        setTimeout(() => {
            router.push(`/admin/recipes/show/${recipeId}`)
        }, 1500)

    } catch (error) {
        console.error('API error:', error)
        errors.value = [`レシピの更新に失敗しました: ${error.message}`]
    } finally {
        isSubmitting.value = false
    }
}

onMounted(async () => {
    await initAuth()
    if (!isAdmin.value) {
        return navigateTo('/admin/login')
    }
    await fetchOriginalRecipe()

    // URLパラメータから下書きIDを取得
    const draftId = route.query.draft

    if (draftId) {
        // パラメータがある場合は該当する下書きを読み込み
        const saved = localStorage.getItem('savedRecipes')
        if (saved) {
            const savedRecipes = JSON.parse(saved)
            const existingDraft = savedRecipes.find(r => r.id === draftId)
            if (existingDraft) {
                loadRecipeToForm(existingDraft)
                currentEditingRecipe.value = existingDraft

                // 画像復元処理を修正
                if (existingDraft.hasImage) {
                    if (existingDraft.tempImageUrl) {
                        // 一時保存画像の場合
                        imagePreview.value = existingDraft.tempImageUrl
                        selectedFile.value = {
                            tempImageUrl: existingDraft.tempImageUrl,
                            tempImagePath: existingDraft.tempImagePath,
                            isTemp: true
                        }
                    } else if (existingDraft.originalImageUrl) {
                        // 元画像そのままの場合
                        imagePreview.value = existingDraft.originalImageUrl
                        selectedFile.value = null
                    }
                }
            }
        }
    }

    // パラメータがない場合は通常の下書き検索
    loadDraftIfExists()
})
</script>

<style scoped>
@import '@/assets/css/common.css';

body {
    margin: 0;
    font-family: sans-serif;
}

.recipe-edit-container {
    display: flex;
    gap: 40px;
    justify-content: center;
    align-items: flex-start;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

.image-preview {
    width: 300px;
    height: 300px;
    background-color: #f0f0f0;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 80px;
    cursor: pointer;
    overflow: hidden;
    position: relative;
    flex-shrink: 0;
}

.no-image-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    color: #999;
}

.no-image-text {
    font-size: 18px;
    font-weight: 500;
}

.preview-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 12px;
}

.recipe-form {
    width: 400px;
    flex-shrink: 0;
}

.recipe-form h2 {
    margin-bottom: 20px;
    text-align: center;
    font-family: 'Italianno', cursive;
    font-size: 2rem;
}

.recipe-form label {
    display: block;
    font-weight: bold;
    margin-top: 15px;
    margin-bottom: 5px;
}

.recipe-title {
    width: 100%;
    padding: 10px;
    font-size: 16px;
    border: 1px solid #aaa;
    border-radius: 6px;
    box-sizing: border-box;
}

.recipe-title:focus {
    outline: none;
    border: 1px solid #aaa;
}

.recipe-form .ingredient-row {
    display: flex;
    gap: 0px;
    margin-bottom: 10px;
}

.recipe-form .ingredient-name {
    flex: 2;
    border: 1px solid #aaa !important;
    border-right: none !important;
    border-radius: 6px 0 0 6px !important;
    padding: 10px !important;
    box-sizing: border-box;
}

.recipe-form .ingredient-name:focus {
    outline: none;
    border: 1px solid #aaa !important;
    border-right: none !important;
}

.recipe-form .ingredient-qty {
    flex: 1;
    border: 1px solid #aaa !important;
    border-left: none !important;
    border-radius: 0 6px 6px 0 !important;
    padding: 10px !important;
    box-sizing: border-box;
}

.recipe-form .ingredient-qty:focus {
    outline: none;
    border: 1px solid #aaa !important;
    border-left: none !important;
}

.servings-input {
    width: 150px;
    padding: 8px;
    font-size: 14px;
    margin-bottom: 10px;
    border: 1px solid #aaa;
    border-radius: 6px;
}

.servings-input:focus {
    outline: none;
    border: 1px solid #aaa;
}

.auto-resize {
    width: 100%;
    min-height: 80px;
    resize: none;
    padding: 10px;
    border: 1px solid #aaa;
    border-radius: 6px;
    font-size: 14px;
    box-sizing: border-box;
    overflow: auto;
}

.auto-resize:focus {
    outline: none;
    border: 1px solid #aaa;
}

.button-container {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.save-button {
    flex: 1;
    padding: 12px;
    background-color: #59d4d4fc;
    color: white;
    border: none;
    font-weight: bold;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}

.save-button:hover:not(:disabled) {
    background-color: #59b9d4fc;
}

.save-button:active:not(:disabled) {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(89, 212, 212, 0.3);
}

.save-button:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.submit-button {
    flex: 1;
    padding: 12px;
    background-color: #ffbf00;
    color: white;
    border: none;
    font-weight: bold;
    cursor: pointer;
    border-radius: 6px;
    transition: background-color 0.2s;
}

.submit-button:hover:not(:disabled) {
    background-color: #ff9d00;
}

.submit-button:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
}

.error-messages {
    margin-bottom: 20px;
}

.error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 6px;
    border: 1px solid #f5c6cb;
}

.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 6px;
    border: 1px solid #c3e6cb;
}

input::placeholder,
textarea::placeholder {
    color: #ccc !important;
    opacity: 1 !important;
}

@media screen and (max-width: 768px) {
    .recipe-edit-container {
        flex-direction: column;
        align-items: center;
        gap: 20px;
        padding: 15px;
    }

    .image-preview {
        width: 100%;
        max-width: 280px;
        height: 280px;
        margin-top: 0;
    }

    .recipe-form {
        width: 100%;
        max-width: 400px;
    }

    .ingredient-row {
        flex-direction: column;
        gap: 8px;
    }

    .recipe-form .ingredient-name,
    .recipe-form .ingredient-qty {
        border: 1px solid #aaa !important;
        border-radius: 6px !important;
        flex: none;
        margin-bottom: 8px;
    }

    .recipe-form .ingredient-name:focus,
    .recipe-form .ingredient-qty:focus {
        outline: none;
        border: 1px solid #aaa !important;
    }

    .recipe-form .ingredient-qty {
        margin-bottom: 0;
    }

    .servings-input {
        width: 100%;
    }

    .auto-resize {
        min-height: 100px;
    }

    .button-container {
        flex-direction: column;
        gap: 8px;
    }
}
</style>
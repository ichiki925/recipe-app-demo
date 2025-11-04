<template>
    <div class="recipe-detail">
        <div v-if="loading" class="loading">
            レシピを読み込み中...
        </div>

        <div v-else-if="error" class="error">
            {{ error }}
        </div>

        <div v-else-if="recipe" class="recipe-page">
            <aside class="admin-sidebar">
                <div class="admin-actions">
                    <button @click="goToRecipeList" class="action-btn back-btn">
                        <i class="fas fa-arrow-left"></i>
                        レシピ一覧
                    </button>

                    <button @click="editRecipe" class="action-btn edit-btn">
                        <i class="fas fa-edit"></i>
                        編集
                    </button>
                    <button @click="confirmDelete" class="action-btn delete-btn">
                        <i class="fas fa-trash"></i>
                        削除
                    </button>
                </div>

                <div class="admin-info">
                    <h3>管理者情報</h3>
                    <div class="info-item">
                        <span class="label">レシピID:</span>
                        <span class="value">{{ recipe.id }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">いいね数:</span>
                        <span class="value">{{ recipe.likes_count || 0 }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">コメント数:</span>
                        <span class="value">{{ recipe.comments?.length || 0 }}</span>
                    </div>
                </div>
            </aside>

            <div class="recipe-create-container">
                <div class="left-column">
                    <h2 class="recipe-title-heading">{{ recipe.title }}</h2>

                    <RecipeImagePreview
                        :image-url="getImageUrl(recipe)"
                        :alt-text="recipe.title"
                        @image-error="handleImageError"
                    />

                    <RecipeComments
                        :comments="recipe.comments || []"
                        :is-admin="true"
                    />

                    <RecipeLikeButton
                        :is-liked="false"
                        :like-count="recipe.likes_count || 0"
                        :is-admin="true"
                    />
                </div>

                <div class="right-column">
                    <div class="recipe-form">
                        <label>ジャンル</label>
                        <div class="recipe-info">{{ recipe.genre || '未設定' }}</div>

                        <RecipeIngredients
                            :ingredients="recipe.ingredients_array || []"
                            :servings="recipe.servings"
                        />

                        <RecipeInstructions
                            :instructions="recipe.instructions_array || recipe.instructions"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showDeleteModal" class="modal-overlay" @click="showDeleteModal = false">
            <div class="modal-content" @click.stop>
                <h3>レシピの削除</h3>
                <p>「{{ recipe?.title }}」を削除しますか？</p>
                <p class="warning">この操作は取り消すことができません。</p>
                <div class="modal-actions">
                    <button @click="showDeleteModal = false" class="cancel-btn">キャンセル</button>
                    <button @click="deleteRecipe" class="confirm-delete-btn" :disabled="deleting">
                        {{ deleting ? '削除中...' : '削除する' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
definePageMeta({
    layout: 'admin',
    ssr: false
})

import { ref, onMounted } from 'vue'
import { useRoute, useRouter, useHead } from '#app'
import RecipeImagePreview from '~/components/RecipeImagePreview.vue'
import RecipeComments from '~/components/RecipeComments.vue'
import RecipeLikeButton from '~/components/RecipeLikeButton.vue'
import RecipeIngredients from '~/components/RecipeIngredients.vue'
import RecipeInstructions from '~/components/RecipeInstructions.vue'

const { getAuth, delAuth } = useApi()
const { initAuth, isAdmin } = useAuth()


useHead({
    link: [
        {
            rel: 'stylesheet',
            href: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'
        }
        ]
    })

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()

const recipe = ref(null)
const loading = ref(true)
const error = ref('')
const showDeleteModal = ref(false)
const deleting = ref(false)

const recipeId = route.params.id

const getImageUrl = (recipe) => {
    if (!recipe) return '/images/no-image.png'
    if (recipe.image_full_url) {
        return recipe.image_full_url
    }
    if (recipe.image_url) {
        if (recipe.image_url.includes('firebasestorage.googleapis.com') ||
            recipe.image_url.includes('firebasestorage.app')) {
            return recipe.image_url
        }
        if (recipe.image_url.startsWith('/storage/')) {
            return `${config.public.apiBaseUrl}${recipe.image_url}`
        }
        return recipe.image_url
    }
    return '/images/no-image.png'
}

const handleImageError = (event) => {
    console.error('画像読み込みエラー:', {
        recipe_id: recipe.value?.id,
        recipe_title: recipe.value?.title,
        image_url: recipe.value?.image_url,
        image_full_url: recipe.value?.image_full_url,
        attempted_src: event.target.src
    })

    if (event.target.src !== '/images/no-image.png') {
        event.target.src = '/images/no-image.png'
    }
}

const fetchRecipe = async () => {
    loading.value = true
    error.value = ''
    recipe.value = null

    try {
        const data = await getAuth(`admin/recipes/${recipeId}`)
        console.log('ADMIN API raw:', (data?.data?.comments || []).map(c => ({
            id: c.id,
            name: c.user?.name,
            url: c.user?.avatar_url,
            path: c.user?.avatar_path
        })))

        if (data.status === 'success' && data.data) {
            // コメントデータを変換
            const convertedComments = (data.data.comments || []).map(comment => ({
                id: comment.id,
                user: {
                    name: comment.user?.name || 'ユーザー',
                    avatar_path: comment.user?.avatar_url || null
                },
                body: comment.content,
                createdAt: comment.created_at
            }))
            
            recipe.value = {
                ...data.data,
                comments: convertedComments  // 変換したコメントを使用
            }
            console.log('ADMIN converted:', recipe.value.comments.map(c => ({
                id: c.id,
                name: c.user?.name,
                avatar: c.user?.avatar_path
            })))
        } else {
            throw new Error('レスポンスデータの形式が不正です')
        }

    } catch (err) {
        // エラー処理は既存のまま
    } finally {
        loading.value = false
    }
}

const goToRecipeList = () => {
    router.push('/admin/recipes')
}

const editRecipe = () => {
    router.push(`/admin/recipes/edit/${recipeId}`)
}

const confirmDelete = () => {
    showDeleteModal.value = true
}

const deleteRecipe = async () => {
    deleting.value = true

    try {
        await delAuth(`admin/recipes/${recipeId}`)
        alert('レシピが削除されました')
        await router.push('/admin/recipes')

    } catch (err) {
        console.error('削除エラー:', err)

        let errorMessage = '削除に失敗しました。もう一度お試しください。'

        if (err.status === 401) {
            errorMessage = '認証が無効です。再ログインしてください。'
        } else if (err.status === 404) {
            errorMessage = 'レシピが見つかりません。'
        } else if (err.status === 403) {
            errorMessage = 'このレシピを削除する権限がありません。'
        } else if (err.status === 500) {
            errorMessage = 'サーバーエラーが発生しました。しばらく時間をおいて再度お試しください。'
        }

        alert(errorMessage)
    } finally {
        deleting.value = false
        showDeleteModal.value = false
    }
}

onMounted(async () => {
    await initAuth()
    if (!isAdmin.value) {
        return navigateTo('/admin/login')
    }
    await fetchRecipe()
})
</script>

<style scoped>
    @import '@/assets/css/common.css';

    .recipe-detail {
        padding: 20px;
    }

    .recipe-page {
        display: flex;
        gap: 30px;
        max-width: 1400px;
        margin: 0 auto;
    }

    .admin-sidebar {
        width: 300px;
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        height: fit-content;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        flex-shrink: 0;
    }

    .admin-actions {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 30px;
    }

    .action-btn {
        width: 100%;
        padding: 12px 16px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background-color 0.2s;
        font-size: 14px;
    }

    .back-btn {
        background-color: #ddd;
        color: #000;
    }

    .back-btn:hover {
        background-color: #ccc;
    }

    .edit-btn {
        background-color: #fbc559f6;
        color: white;
    }

    .edit-btn:hover {
        background-color: #f6ad1af6;
    }

    .delete-btn {
        background-color: #ec8892f5;
        color: white;
    }

    .delete-btn:hover {
        background-color: #c82333;
    }

    .admin-info {
        border-top: 1px solid #e9ecef;
        padding-top: 20px;
    }

    .admin-info h3 {
        font-size: 16px;
        margin-bottom: 15px;
        color: #333;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .info-item .label {
        color: #666;
    }

    .info-item .value {
        font-weight: 500;
        color: #333;
    }

    .loading, .error {
        text-align: center;
        padding: 40px;
        font-size: 16px;
        color: #666;
    }

    .error {
        color: #dc3545;
    }

    .recipe-create-container {
        display: flex;
        gap: 40px;
        justify-content: center;
        align-items: flex-start;
        flex: 1;
    }

    .left-column {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 300px;
        min-width: 300px;
        flex-shrink: 0;
        gap: 30px;
    }

    .recipe-title-heading {
        font-size: 20px;
        font-weight: 500;
        margin-bottom: 10px;
        text-align: center;
    }

    .right-column {
        width: 400px;
        min-height: 100%;
    }

    .recipe-form {
        width: 100%;
    }

    .recipe-form label {
        display: block;
        font-weight: bold;
        margin-top: 25px;
        margin-bottom: 10px;
    }

    .recipe-info {
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 4px;
        margin-bottom: 15px;
    }

    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 8px;
        max-width: 400px;
        width: 90%;
        text-align: center;
    }

    .modal-content h3 {
        margin-top: 0;
        color: #dc3545;
    }

    .warning {
        color: #856404;
        font-size: 14px;
    }

    .modal-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 20px;
    }

    .cancel-btn {
        padding: 8px 20px;
        background-color: #6c757d;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .confirm-delete-btn {
        padding: 8px 20px;
        background-color: #dc3545;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .confirm-delete-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    @media (max-width: 768px) {
    .recipe-page {
        flex-direction: column;
        gap: 20px;
    }

    .admin-sidebar {
        width: 100%;
        order: 1;
    }

    .recipe-create-container {
        flex-direction: column;
        gap: 30px;
        order: 2;
    }

    .left-column {
        width: 100%;
        min-width: auto;
        gap: 20px;
    }

    .right-column {
        width: 100%;
    }

    .admin-actions {
        flex-direction: row;
        flex-wrap: wrap;
    }

    .action-btn {
        flex: 1;
        min-width: 120px;
    }

    .recipe-title-heading {
        font-size: 18px;
    }
}
</style>
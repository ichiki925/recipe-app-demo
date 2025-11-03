<template>
    <div class="recipe-page">

        <!-- 左サイドバー -->
        <RecipeSearchSection
            user-type="guest"
            :initial-keyword="searchKeyword"
            placeholder="料理名・食材で検索"
            @search="handleSearch"
            @clear-search="clearSearch"
        />

        <!-- メイン：レシピ一覧 -->
        <section class="recipe-list">
            <div v-if="!isLoading && searchKeyword && recipes.length === 0" class="no-recipes">
                レシピが見つかりませんでした。
            </div>
            <div v-else class="recipe-grid">
                <div
                    v-for="recipe in recipes"
                    :key="recipe.id"
                    class="recipe-card guest-card"
                    @click="handleRecipeClick(recipe)"
                >
                    <div class="recipe-image">
                        <img
                            :src="getImageUrl(recipe.image_url)"
                            :alt="recipe.title"
                            @error="handleImageError($event, recipe)"
                        />
                    </div>
                    <div class="recipe-title">{{ recipe.title }}</div>
                    <div class="login-overlay">
                        <i class="fas fa-lock"></i>
                        <span>ログインして詳細を見る</span>
                    </div>
                </div>
            </div>

            <!-- ページネーション -->
            <div v-if="!isLoading && totalPages > 1" class="pagination">
            <button
                v-if="currentPage > 1"
                @click="goToPage(currentPage - 1)"
                class="pagination-btn"
            >
                ＜
            </button>

            <span
                v-for="page in totalPages"
                :key="page"
                :class="{ active: page === currentPage }"
                @click="goToPage(page)"
                class="pagination-number"
            >
                {{ page }}
            </span>

            <button
                v-if="currentPage < totalPages"
                @click="goToPage(currentPage + 1)"
                class="pagination-btn"
            >
                ＞
            </button>
            </div>
        </section>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRoute, useRouter, useHead } from '#app'

definePageMeta({
    layout: 'guest'
})

useHead({
    link: [
        {
            rel: 'stylesheet',
            href: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
        },
    ]
})

// データ定義
const searchKeyword = ref('')
const currentPage = ref(1)
const totalPages = ref(1)
const isLoading = ref(false)

// レシピデータ
const recipes = ref([])

const route = useRoute()
const router = useRouter()

// 検索クリア機能
const clearSearch = () => {
    searchKeyword.value = ''
    currentPage.value = 1
    updateUrl()
}

// 画像URL処理関数
const getImageUrl = (imageUrl) => {
    if (!imageUrl) return '/images/no-image.png'

    if (imageUrl.startsWith('/storage/')) {
        return `${window.location.origin}${imageUrl}`
    }

    return imageUrl
}

// 画像エラーハンドリング（簡略版）
const handleImageError = (event, recipe) => {
    event.target.onerror = null
    const parent = event.target.parentElement
    event.target.style.display = 'none'
    if (!parent.querySelector('.no-image-fallback')) {
        const placeholder = document.createElement('div')
        placeholder.className = 'no-image-fallback'
        placeholder.innerHTML = '<div class="no-image-text">No Image</div>'
        parent.appendChild(placeholder)
    }
}

// API経由でレシピを検索取得
const fetchRecipes = async () => {
    try {
        isLoading.value = true

        const config = useRuntimeConfig()

        const response = await $fetch('/api/recipes/search', {
            baseURL: config.public.apiBaseUrl,
            query: { keyword: searchKeyword.value, page: currentPage.value, per_page: 9 }

        })

        // レシピデータを更新（ジャンル情報は除外）
        recipes.value = response.data.map(recipe => ({
            id: recipe.id,
            title: recipe.title,
            likes: recipe.likes_count,
            image_url: recipe.image_url,
            admin: recipe.admin
        }))

        // ページネーション情報更新
        currentPage.value = response.current_page
        totalPages.value = response.last_page

    } catch (error) {
        console.error('❌ レシピ検索エラー:', error)
      // 本番はモックを使わない：空で表示
        recipes.value = []
        totalPages.value = 1
    } finally {
        isLoading.value = false
    }
}

const searchRecipes = () => {
    currentPage.value = 1
    updateUrl()
}

const handleSearch = (keyword) => {
    searchKeyword.value = keyword
    searchRecipes()
}

const goToPage = (page) => {
    currentPage.value = page
    updateUrl()
}

const updateUrl = () => {
    const query = {}
    if (searchKeyword.value) query.keyword = searchKeyword.value
    if (currentPage.value > 1) query.page = currentPage.value
    router.push({ path: '/', query })
}

const handleRecipeClick = (recipe) => {
    navigateTo(`/auth/login?redirect=${encodeURIComponent(route.fullPath)}`)
}

watch(() => route.query, (newQuery) => {
    searchKeyword.value = newQuery.keyword || ''
    currentPage.value = parseInt(newQuery.page) || 1
    fetchRecipes()
}, { immediate: true })
</script>

<style scoped>
@import "@/assets/css/common.css";

.recipe-page {
    display: flex;
    padding: 20px;
    gap: 30px;
    max-width: 1400px;
    margin: 0 auto;
}

.recipe-list {
    flex: 1;
    min-height: 300px;
}

.no-results {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.no-results-icon {
    font-size: 48px;
    color: #ccc;
    margin-bottom: 20px;
}

.no-results h3 {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 10px;
    color: #333;
}

.no-results p {
    font-size: 16px;
    margin-bottom: 30px;
    color: #666;
}

.show-all-button {
    background-color: #ff7700;
    color: white;
    border: none;
    padding: 12px 24px;
    font-size: 16px;
    font-weight: bold;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.show-all-button:hover {
    background-color: #e66600;
}

.recipe-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

@media (max-width: 1024px) {
    .recipe-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .recipe-grid {
        grid-template-columns: 1fr;
    }
}

.recipe-card {
    width: 100%;
    height: 400px;
    border: 1px solid #eee;
    border-radius: 6px;
    padding: 10px;
    box-shadow: 1px 1px 5px rgba(0, 0, 0, 0.1);
    text-align: center;
    background: white;
    box-sizing: border-box;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.no-image {
    width: 100%;
    height: 300px;
    background-color: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 14px;
    border-radius: 6px;
}

.recipe-title {
    margin-top: 10px;
    font-weight: bold;
    color: #333;
}

.recipe-image {
    width: 100%;
    height: 300px;
    border-radius: 6px;
    overflow: hidden;
    position: relative;
    background-color: #f0f0f0;
}

.recipe-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image-fallback {
    width: 100%;
    height: 100%;
    background-color: #f0f0f0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #999;
    border-radius: 6px;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
}

.no-image-text {
    font-size: 14px;
    font-weight: 500;
    text-align: center;
}

.recipe-title {
    margin-top: 10px;
    font-weight: bold;
    color: #333;
    font-size: 16px;
}

.recipe-stats {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 8px;
    font-size: 12px;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 30px;
}

.pagination-btn {
    padding: 8px 16px;
    background-color: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.pagination-btn:hover {
    background-color: #e9e9e9;
}

.pagination-number {
    padding: 8px 12px;
    cursor: pointer;
    border-radius: 4px;
    font-size: 14px;
}

.pagination-number:hover {
    background-color: #f0f0f0;
}

.pagination-number.active {
    background-color: #ffb300c7;
    color: white;
}

.guest-card {
    position: relative;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.guest-card:hover {
    transform: translateY(-2px);
    box-shadow: 2px 4px 12px rgba(0, 0, 0, 0.15);
}

.guest-card:hover .login-overlay {
    opacity: 1;
}

.login-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    border-radius: 6px;
}

.login-overlay i {
    font-size: 24px;
    margin-bottom: 8px;
}

.login-overlay span {
    font-size: 14px;
    font-weight: bold;
}

.recipe-list
.no-recipes {
    text-align: center;
    padding: 40px;
    font-size: 16px;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .recipe-page {
        flex-direction: column;
        padding: 15px;
    }

    .recipe-list {
        order: 1;
    }

    .recipe-grid {
        grid-template-columns: 1fr;
    }

    .no-results {
        padding: 40px 15px;
    }

    .no-results h3 {
        font-size: 20px;
    }

    .no-results p {
        font-size: 14px;
    }
}
</style>



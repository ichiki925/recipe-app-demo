<template>
  <div class="recipe-page">
    <!-- 左サイドバー -->
    <RecipeSearchSection
      user-type="user"
      :initial-keyword="searchKeyword"
      @search="handleSearch"
      @clear-search="handleClearSearch"
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
          class="recipe-card"
          @click="goToRecipeDetail(recipe.id)"
        >
          <div class="recipe-image">
              <img
                  :src="getImageUrl(recipe.image_url)"
                  :alt="recipe.title"
                  @error="handleImageError($event, recipe)"
              />
          </div>
          <div class="recipe-title">{{ recipe.title }}</div>
          <div class="recipe-genre">{{ recipe.genre }}</div>
          <div class="recipe-stats">
            <button
              @click="toggleLike(recipe, $event)"
              class="like-button"
              :class="{ liked: recipe.isFavorited }"
              :title="recipe.isFavorited ? 'お気に入りから削除' : 'お気に入りに追加'"
            >
              <i
                v-if="recipe.isFavorited"
                class="fas fa-heart heart-icon-filled"
              ></i>
              <i
                v-else
                class="far fa-heart heart-icon-outline"
              ></i>
              <span class="like-count">{{ recipe.likes }}</span>
            </button>
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
import { ref, onMounted, watch } from 'vue'

definePageMeta({
  ssr: false
})

useHead({
  link: [
    {
      rel: 'stylesheet',
      href: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
      crossorigin: 'anonymous'
    },
  ]
})

const { user, isLoggedIn, initAuth, waitForAuth } = useAuth()
const { getAuth, postAuth } = useApi()

const searchKeyword = ref('')
const currentPage = ref(1)
const totalPages = ref(1)
const recipes = ref([])
const isLoading = ref(false)
const isAuthInitialized = ref(false)

const route = useRoute()
const router = useRouter()

// 画像URL処理関数
const getImageUrl = (imageUrl) => {
  if (!imageUrl) return '/images/no-image.png'
  const config = useRuntimeConfig()
  const host =
    (config.public.apiBaseUrl || '').replace(/\/api\/?$/, '') ||
    (import.meta.client ? window.location.origin : '')
  return imageUrl.startsWith('/storage/') ? `${host}${imageUrl}` : imageUrl
}

// 画像エラーハンドリング
const handleImageError = (event) => {
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

// お気に入り状態管理用のグローバルストア
const favoriteStore = useState('favorites', () => new Set())

// 詳細ページへの遷移
const goToRecipeDetail = (recipeId) => {
  navigateTo(`/user/show/${recipeId}`)
}

const handleSearch = (keyword) => {
  searchKeyword.value = keyword
  currentPage.value = 1
  updateUrl()
}

const handleClearSearch = () => {
  searchKeyword.value = ''
  currentPage.value = 1
  updateUrl()
}

const goToPage = (page) => {
  currentPage.value = page
  updateUrl()
}

const updateUrl = () => {
  const query = {}
  if (searchKeyword.value) query.keyword = searchKeyword.value
  if (currentPage.value > 1) query.page = currentPage.value
  router.push({ path: '/user', query })
}

const fetchRecipes = async () => {
  if (!isAuthInitialized.value) {
    console.log('⏳ 認証初期化待ち...')
    return
  }

  isLoading.value = true
  console.log('🔍 fetchRecipes開始:', { keyword: searchKeyword.value, page: currentPage.value })

  try {
    const response = await getAuth('recipes/search', {
      query: {
        keyword: searchKeyword.value || '',
        page: currentPage.value,
        per_page: 9
      }
    })

    console.log('📦 API Response:', response)

    recipes.value = (response.data || []).map(r => ({
      id: r.id,
      title: r.title,
      genre: r.genre,
      likes: r.likes_count ?? 0,
      isFavorited: !!r.is_liked,
      image_url: r.image_url,
      admin: r.admin
    }))

    console.log('✅ recipes.value:', recipes.value)

    const isFirstLoad = favoriteStore.value.size === 0

    if (isFirstLoad) {
      recipes.value.forEach(recipe => {
        if (recipe.isFavorited) {
          favoriteStore.value.add(recipe.id)
        }
      })
      favoriteStore.value = new Set(favoriteStore.value)
    } else {
      recipes.value.forEach(recipe => {
        const shouldBeFavorited = favoriteStore.value.has(recipe.id)
        if (recipe.isFavorited !== shouldBeFavorited) {
          recipe.isFavorited = shouldBeFavorited
        }
      })
    }

    // ページネーション情報更新
    currentPage.value = Number(response.current_page ?? 1)
    totalPages.value = Number(response.last_page ?? 1)

  } catch (e) {
    console.error('❌ レシピ取得エラー:', e)

    if (e.status === 401 || e.statusCode === 401) {
      await navigateTo('/auth/login')
    }
  } finally {
    isLoading.value = false
  }
}

const toggleLike = async (recipe, event) => {
  if (event) {
    event.preventDefault()
    event.stopPropagation()
  }

  const currentUser = user?.value || auth?.user?.value
  if (!currentUser) {
    alert('ログインが必要です')
    return
  }

  const originalState = recipe.isFavorited
  const originalLikes = recipe.likes

  recipe.isFavorited = !originalState
  recipe.likes = originalState ? recipe.likes - 1 : recipe.likes + 1

  try {
    const response = await postAuth(`recipes/${recipe.id}/toggle-like`)

    if (response && typeof response.is_liked !== 'undefined') {
      const newLikedState = !!response.is_liked
      const newLikesCount = response.likes_count || 0
      recipe.isFavorited = newLikedState
      recipe.likes = newLikesCount
      if (newLikedState) favoriteStore.value.add(recipe.id)
      else favoriteStore.value.delete(recipe.id)
      favoriteStore.value = new Set(favoriteStore.value)
    }

  } catch (error) {
    console.error('❌ いいね更新エラー:', error)

    recipe.isFavorited = originalState
    recipe.likes = originalLikes

    if (originalState) {
      favoriteStore.value.add(recipe.id)
    } else {
      favoriteStore.value.delete(recipe.id)
    }

    alert('いいねの更新に失敗しました')
  }
}

onMounted(async () => {
  console.log('1️⃣ onMounted開始')

  await initAuth()        // 一度だけ初期化（❷の効果）
  await waitForAuth()     // Firebaseの復元を必ず待つ
  await new Promise(r => requestAnimationFrame(() => r())) // 1フレーム待機

  if (!isLoggedIn.value) {
    return navigateTo('/auth/login')
  }

  console.log('4️⃣ ログイン済み')

  favoriteStore.value.clear()

  isAuthInitialized.value = true
  console.log('5️⃣ 認証初期化完了フラグON')

  searchKeyword.value = route.query.keyword || ''
  currentPage.value = parseInt(route.query.page) || 1

  await fetchRecipes()
  console.log('6️⃣ 初回レシピ取得完了')
})


watch(() => route.query, (newQuery) => {
  console.log('🔄 URLクエリ変更検知:', newQuery)

  searchKeyword.value = newQuery.keyword || ''
  currentPage.value = parseInt(newQuery.page) || 1

  if (isAuthInitialized.value) {
    fetchRecipes()
  }
})

watch(favoriteStore, (newFavorites) => {
  recipes.value.forEach(recipe => {
    const shouldBeFavorited = newFavorites.has(recipe.id)
    if (recipe.isFavorited !== shouldBeFavorited) {
      recipe.isFavorited = shouldBeFavorited
    }
  })
}, { deep: true })

</script>

<style scoped>
@import "@/assets/css/common.css";

.recipe-page {
  padding: 20px;
  gap: 30px;
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
}

.recipe-list {
  flex: 1;
  min-height: 300px;
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
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.recipe-card:hover {
  transform: translateY(-2px);
  box-shadow: 2px 4px 12px rgba(0, 0, 0, 0.15);
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
}

.recipe-genre {
  color: #555;
  margin-bottom: 5px;
}

.recipe-stats {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 10px;
}

.like-button {
  display: flex;
  align-items: center;
  gap: 4px;
  background: none;
  border: none;
  cursor: pointer;
  border-radius: 4px;
  transition: all 0.2s ease;
  transform: translateY(-5px);
  padding: 8px 12px;
  position: relative;
  z-index: 10;
}

.recipe-card {
  position: relative;
  z-index: 1;
}

.like-button:hover {
  background-color: #f8f9fa;
  transform: translateY(-7px);
}

.like-button:active {
  transform: translateY(-3px);
}

.heart-icon-filled,
.heart-icon-outline {

  cursor: pointer;
  font-size: 16px;
}

.heart-icon-filled {
  color: #dc3545;
  animation: heartBeat 0.3s ease;
}

.heart-icon-outline {
  color: #333;
  transition: color 0.2s ease;
}

.like-button:hover .heart-icon-outline {
  color: #dc3545;
}

.like-count {
  font-size: 12px;
  color: #333;
  transform: translateY(-1.5px);
  pointer-events: none;
}

.like-button.liked  {
  color: #dc3545;
  font-weight: 500;
}

.recipe-list .no-recipes {
  text-align: center;
  padding: 40px;
  font-size: 16px;
}

@keyframes heartBeat {
  0% { transform: scale(1); }
  50% { transform: scale(1.3); }
  100% { transform: scale(1); }
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
  color: #000;
}

.pagination-number:hover {
  background-color: #f0f0f0;
}

.pagination-number.active {
  background-color: #ffb300c7;
  color: white;
}

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
}
</style>
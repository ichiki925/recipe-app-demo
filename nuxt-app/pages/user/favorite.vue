<template>
  <div class="recipe-page">
    <RecipeSearchSection
      user-type="user"
      :initial-keyword="searchKeyword"
      placeholder="お気に入りレシピを検索"
      @search="handleSearch"
      @clear-search="handleClearSearch"
    />

    <section class="recipe-list">
      <h2 class="page-title">
        <i class="fas fa-heart"></i>
        お気に入りレシピ ({{ totalRecipes }}件)
      </h2>

      <div v-if="isLoading" class="loading">
        <i class="fas fa-spinner fa-spin"></i>
        読み込み中...
      </div>

      <div v-else-if="favoriteRecipes.length === 0" class="no-recipes">
        <div class="empty-state">
          <i class="far fa-heart empty-heart"></i>
          <h3 v-if="!searchKeyword">お気に入りのレシピがありません</h3>
          <h3 v-else>「{{ searchKeyword }}」に該当するお気に入りレシピがありません</h3>
          <p v-if="!searchKeyword">
            レシピ一覧で♡をクリックして、<br>お気に入りに追加してみましょう！
          </p>
          <p v-else>
            検索条件を変更するか、他のレシピをお気に入りに追加してみてください。
          </p>
          <NuxtLink to="/user" class="back-to-recipes">
            レシピ一覧に戻る
          </NuxtLink>
        </div>
      </div>

      <div v-else class="recipe-grid">
        <div
          v-for="recipe in favoriteRecipes"
          :key="recipe.id"
          class="recipe-card"
          :data-recipe-id="recipe.id"
          @click="goToRecipeDetail(recipe.id)"
        >
          <div class="recipe-image">
            <img
              :src="recipe.image_full_url || '/images/no-image.png'"
              :alt="recipe.title"
              class="recipe-img"
              loading="lazy"
              decoding="async"
              @error="e => { e.target.onerror = null; e.target.src = '/images/no-image.png' }"
            />
          </div>
          <div class="recipe-title">{{ recipe.title }}</div>
          <div class="recipe-genre">{{ recipe.genre }}</div>
          <div class="recipe-stats">
            <button
              @click.stop="toggleLike(recipe)"
              class="like-button liked"
              title="お気に入りから削除"
            >
              <i class="fas fa-heart heart-icon-filled"></i>
              <span class="like-count">{{ recipe.likes }}</span>
            </button>
          </div>
        </div>
      </div>

      <div class="pagination" v-if="!isLoading && totalPages > 1">
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
definePageMeta({
  ssr: false
})

useHead({
  link: [
    {
      rel: 'stylesheet',
      href: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
    },
  ]
})

const { user, isLoggedIn, initAuth } = useAuth()
const { getAuth, postAuth } = useApi()

const searchKeyword = ref('')
const currentPage = ref(1)
const totalPages = ref(1)
const totalRecipes = ref(0)
const isLoading = ref(false)

const favoriteRecipes = ref([])

const route = useRoute()
const router = useRouter()

const favoriteStore = useState('favorites', () => new Set())

const fetchFavoriteRecipes = async () => {
  if (!user.value) return

  try {
    isLoading.value = true

    const response = await getAuth('user/liked-recipes', {
      query: {
        keyword: searchKeyword.value || '',
        page: currentPage.value,
        per_page: 6
      }
    })

    favoriteRecipes.value = (response.data || []).map(recipe => ({
      id: recipe.id,
      title: recipe.title,
      genre: recipe.genre,
      likes_count: recipe.likes_count,
      isFavorited: true,
      image_full_url: recipe.image_full_url,
      admin: recipe.admin
    }))

    currentPage.value = response.current_page || 1
    totalPages.value = response.last_page || 1
    totalRecipes.value = response.total || 0

    favoriteRecipes.value.forEach(recipe => {
      favoriteStore.value.add(recipe.id)
    })
    favoriteStore.value = new Set(favoriteStore.value)

  } catch (error) {
    console.error('❌ お気に入りレシピ取得エラー:', error)
    favoriteRecipes.value = []
    totalRecipes.value = 0
    totalPages.value = 1

    if (error.status === 401 || error.statusCode === 401) {
      await navigateTo('/auth/login')
    }
  } finally {
    isLoading.value = false
  }
}

const goToRecipeDetail = (recipeId) => {
  navigateTo(`/user/show/${recipeId}`)
}

const handleSearch = (keyword) => {
  searchKeyword.value = keyword
  currentPage.value = 1
  updateUrl()
  fetchFavoriteRecipes()
}

const handleClearSearch = () => {
  searchKeyword.value = ''
  currentPage.value = 1
  updateUrl()
  fetchFavoriteRecipes()
}

const goToPage = (page) => {
  currentPage.value = page
  updateUrl()
  fetchFavoriteRecipes()
}

const updateUrl = () => {
  const query = {}
  if (searchKeyword.value) query.keyword = searchKeyword.value
  if (currentPage.value > 1) query.page = currentPage.value
  router.push({ path: '/user/favorite', query })
}

const toggleLike = async (recipe) => {
  if (!user.value) return

  try {
    const recipeElement = document.querySelector(`[data-recipe-id="${recipe.id}"]`)
    if (recipeElement) {
      recipeElement.style.transition = 'opacity 0.3s ease, transform 0.3s ease'
      recipeElement.style.opacity = '0'
      recipeElement.style.transform = 'scale(0.8)'
    }

    favoriteStore.value.delete(recipe.id)

    const response = await postAuth(`recipes/${recipe.id}/toggle-like`)

    await fetchFavoriteRecipes()

    favoriteStore.value = new Set(favoriteStore.value)

  } catch (error) {
    console.error('❌ お気に入り削除エラー:', error)

    favoriteStore.value.add(recipe.id)

    const recipeElement = document.querySelector(`[data-recipe-id="${recipe.id}"]`)
    if (recipeElement) {
      recipeElement.style.opacity = '1'
      recipeElement.style.transform = 'scale(1)'
    }

    alert('お気に入りの削除に失敗しました。もう一度お試しください。')
  }
}

onMounted(async () => {
  await initAuth()

  if (!isLoggedIn.value) {
    return navigateTo('/auth/login')
  }

  searchKeyword.value = route.query.keyword || ''
  currentPage.value = parseInt(route.query.page) || 1

  await fetchFavoriteRecipes()
})

watch(() => route.query, (newQuery) => {
  const newKeyword = newQuery.keyword || ''
  const newPage = parseInt(newQuery.page) || 1

  const oldKeyword = searchKeyword.value
  const oldPage = currentPage.value

  let shouldFetch = false

  if (newKeyword !== oldKeyword) {
    searchKeyword.value = newKeyword
    shouldFetch = true
  }

  if (newPage !== oldPage) {
    currentPage.value = newPage
    shouldFetch = true
  }

  if (shouldFetch) {
    fetchFavoriteRecipes()
  }
}, { immediate: false })
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

.recipe-image {
    width: 100%;
    height: 300px;
    border-radius: 6px;
    overflow: hidden;
    position: relative;
}

.recipe-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 6px;
    transition: transform 0.2s ease;
}

.recipe-card:hover .recipe-img {
    transform: scale(1.05);
}

.loading {
  text-align: center;
  padding: 40px;
  color: #666;
  font-size: 16px;
}

.loading i {
  font-size: 20px;
  margin-right: 10px;
  color: #dc3545;
}

.page-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 1.5rem;
  color: #111;
  margin-bottom: 20px;
  font-weight: lighter;
}

.page-title i {
  color: #dc3545;
}

.no-recipes {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 400px;
}

.empty-state {
  text-align: center;
  color: #333;
}

.empty-heart {
  font-size: 4rem;
  color: #ddd;
  margin-bottom: 1rem;
}

.empty-state h3 {
  font-size: 1.2rem;
  margin-bottom: 0.5rem;
  color: #333;
}

.empty-state p {
  line-height: 1.6;
  margin-bottom: 1.5rem;
}

.back-to-recipes {
  display: inline-block;
  background-color: #dc3545;
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  text-decoration: none;
  transition: background-color 0.3s ease;
}

.back-to-recipes:hover {
  background-color: #c82333;
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
    transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.3s ease;
}

.recipe-card:hover {
    transform: translateY(-2px);
    box-shadow: 2px 4px 12px rgba(0, 0, 0, 0.15);
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
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.2s ease;
    transform: translateY(-5px);
}

.like-button:hover {
    background-color: #f8f9fa;
    transform: translateY(-7px);
}

.like-button.liked {
  animation: heartPulse 0.3s ease;
}

.heart-icon-filled {
    color: #dc3545;
    font-size: 16px;
}

.like-count {
  font-size: 12px;
  color: #dc3545;
  font-weight: 500;
  transform: translateY(-1.5px);
}

/* ハートパルスアニメーション */
@keyframes heartPulse {
  0% { transform: translateY(-5px) scale(1); }
  50% { transform: translateY(-5px) scale(1.1); }
  100% { transform: translateY(-5px) scale(1); }
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
    transition: background-color 0.2s ease;
}

.pagination-btn:hover {
    background-color: #e9e9e9;
}

.pagination-number {
    padding: 8px 12px;
    cursor: pointer;
    border-radius: 4px;
    font-size: 14px;
    transition: background-color 0.2s ease;
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

    .page-title {
      font-size: 1.2rem;
    }
}
</style>
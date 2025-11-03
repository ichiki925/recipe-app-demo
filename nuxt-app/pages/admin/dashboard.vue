<template>
  <div class="dashboard-container">
    <h1>管理者ダッシュボード</h1>

    <div class="dashboard-stats">
      <div class="dashboard-card">
        <span>全レシピ数</span>
        <strong>{{ totalRecipes }} 件</strong>
      </div>
      <div class="dashboard-card">
        <span>最近更新されたレシピ</span>
        <strong>{{ recentUpdatedRecipes }} 件</strong>
      </div>
      <div class="dashboard-card">
        <span>ユーザー登録数</span>
        <strong>{{ totalUsers }} 件</strong>
      </div>
    </div>

    <div class="admin-menu">
      <NuxtLink to="/admin/recipes" class="admin-button">📋 レシピ一覧</NuxtLink>
      <NuxtLink to="/admin/recipes/create" class="admin-button">➕ レシピ新規作成</NuxtLink>
      <NuxtLink to="/admin/comments" class="admin-button">💬 コメント管理</NuxtLink>
    </div>

    <div class="recent-deleted" v-if="editingRecipes.length > 0">
      <h2>✏️ 編集中のレシピ</h2>
      <ul class="deleted-list">
        <li v-for="recipe in editingRecipes" :key="recipe.id">
          <div class="recipe-info">
            <span class="recipe-title">{{ recipe.title || '無題のレシピ' }}</span>
            <span class="recipe-meta">
              {{ recipe.isEditDraft ? '(編集下書き)' : '(新規下書き)' }} - {{ formatDate(recipe.savedAt) }}
            </span>
          </div>
          <div class="recipe-actions">
            <NuxtLink
              :to="recipe.isEditDraft
                ? `/admin/recipes/edit/${recipe.originalRecipeId}?draft=${recipe.id}`
                : `/admin/recipes/create?draft=${recipe.id}`"
              class="edit-link"
            >
              編集を続ける
            </NuxtLink>
            <button
              @click="deleteEditingRecipe(recipe)"
              class="delete-link"
            >
              下書きを削除
            </button>
          </div>
        </li>
      </ul>
    </div>

    <div class="recent-deleted">
      <h2>🗑 最近削除されたレシピ</h2>
      <ul class="deleted-list" v-if="deletedRecipes.length > 0">
        <li v-for="recipe in deletedRecipes" :key="recipe.id">
          <div class="recipe-info">
            <span class="recipe-title">{{ recipe.title }}</span>
            <span class="recipe-meta">{{ formatDate(recipe.deleted_at) }}</span>
          </div>
          <div class="recipe-actions">
            <button
              @click="restoreRecipe(recipe.id)"
              class="restore-button"
              :disabled="isProcessing"
            >
              復元
            </button>
            <button
              @click="permanentlyDeleteRecipe(recipe.id)"
              class="permanent-delete-button"
              :disabled="isProcessing"
            >
              完全削除
            </button>
          </div>
        </li>
      </ul>
      <div v-else class="no-items">
        最近削除されたレシピはありません
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  layout: 'admin',
  ssr: false
})

import { getStorage, ref as storageRef, deleteObject } from 'firebase/storage'

const { isLoggedIn, isAdmin, initAuth } = useAuth()
const { getAuth, postAuth, delAuth } = useApi()


const dashboardData = ref({
  stats: {},
  deleted_recipes: []
})

// 将来実装予定の機能
// const recentActivities = ref([])  // 最近の活動
// const popularRecipes = ref([])    // 人気レシピTop5

const isLoading = ref(true)
const isProcessing = ref(false)
const editingRecipes = ref([])

const loadEditingRecipes = () => {
  try {
    const saved = localStorage.getItem('savedRecipes')
    if (saved) {
      editingRecipes.value = JSON.parse(saved)
    }
  } catch (error) {
    console.error('編集中レシピの読み込みエラー:', error)
    editingRecipes.value = []
  }
}

const deleteEditingRecipe = async (editingRecipe) => {
  if (!confirm(`「${editingRecipe.title}」の下書きを削除しますか？`)) {
    return
  }

  isProcessing.value = true

  try {
    if (editingRecipe.tempImagePath) {
      try {
        await deleteTempImage(editingRecipe.tempImagePath)
      } catch (error) {
        console.error('一時画像削除エラー（無視）:', error)
      }
    }

    editingRecipes.value = editingRecipes.value.filter(r => r.id !== editingRecipe.id)
    localStorage.setItem('savedRecipes', JSON.stringify(editingRecipes.value))

    alert('下書きを削除しました')

  } catch (error) {
    console.error('下書き削除エラー:', error)
    alert('下書きの削除に失敗しました')
  } finally {
    isProcessing.value = false
  }
}

const restoreRecipe = async (recipeId) => {
  if (!confirm('このレシピを復元しますか？')) {
    return
  }

  isProcessing.value = true

  try {
    await postAuth(`/api/admin/recipes/${recipeId}/restore`)

    dashboardData.value.deleted_recipes = dashboardData.value.deleted_recipes.filter(
      recipe => recipe.id !== recipeId
    )

    if (dashboardData.value.stats) {
      dashboardData.value.stats.total_recipes = (dashboardData.value.stats.total_recipes || 0) + 1
    }

    alert('レシピを復元しました')

    await navigateTo('/admin/recipes?restored=true')

  } catch (error) {
    console.error('レシピ復元エラー:', error)
    alert('レシピの復元に失敗しました')
  } finally {
    isProcessing.value = false
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


const permanentlyDeleteRecipe = async (recipeId) => {
  if (!confirm('このレシピを完全に削除しますか？\n※この操作は取り消せません')) {
    return
  }

  isProcessing.value = true

  try {
    await delAuth(`/api/admin/recipes/${recipeId}/permanent-delete`)

    dashboardData.value.deleted_recipes = dashboardData.value.deleted_recipes.filter(
      recipe => recipe.id !== recipeId
    )

    alert('レシピを完全に削除しました')

  } catch (error) {
    console.error('レシピ完全削除エラー:', error)
    alert('レシピの完全削除に失敗しました')
  } finally {
    isProcessing.value = false
  }
}

const formatDate = (dateString) => {
  try {
    if (!dateString) return '不明'
    const date = new Date(dateString)
    return date.toLocaleDateString('ja-JP', {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch (error) {
    return '不明'
  }
}

onMounted(async () => {
  loadEditingRecipes()

  await initAuth()
  if (!isLoggedIn.value || !isAdmin.value) {
    return navigateTo('/admin/login')
  }

  try {
    const response = await getAuth('/api/admin/dashboard')
    dashboardData.value = response.data || response
  } catch (error) {
    console.error('ダッシュボードデータ取得エラー:', error)
    if (error?.status === 401 || error?.status === 403) {
      return navigateTo('/admin/login')
    }
  } finally {
    isLoading.value = false
  }
})

const totalRecipes = computed(() => dashboardData.value.stats?.total_recipes || 0)
const recentUpdatedRecipes = computed(() => dashboardData.value.stats?.recent_updated_recipes || 0)
const totalUsers = computed(() => dashboardData.value.stats?.total_users || 0)
const deletedRecipes = computed(() => dashboardData.value.deleted_recipes || [])
</script>

<style scoped>
@import '@/assets/css/common.css';

.dashboard-container {
    padding: 30px;
    font-family: 'Arial', sans-serif;
    color: #333;
}

.dashboard-container h1 {
    font-size: 28px;
    font-family: serif;
    font-weight: lighter;
    margin-bottom: 20px;
    border-left: 6px solid #888;
    padding-left: 10px;
}

.dashboard-stats {
    display: flex;
    gap: 30px;
    margin-bottom: 30px;
}

.dashboard-card {
    background-color: #fff;
    border: 1px solid #ddd;
    border-left: 6px solid #999;
    padding: 15px 20px;
    box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.1);
    border-radius: 6px;
    flex: 1;
}

.dashboard-card span {
    font-size: 14px;
    color: #777;
}

.dashboard-card strong {
    font-size: 22px;
    display: block;
    margin-top: 5px;
}

.admin-menu {
    display: flex;
    gap: 15px;
    margin: 20px 0;
    flex-wrap: wrap;
}

.admin-button {
    background-color: #eee;
    border: 1px solid #ccc;
    padding: 10px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-family: cursive;
    color: #333;
    transition: background 0.2s ease;
}

.admin-button:hover {
    background-color: #ddd;
}

.recent-deleted h2 {
    font-family: serif;
    font-weight: lighter;
    font-size: 20px;
    margin-top: 40px;
    margin-bottom: 10px;
}

.deleted-list {
    list-style: none;
    padding: 0;
}

.deleted-list li {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.recipe-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.recipe-title {
    font-weight: bold;
}

.recipe-meta {
    font-size: 12px;
    color: #666;
}

.recipe-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.edit-link {
    color: #007bff;
    text-decoration: none;
    font-size: 14px;
}

.edit-link:hover {
    text-decoration: underline;
}

.delete-link {
    background: none;
    border: none;
    color: #dc3545;
    text-decoration: none;
    font-size: 14px;
    cursor: pointer;
}

.delete-link:hover {
    text-decoration: underline;
}

.restore-button {
    background-color: #fbc559f6;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.restore-button:hover:not(:disabled) {
    background-color: #f6ad1af6;
}

.restore-button:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
}

.permanent-delete-button {
    background-color: #ec8892f5;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.permanent-delete-button:hover:not(:disabled) {
    background-color: #c82333;
}

.permanent-delete-button:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
}

.no-items {
    color: #666;
    font-style: italic;
    padding: 10px 0;
}

@media screen and (max-width: 768px) {
    .dashboard-stats {
        flex-direction: column;
        gap: 15px;
    }

    .admin-menu {
        flex-direction: column;
        gap: 10px;
    }

    .dashboard-card {
        width: 100%;
    }

    .admin-button {
        width: 100%;
        text-align: center;
    }

    .dashboard-container {
        padding: 15px;
    }

    .deleted-list li {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .recipe-actions {
        align-self: stretch;
        justify-content: flex-end;
    }
}
</style>
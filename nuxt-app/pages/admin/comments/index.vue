<template>
  <div class="comments-page">
    <h1>Comments</h1>

    <div class="simple-search">
      <input
        v-model="searchFilters.keyword"
        type="text"
        placeholder="コメント・ユーザー名・レシピ名で検索"
        @input="debouncedSearch"
        class="search-input"
      >
      <button @click="clearSearch" class="clear-btn" v-if="searchFilters.keyword">クリア</button>
    </div>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>ユーザー名</th>
          <th>レシピ</th>
          <th>コメント</th>
          <th>投稿日</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td colspan="6" style="text-align: center; color: #666;">読み込み中...</td>
        </tr>
        <tr v-else-if="comments.length === 0">
          <td colspan="6" style="text-align: center; color: #666;">コメントが見つかりません</td>
        </tr>
        <tr v-else v-for="comment in comments" :key="comment.id">
          <td data-label="ID">{{ comment.id }}</td>
          <td data-label="ユーザー名">{{ comment.user.name }}</td>
          <td data-label="レシピ">{{ comment.recipe.title }}</td>
          <td data-label="コメント">{{ comment.content }}</td>
          <td data-label="投稿日">{{ formatDate(comment.created_at) }}</td>
          <td data-label="操作">
            <button @click="deleteComment(comment.id)">削除</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="pagination" v-if="totalPages > 1">
      <button
        @click="goToPage(currentPage - 1)"
        :disabled="currentPage <= 1"
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
        @click="goToPage(currentPage + 1)"
        :disabled="currentPage >= totalPages"
        class="pagination-btn"
      >
        ＞
      </button>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  layout: 'admin',
  ssr: false
})

import { ref, onMounted } from 'vue'
import { useAuth } from '~/composables/useAuth'
import { useRoute, useRouter } from '#app'

const { initAuth, isAdmin } = useAuth()
const { getAuth, delAuth } = useApi()

const comments = ref([])
const loading = ref(false)
const currentPage = ref(1)
const totalPages = ref(1)
const perPage = 10

const searchFilters = ref({
  keyword: ''
})

const route = useRoute()
const router = useRouter()

onMounted(async () => {
  await initAuth()
  if (!isAdmin.value) {
    return navigateTo('/admin/login')
  }

  currentPage.value = parseInt(route.query.page) || 1
  searchFilters.value.keyword = route.query.keyword || ''
  await loadComments()
})

const loadComments = async () => {
  loading.value = true
  try {
    const response = await getAuth('admin/comments', {
      query: {
        page: currentPage.value,
        per_page: perPage,
        keyword: searchFilters.value.keyword
      }
    })
    comments.value = response.data
    totalPages.value = response.meta?.last_page || 1
    currentPage.value = response.meta?.current_page || 1
  } catch (apiError) {
    console.error('API接続失敗:', apiError)
    comments.value = []
    totalPages.value = 1
    currentPage.value = 1
  } finally {
    loading.value = false
  }
}

const searchComments = async () => {
  currentPage.value = 1
  updateUrl()
  await loadComments()
}

const clearSearch = () => {
  searchFilters.value.keyword = ''
  currentPage.value = 1
  updateUrl()
  loadComments()
}

const goToPage = (page) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  updateUrl()
  loadComments()
}

const updateUrl = () => {
  const query = {}
  if (searchFilters.value.keyword) query.keyword = searchFilters.value.keyword
  if (currentPage.value > 1) query.page = currentPage.value
  router.push({ path: '/admin/comments', query })
}

let searchTimeout
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    if (searchFilters.value.keyword.length > 0) {
      searchComments()
    } else {
      clearSearch()
    }
  }, 500)
}

const formatDate = (datetime) => {
  const date = new Date(datetime)
  return date.toISOString().split('T')[0]
}

const deleteComment = async (id) => {
  if (!confirm('本当に削除しますか？')) return
  try {
    await delAuth(`admin/comments/${id}`)
    await loadComments()

    if (comments.value.length === 0 && currentPage.value > 1) {
      currentPage.value = currentPage.value - 1
      updateUrl()
      await loadComments()
    }
  } catch (error) {
    console.error('削除エラー:', error)
    alert('削除に失敗しました')
  }
}
</script>

<style scoped>
body {
  background-color: #fff;
}

h1 {
  font-family: cursive;
  text-align: center;
  margin-top: 30px;
}

.simple-search {
  width: 90%;
  margin: 15px auto;
  display: flex;
  align-items: center;
  gap: 10px;
}

.search-input {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid #aaa;
  border-radius: 4px;
  font-size: 14px;
}

.clear-btn {
  background-color: #6c757d;
  color: white;
  padding: 8px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}

.clear-btn:hover {
  background-color: #545b62;
}

table {
  width: 90%;
  margin: 20px auto;
  border-collapse: collapse;
  background-color: #fff;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
  border-radius: 8px;
  overflow: hidden;
}

thead {
  background-color: #f0f0f0;
}

thead th {
  padding: 12px;
  text-align: left;
  font-size: 14px;
  color: #555;
  border-bottom: 1px solid #bbb;
}

tbody td {
  padding: 12px;
  font-size: 14px;
  border-bottom: 1px solid #eee;
  vertical-align: top;
}

tbody tr:hover {
  background-color: #f9f9f9;
}

button {
  background-color: #ff6b6b;
  color: white;
  padding: 6px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
}

button:hover {
  background-color: #e63946;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
  margin: 30px 0;
}

.pagination-btn {
  background-color: #f5f5f5;
  color: #333;
  border: 1px solid #ccc;
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.pagination-btn:hover:not(:disabled) {
  background-color: #e9e9e9;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-number {
  padding: 8px 12px;
  cursor: pointer;
  border-radius: 4px;
  font-size: 14px;
  color: #333;
}

.pagination-number:hover {
  background-color: #f0f0f0;
}

.pagination-number.active {
  background-color: #ff6b6b;
  color: white;
}

@media screen and (max-width: 768px) {
  .simple-search {
    flex-direction: column;
    gap: 8px;
  }

  .search-input {
    width: 100%;
  }

  table,
  thead,
  tbody,
  th,
  td,
  tr {
    display: block;
  }

  thead {
    display: none;
  }

  tbody tr {
    background-color: #fff;
    margin: 10px;
    padding: 10px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
  }

  tbody td {
    padding: 8px 0;
    font-size: 14px;
    border-bottom: none;
  }

  tbody td::before {
    content: attr(data-label);
    font-weight: bold;
    display: inline-block;
    width: 80px;
  }

  button {
    font-size: 13px;
    padding: 4px 8px;
  }

  .pagination {
    flex-wrap: wrap;
    gap: 5px;
  }

  .pagination-btn,
  .pagination-number {
    padding: 6px 10px;
    font-size: 12px;
  }
}
</style>
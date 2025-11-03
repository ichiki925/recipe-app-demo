<template>
  <aside class="sidebar">
    <form @submit.prevent="handleSearch">
      <div class="search-wrapper">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input
          type="text"
          v-model="searchQuery"
          :placeholder="placeholder"
          class="search-input"
        />
        <!-- クリアボタン（ゲストページのみ） -->
        <button
          v-if="showClearButton && searchQuery"
          type="button"
          @click="clearSearch"
          class="clear-button"
        >
          ×
        </button>
      </div>
      <button type="submit" class="search-button">検索</button>
    </form>

    <!-- 新規レシピ作成ボタン（管理者のみ） -->
    <template v-if="mounted">
      <button
        v-if="userType === 'admin'"
        @click="handleCreateRecipe"
        class="create-button"
      >
        ＋ 新規レシピ作成
      </button>
    </template>
  </aside>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const mounted = ref(false)

onMounted(() => {
  mounted.value = true
})

const props = defineProps({
  // ユーザータイプ: 'guest', 'user', 'admin'
  userType: {
    type: String,
    default: 'guest',
    validator: (value) => ['guest', 'user', 'admin'].includes(value)
  },
  // 初期検索キーワード
  initialKeyword: {
    type: String,
    default: ''
  },
  // プレースホルダーテキスト
  placeholder: {
    type: String,
    default: '料理名・食材で検索'
  },
  // クリアボタンを表示するか
  showClearButton: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['search', 'clear-search', 'create-recipe'])

// 検索クエリ
const searchQuery = ref(props.initialKeyword)

// propsの変更を監視して検索クエリを更新
watch(
  () => props.initialKeyword,
  (newKeyword) => {
    searchQuery.value = newKeyword
  }
)

const toHiragana = (str) => {
  return str.replace(/[\u30a1-\u30f6]/g, (match) => {
    const chr = match.charCodeAt(0) - 0x60;
    return String.fromCharCode(chr);
  });
}

const handleSearch = () => {
  const originalKeyword = searchQuery.value;
  const hiraganaKeyword = toHiragana(originalKeyword);
  // 変換結果をログで確認（開発時のみ）
  console.log('検索キーワード:', originalKeyword, '→', hiraganaKeyword);
  // 元のキーワードをそのまま送信（バックエンドで両方検索するため）
  emit('search', originalKeyword);
}

// 検索クリア
const clearSearch = () => {
  searchQuery.value = ''
  emit('clear-search')
}

// 新規レシピ作成
const handleCreateRecipe = () => {
  emit('create-recipe')
}
</script>

<style scoped>
.sidebar {
  width: 300px;
  background-color: #fff;
  padding: 20px;
  border-radius: 8px;
  height: fit-content;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.search-wrapper {
  position: relative;
  width: 100%;
}

.search-wrapper i.fa-solid {
  position: absolute;
  top: 50%;
  left: 12px;
  transform: translateY(-50%);
  color: #e6e5e5;
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 10px 10px 10px 40px;
  font-size: 16px;
  border: 1px solid #adadad;
  border-radius: 6px;
  box-sizing: border-box;
}

.search-input::placeholder {
  color: #ddd;
  opacity: 1;
}

.clear-button {
  position: absolute;
  top: 50%;
  right: 12px;
  transform: translateY(-60%);
  background: none;
  border: none;
  font-size: 20px;
  font-family: sans-serif;
  color: #999;
  cursor: pointer;
  padding: 0;
  line-height: 1;
}

.clear-button:hover {
  color: #666;
}

.search-button {
  width: 100%;
  background-color: #ddd;
  border: none;
  color: #000;
  padding: 10px;
  font-weight: bold;
  border-radius: 8px;
  margin-top: 20px;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.search-button:hover {
  background-color: #e6e5e5;
}

.create-button {
  width: 100%;
  background-color: #ffbf00;
  border: none;
  color: white;
  padding: 10px;
  font-weight: bold;
  border-radius: 8px;
  margin-top: 15px;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.create-button:hover {
  background-color: #ff9d00;
}

@media (max-width: 768px) {
  .sidebar {
    width: 100%;
  }
}
</style>
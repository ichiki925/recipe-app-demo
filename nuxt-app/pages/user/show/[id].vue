<template>
  <div class="recipe-create-container">
    <!-- 左カラム -->
    <div class="left-column">
      <h2 class="recipe-title-heading">{{ recipe.title || 'レシピ名を入力' }}</h2>

      <RecipeImagePreview
        :image-url="getImageUrl(recipe.image)"
        :alt-text="recipe.title"
        @image-error="handleImageError"
        @image-load="handleImageLoad"
      />

      <RecipeComments
        :comments="comments"
        :is-admin="false"
        @submit-comment="handleSubmitComment"
        ref="recipeComments"
      />

      <RecipeLikeButton
        :is-liked="recipe.isLiked"
        :like-count="recipe.likes"
        :is-admin="false"
        @toggle-like="toggleLike"
      />
    </div>

    <!-- 右カラム -->
    <div class="right-column">
      <div class="recipe-form">
        <label>ジャンル</label>
        <div class="recipe-info">{{ recipe.genre }}</div>

        <RecipeIngredients
          :ingredients="recipe.ingredients"
          :servings="recipe.servings"
        />

        <RecipeInstructions
          :instructions="recipe.body"
        />

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'
import { useRoute, useHead, navigateTo } from '#app'
import RecipeImagePreview from '~/components/RecipeImagePreview.vue'
import RecipeComments from '~/components/RecipeComments.vue'
import RecipeLikeButton from '~/components/RecipeLikeButton.vue'
import RecipeIngredients from '~/components/RecipeIngredients.vue'
import RecipeInstructions from '~/components/RecipeInstructions.vue'

definePageMeta({
  ssr: false
})

const { user, isLoggedIn, initAuth } = useAuth()
const { getAuth, postAuth } = useApi()

useHead({
  title: "Vanilla’s Kitchen",
  link: [
    {
      rel: 'stylesheet',
      href: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'
    }
  ]
})

const route = useRoute()
const recipeId = parseInt(route.params.id)

const recipe = ref({})
const comments = ref([])
const commentsLoading = ref(false)
const favoriteStore = useState('favorites', () => new Set())
const recipeComments = ref(null)

const getImageUrl = (imageUrl) => {
    if (!imageUrl) return '/images/no-image.png'

    if (imageUrl.startsWith('/storage/')) {
        return `http://localhost${imageUrl}`
    }

    return imageUrl
}

// モックレシピデータ
const recipeDatabase = {
  1: {
    id: 1,
    title: '基本のハンバーグ',
    genre: '肉料理',
    servings: '4人分',
    image: null,
    body: '1. 玉ねぎをみじん切りにして炒め、冷ましておく\n2. ボウルにひき肉、卵、パン粉、牛乳を入れて混ぜる\n3. 炒めた玉ねぎ、塩こしょう、ナツメグを加えてよく混ぜる\n4. 4等分して楕円形に成形する\n5. フライパンで両面を焼き、蓋をして中まで火を通す',
    likes: 23,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: '牛ひき肉', quantity: '400g' },
      { id: 2, name: '玉ねぎ', quantity: '1個' },
      { id: 3, name: '卵', quantity: '1個' },
      { id: 4, name: 'パン粉', quantity: '1/2カップ' },
      { id: 5, name: '牛乳', quantity: '大さじ2' },
      { id: 6, name: '塩こしょう', quantity: '適量' },
      { id: 7, name: 'ナツメグ', quantity: '少々' }
    ]
  },
  2: {
    id: 2,
    title: 'チキンカレー',
    genre: 'カレー',
    servings: '3人分',
    image: null,
    body: '1. 鶏肉を一口大に切る\n2. 野菜を食べやすい大きさに切る\n3. 鍋で鶏肉を炒め、色が変わったら野菜を加える\n4. 水とトマト缶を加えて煮込む\n5. 野菜が柔らかくなったらカレールーを溶かし入れる\n6. 10分程度煮込んで完成',
    likes: 35,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: '鶏もも肉', quantity: '400g' },
      { id: 2, name: '玉ねぎ', quantity: '2個' },
      { id: 3, name: 'にんじん', quantity: '1本' },
      { id: 4, name: 'じゃがいも', quantity: '2個' },
      { id: 5, name: 'トマト缶', quantity: '1缶' },
      { id: 6, name: 'カレールー', quantity: '1/2箱' },
      { id: 7, name: '水', quantity: '400ml' },
      { id: 8, name: 'サラダ油', quantity: '大さじ1' }
    ]
  },
  3: {
    id: 3,
    title: '和風パスタ',
    genre: '麺類',
    servings: '2人分',
    image: null,
    body: '1. パスタを茹でる\n2. ベーコンを切って炒める\n3. しめじを加えて炒める\n4. 茹で上がったパスタを加える\n5. 醤油とバターで味付けし、大葉をトッピング',
    likes: 12,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: 'スパゲッティ', quantity: '200g' },
      { id: 2, name: 'しめじ', quantity: '1パック' },
      { id: 3, name: 'ベーコン', quantity: '3枚' },
      { id: 4, name: '大葉', quantity: '5枚' },
      { id: 5, name: '醤油', quantity: '大さじ2' },
      { id: 6, name: 'バター', quantity: '15g' },
      { id: 7, name: '塩こしょう', quantity: '適量' }
    ]
  },
  4: {
    id: 4,
    title: 'チョコレートケーキ',
    genre: 'デザート',
    servings: '5人分以上',
    image: null,
    body: '1. オーブンを180度に予熱する\n2. バターを溶かす\n3. 卵と砂糖を混ぜる\n4. 粉類をふるって加える\n5. バターと牛乳を加えて混ぜる\n6. 型に入れて30分焼く',
    likes: 28,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: '薄力粉', quantity: '100g' },
      { id: 2, name: 'ココアパウダー', quantity: '30g' },
      { id: 3, name: '卵', quantity: '2個' },
      { id: 4, name: '砂糖', quantity: '80g' },
      { id: 5, name: 'バター', quantity: '50g' },
      { id: 6, name: '牛乳', quantity: '50ml' },
      { id: 7, name: 'ベーキングパウダー', quantity: '小さじ1' }
    ]
  },
  5: {
    id: 5,
    title: '野菜炒め',
    genre: '野菜料理',
    servings: '2人分',
    image: null,
    body: '1. 野菜を食べやすい大きさに切る\n2. フライパンで豚肉を炒める\n3. 野菜を加えて炒める\n4. 醤油と塩こしょうで味付け\n5. 最後にごま油を回しかける',
    likes: 9,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: 'キャベツ', quantity: '1/4個' },
      { id: 2, name: 'にんじん', quantity: '1/2本' },
      { id: 3, name: 'ピーマン', quantity: '2個' },
      { id: 4, name: 'もやし', quantity: '1袋' },
      { id: 5, name: '豚こま肉', quantity: '150g' },
      { id: 6, name: '醤油', quantity: '大さじ1' },
      { id: 7, name: '塩こしょう', quantity: '適量' },
      { id: 8, name: 'ごま油', quantity: '大さじ1' }
    ]
  },
  6: {
    id: 6,
    title: 'グラタン',
    genre: '洋食',
    servings: '4人分',
    image: null,
    body: '1. マカロニを茹でる\n2. 玉ねぎを薄切りにする\n3. ホワイトソースを作る\n4. 具材を混ぜ合わせる\n5. チーズをのせる\n6. オーブンで焼いて完成',
    likes: 19,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: 'マカロニ', quantity: '200g' },
      { id: 2, name: '鶏肉', quantity: '150g' },
      { id: 3, name: '玉ねぎ', quantity: '1個' },
      { id: 4, name: 'バター', quantity: '30g' },
      { id: 5, name: '小麦粉', quantity: '大さじ3' },
      { id: 6, name: '牛乳', quantity: '400ml' },
      { id: 7, name: 'チーズ', quantity: '100g' },
      { id: 8, name: '塩こしょう', quantity: '適量' }
    ]
  },
  7: {
    id: 7,
    title: 'ゆかりおにぎり',
    genre: '和食',
    servings: '2人分',
    image: null,
    body: '1. ご飯を炊く\n2. ゆかりをご飯に混ぜ込む\n3. 手を軽く濡らす\n4. ご飯を三角形に握る\n5. 海苔を巻いて完成',
    likes: 12,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: 'ご飯', quantity: '2杯' },
      { id: 2, name: 'ゆかり', quantity: '大さじ1' },
      { id: 3, name: '海苔', quantity: '2枚' },
      { id: 4, name: '塩', quantity: '少々' }
    ]
  },
  8: {
    id: 8,
    title: '唐揚げ',
    genre: '和食',
    servings: '3人分',
    image: null,
    body: '1. 鶏肉を一口大に切る\n2. 醤油、酒、生姜で下味をつける\n3. 片栗粉をまぶす\n4. 170度の油で揚げる\n5. 一度取り出して2度揚げする\n6. 油を切って完成',
    likes: 28,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: '鶏もも肉', quantity: '400g' },
      { id: 2, name: '醤油', quantity: '大さじ2' },
      { id: 3, name: '酒', quantity: '大さじ1' },
      { id: 4, name: '生姜', quantity: '1片' },
      { id: 5, name: '片栗粉', quantity: '適量' },
      { id: 6, name: 'サラダ油', quantity: '適量' }
    ]
  },
  9: {
    id: 9,
    title: '味噌汁',
    genre: '和食',
    servings: '4人分',
    image: null,
    body: '1. だしを取る\n2. 豆腐とわかめを用意する\n3. だしを沸騰させる\n4. 具材を入れて煮る\n5. 味噌を溶き入れる\n6. ネギを散らして完成',
    likes: 7,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: 'だし', quantity: '800ml' },
      { id: 2, name: '味噌', quantity: '大さじ3' },
      { id: 3, name: '豆腐', quantity: '1/2丁' },
      { id: 4, name: 'わかめ', quantity: '適量' },
      { id: 5, name: 'ネギ', quantity: '1本' }
    ]
  },
  10: {
    id: 10,
    title: '焼きそば',
    genre: '中華',
    servings: '2人分',
    image: null,
    body: '1. 野菜を切る\n2. 麺を茹でる\n3. フライパンで野菜を炒める\n4. 麺を加えて炒める\n5. ソースを絡める\n6. 青のりをかけて完成',
    likes: 18,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: '焼きそば麺', quantity: '2玉' },
      { id: 2, name: 'キャベツ', quantity: '1/4個' },
      { id: 3, name: '人参', quantity: '1/2本' },
      { id: 4, name: 'もやし', quantity: '1袋' },
      { id: 5, name: '豚こま肉', quantity: '100g' },
      { id: 6, name: '焼きそばソース', quantity: '1袋' },
      { id: 7, name: '青のり', quantity: '適量' }
    ]
  },
  11: {
    id: 11,
    title: 'チャーハン',
    genre: '中華',
    servings: '2人分',
    image: null,
    body: '1. ご飯を冷ます\n2. 卵を溶きほぐす\n3. フライパンで卵を炒める\n4. ご飯を加えて炒める\n5. 調味料で味付けする\n6. ネギを散らして完成',
    likes: 22,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: 'ご飯', quantity: '2杯' },
      { id: 2, name: '卵', quantity: '2個' },
      { id: 3, name: 'ハム', quantity: '2枚' },
      { id: 4, name: 'ネギ', quantity: '1本' },
      { id: 5, name: '醤油', quantity: '大さじ1' },
      { id: 6, name: '塩こしょう', quantity: '適量' },
      { id: 7, name: 'ごま油', quantity: '小さじ1' }
    ]
  },
  12: {
    id: 12,
    title: 'オムライス',
    genre: '洋食',
    servings: '2人分',
    image: null,
    body: '1. チキンライスを作る\n2. 卵を溶きほぐす\n3. フライパンで卵を焼く\n4. チキンライスを包む\n5. ケチャップをかける\n6. パセリを散らして完成',
    likes: 35,
    isLiked: false,
    isFavorited: false,
    ingredients: [
      { id: 1, name: 'ご飯', quantity: '2杯' },
      { id: 2, name: '卵', quantity: '4個' },
      { id: 3, name: '鶏肉', quantity: '100g' },
      { id: 4, name: '玉ねぎ', quantity: '1/2個' },
      { id: 5, name: 'ケチャップ', quantity: '大さじ4' },
      { id: 6, name: 'バター', quantity: '20g' },
      { id: 7, name: '塩こしょう', quantity: '適量' },
      { id: 8, name: 'パセリ', quantity: '少々' }
    ]
  }
}

const handleImageError = (event) => {
  console.error('Image loading failed:', event)
}

const handleImageLoad = (event) => {
  console.log('Image loaded successfully:', event)

}

const handleSubmitComment = async ({ content, onSuccess, onError }) => {
  if (!user.value) {
    onError(new Error('ログインが必要です'))
    return
  }

  try {
    await postAuth(`recipes/${recipeId}/comments`, { content })
    await fetchComments()
    onSuccess()
  } catch (error) {
    let errorMessage = 'コメントの送信に失敗しました'

    if (error.status === 403) {
      errorMessage = '管理者はコメントできません'
    } else if (error.status === 429) {
      errorMessage = '1分以内の連続投稿はできません'
    } else if (error.data?.errors?.content) {
      errorMessage = error.data.errors.content[0]
    }

    onError(new Error(errorMessage))
  }
}

const toggleLike = async () => {
  if (!user.value) {
    return
  }

  const originalLiked = recipe.value.isLiked
  const originalLikes = recipe.value.likes

  recipe.value.isLiked = !originalLiked
  recipe.value.likes = originalLiked ? recipe.value.likes - 1 : recipe.value.likes + 1

  try {
    const response = await postAuth(`recipes/${recipe.value.id}/toggle-like`)

    const newLikedState = Boolean(response.is_liked)
    const newLikesCount = response.likes_count || 0

    recipe.value.isLiked = newLikedState
    recipe.value.likes = newLikesCount

    if (newLikedState) {
      favoriteStore.value.add(recipe.value.id)
    } else {
      favoriteStore.value.delete(recipe.value.id)
    }

    favoriteStore.value = new Set(favoriteStore.value)

  } catch (error) {
    console.error('❌ いいね切り替えエラー:', error)

    recipe.value.isLiked = originalLiked
    recipe.value.likes = originalLikes

    if (originalLiked) {
      favoriteStore.value.add(recipe.value.id)
    } else {
      favoriteStore.value.delete(recipe.value.id)
    }

    if (error.status === 401) {
      await navigateTo('/auth/login')
    } else {
      alert('いいねの更新に失敗しました。もう一度お試しください。')
    }
  }
}

const fetchComments = async () => {
  commentsLoading.value = true
  try {
    const response = await getAuth(`recipes/${recipeId}/comments`)
    const apiComments = response.data || []

    const convertedComments = apiComments.map(comment => ({
      id: comment.id,
      user: {
        name: comment.user?.name || 'ユーザー',
        avatar_path: comment.user?.avatar_url || null
      },
      body: comment.content,
      createdAt: comment.created_at
    }))

    comments.value = convertedComments

  } catch (error) {
    console.error('❌ コメント取得エラー:', error)
    comments.value = []
  } finally {
    commentsLoading.value = false
  }
}

const parseIngredients = (ingredientsString) => {
  if (!ingredientsString || typeof ingredientsString !== 'string') {
    return []
  }

  const lines = ingredientsString.split('\n').filter(line => line.trim())

  return lines.map((line, index) => {
    const trimmedLine = line.trim()
    const lastSpaceIndex = trimmedLine.lastIndexOf(' ')

    if (lastSpaceIndex > 0) {
      const name = trimmedLine.substring(0, lastSpaceIndex).trim()
      const quantity = trimmedLine.substring(lastSpaceIndex + 1).trim()

      return {
        id: index + 1,
        name: name,
        quantity: quantity
      }
    } else {
      return {
        id: index + 1,
        name: trimmedLine,
        quantity: ''
      }
    }
  })
}

const autoResize = () => {
  nextTick(() => {
    if (recipeComments.value && recipeComments.value.autoResize) {
      recipeComments.value.autoResize()
    }
  })
}

onMounted(async () => {
  await initAuth()

  if (!isLoggedIn.value) {
    return navigateTo('/auth/login')
  }

  try {
    const response = await getAuth(`recipes/${recipeId}`)
    const recipeData = response.data || response

    recipe.value = {
      id: recipeData.id,
      title: recipeData.title,
      genre: recipeData.genre,
      servings: recipeData.servings,
      image: getImageUrl(recipeData.image_url),
      body: recipeData.instructions,
      likes: recipeData.likes_count,
      isLiked: recipeData.is_liked || false,
      ingredients: parseIngredients(recipeData.ingredients || '')
    }

  } catch (apiError) {
    console.error('❌ レシピAPI取得エラー:', apiError)

    const recipeData = recipeDatabase[recipeId]

    if (!recipeData) {
      alert(`レシピ（ID: ${recipeId}）が見つかりません`)
      await navigateTo('/user')
      return
    }

    recipe.value = { ...recipeData }
  }

  await fetchComments()
  recipe.value.isLiked = favoriteStore.value.has(recipe.value.id)
  autoResize()
})

// お気に入りストアの変更を監視（他のページからの変更を反映）
watch(
  () => favoriteStore.value,
  (newFavorites) => {
    if (recipe.value.id) {
      const shouldBeLiked = newFavorites.has(recipe.value.id)
      if (recipe.value.isLiked !== shouldBeLiked) {
        recipe.value.isLiked = shouldBeLiked
      }
    }
  },
  { deep: true }
)

</script>

<style scoped>
@import "@/assets/css/common.css";

.recipe-create-container {
    display: flex;
    padding: 40px;
    gap: 40px;
    justify-content: center;
    align-items: stretch;
    width: 100%;
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

.recipe-form {
    width: 400px;
    min-height: 100%;
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

@media (max-width: 768px) {
    .recipe-create-container {
        flex-direction: column;
        padding: 20px;
        gap: 30px;
    }

    .left-column {
        width: 100%;
        min-width: auto;
        gap: 20px;
    }

    .recipe-form {
        width: 100%;
    }

    .recipe-title-heading {
        font-size: 18px;
    }
}
</style>
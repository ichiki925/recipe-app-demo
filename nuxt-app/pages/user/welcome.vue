<template>
    <div class="welcome-page">
        <div class="welcome-container">
            <img src="/images/rabbit-shape.svg" class="logo" alt="Rabbit" />
            <h1 class="title">Vanilla's Kitchen</h1>
            <p class="subtitle">日々のお料理レシピを記録</p>
            <div class="buttons">
                <NuxtLink to="/auth/register" class="btn">新規登録</NuxtLink>
                <NuxtLink to="/auth/login" class="btn ghost">ログイン</NuxtLink>
                <NuxtLink to="/" class="btn ghost">ゲストページ</NuxtLink>
            </div>
        </div>
    </div>
</template>

<script setup>
definePageMeta({
    layout: false
})
</script>

<style scoped>
.welcome-page {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;


    background-color: #f2f2f2;
    font-family: 'Noto Sans JP', sans-serif;

    display: flex;
    align-items: center;
    justify-content: center;

    margin: 0;
    padding: 20px;
}

.welcome-container {
    max-width: 400px;
    width: 90%;
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    text-align: center;
}

.logo {
    width: 60px;
    height: auto;
    max-height: 60px;
    display: block;
    margin: 0 auto 1rem;
}

.title {
    color: #555;
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    font-family: 'Italianno', cursive;
    font-weight: 400;
}

.subtitle {
    color: #444;
    font-size: 0.95rem;
    margin-bottom: 2rem;
}

.buttons {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.btn {
    background-color: #ddd;
    color: #555;
    padding: 0.75rem;
    border: none;
    text-decoration: none;
    font-weight: 400;
    display: block;
    width: 100%;
    border-radius: 5px;
    text-align: center;
    box-sizing: border-box;
}

.btn:hover {
    background-color: #cfcfcf;
}

.btn.ghost {
    background-color: transparent;
    color: #888;
    text-decoration: underline;
}

.btn.ghost:hover {
    color: #555;
    background-color: transparent;
}

@media screen and (max-width: 768px) {
    .welcome-page {
        background-color: #ffffff;
        padding: 15px;
        align-items: flex-start;
        padding-top: 10vh;
    }

    .welcome-container {
        box-shadow: none;
        border-radius: 0;
        max-width: 100%;
        padding: 1rem;
        margin-bottom: 2rem;
    }

    .title {
        font-size: 1.5rem;
    }

    .subtitle {
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
    }

    @media screen and (max-height: 600px) {
        .welcome-page {
            padding-top: 5vh;
        }

        .welcome-container {
            padding: 1rem 1rem 2rem;
        }

        .title {
            font-size: 1.3rem;
            margin-bottom: 0.3rem;
        }

        .subtitle {
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }
    }
}
</style>
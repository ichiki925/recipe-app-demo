<template>
    <div>
        <Head>
            <Title>Vanilla's Kitchen | バニラキッチン</Title>
            <Meta charset="UTF-8" />
            <Meta name="viewport" content="width=device-width, initial-scale=1.0" />
            <Meta name="description" content="登録制のレシピ管理アプリ『バニラキッチン（Vanilla's Kitchen）』公式サイト。特徴や使い方のご案内。"/>
            <Link rel="canonical" href="https://vanilla.k-ichiki.com/" />
            <Meta property="og:type" content="website" />
            <Meta property="og:title" content="バニラキッチン（Vanilla's Kitchen）" />
            <Meta property="og:description" content="登録制のレシピ管理アプリ『バニラキッチン』公式サイト。" />
            <Meta property="og:url" content="https://vanilla.k-ichiki.com/" />
            <Meta property="og:image" content="https://vanilla.k-ichiki.com/og.jpg" />
            <Meta name="twitter:card" content="summary_large_image" />
        </Head>

        <header>
            <div class="container">
            <div class="logo">
                <img src="/images/rabbit-shape.svg" alt="バニラキッチン（Vanilla's Kitchen）のロゴ" class="logo-image" />
                <span class="logo-text">
                    Vanilla's Kitchen
                    <span class="sr-only">（バニラキッチン）</span>
                </span>
            </div>
            <nav>
                <ul>
                    <li><NuxtLink to="/auth/login"  class="nav-link">Login</NuxtLink></li>
                    <li><NuxtLink to="/auth/register" class="nav-link">Sign Up</NuxtLink></li>
                </ul>
            </nav>
            </div>
        </header>

        <main>
            <NuxtPage />
        </main>
    </div>
</template>

<style>
    @import "@/assets/css/common.css";
    
    .sr-only{
        position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;
        clip:rect(0,0,0,0);white-space:nowrap;border:0;
    }
</style>

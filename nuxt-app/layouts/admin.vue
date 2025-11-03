<template>
    <div>
        <Head>
            <Title>Vanilla's Kitchen</Title>
            <Meta charset="UTF-8" />
            <Meta name="viewport" content="width=device-width, initial-scale=1.0" />
        </Head>

        <header>
            <div class="container">
                <div class="logo" @click="goToDashboard">
                    <img src="/images/rabbit-shape.svg" alt="Logo" class="logo-image" />
                    <span class="logo-text">Vanilla's Kitchen</span>
                </div>
                <nav>
                    <ul>
                        <li>
                            <button type="button" class="nav-link" @click.prevent="logout">
                                Logout
                            </button>
                        </li>
                    </ul>
                </nav>
            </div>
        </header>

        <main>
            <NuxtPage />
        </main>
        </div>
    </template>

    <script setup>
    const { logout: authLogout } = useAuth()
    const router = useRouter()

    const goToDashboard = () => {
        router.push('/admin/dashboard')
    }

    const logout = async () => {
        try {
            await authLogout()
        } catch (error) {
            console.error('Logout failed:', error)
        }
    }
    </script>

    <style>
    @import "@/assets/css/common.css";
    </style>

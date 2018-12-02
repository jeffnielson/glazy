<template>
    <div v-if="$auth.ready()"
        class="container-fluid app-container">
        <AppHeader/>
        <transition
            name="fade"
            mode="out-in"
        >
            <keep-alive include="Search">
                <router-view/>
            </keep-alive>
        </transition>
    </div>
</template>

<script>
import AppHeader from "./AppHeader.vue";

export default {
  name: "AppLayoutFull",
  components: {
    AppHeader
  },
  mounted() {}
}
</script>

<style>
.app-container {
    min-height: 100%;
}

.fade-enter-active,
.fade-leave-active {
  transition-duration: 0.3s;
  transition-property: opacity;
  transition-timing-function: ease;
}

.fade-enter,
.fade-leave-active {
  opacity: 0;
}
</style>
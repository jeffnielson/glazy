import Vue from 'vue'
import Router from 'vue-router'

import AppLayoutFull from '../views/layout/AppLayoutFull'

import Login from '../views/Login'
import Register from '../views/Register'

// import Home from '../views/Home'
import Search from '../views/Search'
import Calculator from '../views/Calculator'
import Recipe from '../views/Recipe'
// import RecipeEdit from '../views/RecipeEdit'

import Error404 from '../views/error/404'
import Error403 from '../views/error/403'
import Error502 from '../views/error/502'

Vue.use(Router)

export default new Router({
  mode: 'history',
  scrollBehavior: function(to, from, savedPosition) {
    if (to.hash) {
      return {selector: to.hash}
    } else {
      return {x: 0, y: 0}
    }
  },
  routes: [
    {
      path: '/',
      redirect: '/search',
      name: 'home',
      component: AppLayoutFull,
      children: [
        {
          path: '/login/:type?',
          name: 'login',
          component: Login
        },
        {
          path: '/register',
          name: 'register',
          component: Register
        },
        {
          path: '/calculator',
          name: 'calculator',
          component: Calculator
        },
        {
          path: '/search',
          name: 'search',
          component: Search
        },
        {
          path: '/u/:id',
          name: 'user',
          component: Search
        },
        {
          path: '/u/:id/materials',
          name: 'user-materials',
          component: Search
        },
        {
          path: '/materials',
          name: 'materials',
          component: Search
        },
        {
          path: '/recipes/:id',
          name: 'recipes',
          component: Recipe
        },
        {
          path: '/materials/:id',
          name: 'material',
          component: Recipe
        },
        {
          path: '/404',
          name: 'error-404',
          component: Error404
        },
        {
          path: '/403',
          name: 'error-403',
          component: Error403
        },
        {
          path: '/502',
          name: 'error-502',
          component: Error502
        },
        { path: '*', redirect: '/search' }
      ]
    },
  ]
})
